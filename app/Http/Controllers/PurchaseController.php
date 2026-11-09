<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProfitHarga;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sales;
use App\Models\Unit;
use App\Models\VariantOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
{
    /* ------------------------------------------------------------------
     |  Cek Paket: daftar pembelian + progress
     | ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $status = $request->query('status');

        $purchases = Purchase::query()
            ->with('sales:id,name')
            ->withCount([
                'items',
                'items as received_items_count' => fn ($q) => $q->whereNotNull('received_at'),
            ])
            ->when($status === 'pending', fn ($q) => $q->whereNull('completed_at'))
            ->when($status === 'completed', fn ($q) => $q->whereNotNull('completed_at'))
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('purchases.index', compact('purchases', 'status'));
    }

    /* ------------------------------------------------------------------
     |  Tambah Produk (form pembelian dari sales)
     | ------------------------------------------------------------------ */
    public function create()
    {
        return view('purchases.form', [
            'purchase' => null,
            'config'   => $this->formConfig(null),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $purchase = DB::transaction(function () use ($payload) {
            $purchase = Purchase::create([
                'sales_id'      => $payload['sales']->id,
                'purchase_date' => $payload['purchase_date'],
                'note'          => $payload['note'],
                'total_amount'  => $payload['total'],
            ]);

            $purchase->update(['code' => $purchase->makeCode()]);

            foreach ($payload['items'] as $row) {
                unset($row['id']);
                $purchase->items()->create($row);
            }

            return $purchase;
        });

        session()->flash('success', 'Pembelian dari ' . $payload['sales']->name . ' berhasil disimpan. Centang barang yang sudah datang di halaman ini.');

        return response()->json(['redirect' => route('purchases.show', $purchase)]);
    }

    /* ------------------------------------------------------------------
     |  Detail: daftar barang + centang barang datang
     | ------------------------------------------------------------------ */
    public function show(Purchase $purchase)
    {
        $purchase->load([
            'sales',
            'items' => fn ($q) => $q->orderBy('id'),
            'items.category',
            'items.product',
            'items.variant',
        ]);

        return view('purchases.show', compact('purchase'));
    }

    /* ------------------------------------------------------------------
     |  Edit pembelian (hanya selama belum selesai)
     | ------------------------------------------------------------------ */
    public function edit(Purchase $purchase)
    {
        if ($purchase->completed_at) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', 'Pembelian yang sudah selesai tidak bisa diubah.');
        }

        $purchase->load([
            'sales',
            'items' => fn ($q) => $q->orderBy('id'),
            'items.category',
            'items.product',
            'items.variant',
        ]);

        return view('purchases.form', [
            'purchase' => $purchase,
            'config'   => $this->formConfig($purchase),
        ]);
    }

    public function update(Request $request, Purchase $purchase): JsonResponse
    {
        if ($purchase->completed_at) {
            abort(422, 'Pembelian yang semua barangnya sudah dicek tidak bisa diubah. Batalkan centang dulu kalau ada yang keliru.');
        }

        $payload = $this->validatePayload($request, $purchase);

        $change = DB::transaction(function () use ($purchase, $payload) {
            $locked = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($locked->completed_at) {
                abort(422, 'Pembelian yang semua barangnya sudah dicek tidak bisa diubah. Batalkan centang dulu kalau ada yang keliru.');
            }

            $locked->syncItems($payload['items']);     // barang yang sudah dicek dikunci

            $locked->update([
                'purchase_date' => $payload['purchase_date'],
                'note'          => $payload['note'],
                // dihitung dari data tersimpan, supaya barang yang dikunci tetap sesuai
                'total_amount'  => (int) $locked->items()->sum('total_price'),
            ]);

            return $locked->refreshCompletion();
        });

        session()->flash('success', $change === 'completed'
            ? 'Perubahan disimpan. Semua barang sudah dicek, paket selesai.'
            : 'Perubahan pembelian berhasil disimpan.');

        return response()->json(['redirect' => route('purchases.show', $purchase)]);
    }

    /* ------------------------------------------------------------------
     |  Hapus seluruh pembelian (hanya kalau belum ada barang yang dicek)
     | ------------------------------------------------------------------ */
    public function destroy(Purchase $purchase): JsonResponse
    {
        DB::transaction(function () use ($purchase) {
            $locked = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($locked->items()->whereNotNull('received_at')->exists()) {
                abort(422, 'Ada barang yang sudah dicek dan masuk stok, jadi pembelian tidak bisa dihapus. Batalkan centang barang-barangnya dulu di halaman detail.');
            }

            $locked->delete();   // item ikut terhapus (cascadeOnDelete)
        });

        session()->flash('success', 'Pembelian ' . $purchase->code . ' berhasil dihapus.');

        return response()->json(['redirect' => route('purchases.index')]);
    }

    /* ------------------------------------------------------------------
     |  Centang / batal centang "barang sudah datang"
     |  Stok bergerak PER BARANG, saat itu juga (tidak menunggu semua barang).
     | ------------------------------------------------------------------ */
    public function receive(Request $request, Purchase $purchase, PurchaseItem $item): JsonResponse
    {
        abort_if($item->purchase_id !== $purchase->id, 404);

        $received = $request->boolean('received');

        $change = DB::transaction(function () use ($purchase, $item, $received) {
            $locked = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            $row    = PurchaseItem::whereKey($item->id)->lockForUpdate()->firstOrFail();

            if ($received && ! $row->received_at) {
                $row->applyToStock();
                $row->update(['received_at' => now()]);
            } elseif (! $received && $row->received_at) {
                $row->revertFromStock();
                $row->update(['received_at' => null]);
            }

            return $locked->refreshCompletion();
        });

        $total = $purchase->items()->count();
        $done  = $purchase->items()->whereNotNull('received_at')->count();

        if ($change === 'completed') {
            session()->flash('success', 'Semua barang sudah dicek. Paket selesai.');
        } elseif ($change === 'reopened') {
            session()->flash('success', 'Paket dibuka kembali karena ada barang yang dibatalkan centangnya.');
        }

        return response()->json([
            'received'  => $done,
            'total'     => $total,
            'percent'   => $total ? (int) round($done / $total * 100) : 0,
            'completed' => $total > 0 && $done === $total,
            'changed'   => $change !== null,       // status paket berubah -> halaman perlu dimuat ulang
        ]);
    }

    /* ==================================================================
     |  Helper
     | ================================================================== */

    /** Data untuk form (dibaca JS lewat @json). */
    private function formConfig(?Purchase $purchase): array
    {
        $sales = Sales::with('categories:id,name,slug')->orderBy('name')->get();

        // kategori yang produknya punya varian (mis. Pakaian) + urutan pilihan dari Master Data > Variasi Pakaian
        $variantCategoryIds = Category::all()->filter(fn ($c) => $c->supportsVariants())->pluck('id')->values();
        $options            = VariantOption::ordered()->get()->groupBy('type');
        $orderOf            = fn (string $type) => $options->get($type, collect())->pluck('name')->values();

        return [
            'isEdit'        => (bool) $purchase,
            'submitUrl'     => $purchase ? route('purchases.update', $purchase) : route('purchases.store'),
            'method'        => $purchase ? 'PUT' : 'POST',
            'salesId'       => $purchase?->sales_id,
            'date'          => $purchase?->purchase_date?->format('Y-m-d'),
            'sales'         => $sales->map(fn ($s) => [
                'id'         => $s->id,
                'name'       => $s->name,
                'categories' => $s->categories->map(fn ($c) => [
                    'id' => $c->id, 'name' => $c->name, 'slug' => $c->slug,
                ])->values(),
            ])->values(),
            // Master Data > Profit Harga (harga jual per pcs), untuk select box di form
            'profitHarga'   => ProfitHarga::orderBy('harga')->orderBy('code')->get()
                ->map(fn ($p) => ['id' => $p->id, 'harga' => $p->harga, 'harga_formatted' => $p->harga_formatted, 'code' => $p->code])->values(),
            'variantCategories' => $variantCategoryIds,
            'variantOrder'  => [
                'color'    => $orderOf('color'),
                'size'     => $orderOf('size'),
                'material' => $orderOf('material'),
                'style'    => $orderOf('style'),
            ],
            // produk dari Master Data > Produk Sales: hanya ini yang boleh dipilih
            'products'      => Product::with(['sales:id', 'variants'])->orderBy('name')->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'c'  => $p->category_id,
                    'n'  => $p->name,
                    's'  => $p->sales->pluck('id')->values(),
                    // varian hanya dikirim untuk kategori yang memakainya (co=warna, si=ukuran, ma=bahan, st=model)
                    'v'  => $variantCategoryIds->contains($p->category_id)
                        ? $p->variants->map(fn ($v) => [
                            'id' => $v->id, 'co' => $v->color, 'si' => $v->size, 'ma' => $v->material, 'st' => $v->style,
                        ])->values()
                        : [],
                ])->values(),
            // unit dari Master Data > Unit (pcs dulu, lalu yang lebih besar, isi manual paling akhir)
            'units'         => Unit::with('categories:id')->get()
                ->sortBy(fn ($u) => [$u->pcs_per_unit ?? PHP_INT_MAX, $u->name])
                ->map(fn ($u) => [
                    'id'           => $u->id,
                    'name'         => $u->name,
                    'pcs_per_unit' => $u->pcs_per_unit,
                    'category_ids' => $u->categories->pluck('id')->values(),
                ])->values(),
            'items'         => $purchase
                ? $purchase->items->map(fn ($i) => [
                    'id'            => $i->id,
                    'category_id'   => $i->category_id,
                    'category_name' => $i->category->name,
                    'category_slug' => $i->category->slug,
                    'product_id'    => $i->product_id,
                    'product_name'  => $i->product?->name ?? $i->product_name,
                    'product_variant_id' => $i->product_variant_id,
                    'variant_label' => $i->variant_text,
                    'profit_harga_id' => $i->profit_harga_id,
                    'unit_id'       => $i->unit_id,
                    'unit_name'     => $i->unit_name,
                    'unit_qty'      => $i->unit_qty,
                    'unit_price'    => $i->unit_price,
                    'pcs_per_unit'  => $i->pcs_per_unit,
                    'received'      => (bool) $i->received_at,
                ])->values()
                : [],
        ];
    }

    /**
     * Validasi payload JSON dari form dan HITUNG ULANG semua angka di server
     * (isi per satuan, total pcs, subtotal) sehingga tidak bergantung pada JS.
     *
     * @return array{sales: Sales, purchase_date: string, note: ?string, items: array, total: int}
     */
    private function validatePayload(Request $request, ?Purchase $purchase = null): array
    {
        $rules = [
            'purchase_date'          => ['required', 'date_format:Y-m-d'],
            'note'                   => ['nullable', 'string', 'max:500'],
            'items'                  => ['required', 'array', 'min:1', 'max:200'],
            'items.*.id'             => ['nullable', 'integer'],
            'items.*.category_id'    => ['required', 'integer', 'exists:categories,id'],
            'items.*.product_id'     => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.profit_harga_id' => ['nullable', 'integer', 'exists:profit_harga,id'],
            'items.*.unit_id'        => ['required', 'integer', 'exists:units,id'],
            'items.*.unit_qty'       => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_price'     => ['required', 'integer', 'min:1', 'max:1000000000'],
            'items.*.pcs_per_unit'   => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];

        if (! $purchase) {
            $rules['sales_id'] = ['required', 'integer', 'exists:sales,id'];
        }

        $messages = [
            'sales_id.required'                => 'Pilih sales terlebih dahulu.',
            'sales_id.exists'                  => 'Sales yang dipilih tidak valid.',
            'purchase_date.required'           => 'Tanggal pembelian wajib diisi.',
            'purchase_date.date_format'        => 'Format tanggal pembelian tidak valid.',
            'note.max'                         => 'Catatan maksimal 500 karakter.',
            'items.required'                   => 'Tambahkan minimal satu barang.',
            'items.min'                        => 'Tambahkan minimal satu barang.',
            'items.max'                        => 'Maksimal 200 barang dalam satu pembelian.',
            'items.*.category_id.required'     => 'kategori wajib dipilih.',
            'items.*.category_id.exists'       => 'kategori tidak valid.',
            'items.*.product_id.required'      => 'produk wajib dipilih.',
            'items.*.product_id.exists'        => 'produk tidak valid.',
            'items.*.profit_harga_id.exists'   => 'profit harga tidak valid.',
            'items.*.unit_id.required'         => 'unit wajib dipilih.',
            'items.*.unit_id.exists'           => 'unit tidak valid.',
            'items.*.unit_qty.required'        => 'jumlah wajib diisi.',
            'items.*.unit_qty.integer'         => 'jumlah harus berupa angka.',
            'items.*.unit_qty.min'             => 'jumlah minimal 1.',
            'items.*.unit_qty.max'             => 'jumlah maksimal 100.000.',
            'items.*.unit_price.required'      => 'harga wajib diisi.',
            'items.*.unit_price.integer'       => 'harga harus berupa angka.',
            'items.*.unit_price.min'           => 'harga minimal Rp 1.',
            'items.*.unit_price.max'           => 'harga terlalu besar.',
            'items.*.pcs_per_unit.integer'     => 'isi per box harus berupa angka.',
            'items.*.pcs_per_unit.min'         => 'isi per box minimal 1.',
            'items.*.pcs_per_unit.max'         => 'isi per box maksimal 10.000.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        $sales = $purchase
            ? $purchase->sales()->with('categories')->first()
            : Sales::with('categories')->find($request->input('sales_id'));

        $units    = Unit::with('categories:id')->get()->keyBy('id');
        $products = Product::with(['sales:id', 'variants'])->get()->keyBy('id');
        $rows     = [];

        $validator->after(function ($v) use ($request, $sales, $units, $products, &$rows) {
            if ($v->errors()->isNotEmpty() || ! $sales) {
                return;
            }

            $allowedCategories = $sales->categories->keyBy('id');

            foreach ((array) $request->input('items') as $i => $item) {
                $category = $allowedCategories->get((int) $item['category_id']);

                if (! $category) {
                    $v->errors()->add("items.$i.category_id", 'kategori ini tidak dijual oleh sales yang dipilih.');
                    continue;
                }

                $product = $products->get((int) $item['product_id']);

                if (! $product
                    || (int) $product->category_id !== (int) $category->id
                    || ! $product->sales->contains('id', $sales->id)) {
                    $v->errors()->add("items.$i.product_id", 'produk tidak terdaftar untuk sales dan kategori ini (cek Master Data > Produk Sales).');
                    continue;
                }

                // Kategori dengan varian (Pakaian): wajib memilih satu varian milik produk ini
                $variant = null;

                if ($category->supportsVariants()) {
                    if ($product->variants->isEmpty()) {
                        $v->errors()->add("items.$i.product_variant_id", 'produk ini belum punya varian (buat di Master Data > Produk Sales, klik nama produknya).');
                        continue;
                    }

                    $variant = $product->variants->firstWhere('id', (int) ($item['product_variant_id'] ?? 0));

                    if (! $variant) {
                        $v->errors()->add("items.$i.product_variant_id", 'varian (warna, ukuran, bahan, model) wajib dipilih dan harus milik produk ini.');
                        continue;
                    }
                }

                $unit = $units->get((int) $item['unit_id']);

                if (! $unit || ! $unit->categories->contains('id', $category->id)) {
                    $v->errors()->add("items.$i.unit_id", 'unit tidak tersedia untuk kategori ' . $category->name . '.');
                    continue;
                }

                $pcsPerUnit = $unit->pcs_per_unit ?? (int) ($item['pcs_per_unit'] ?? 0);

                if ($pcsPerUnit < 1) {
                    $v->errors()->add("items.$i.pcs_per_unit", 'isi per ' . mb_strtolower($unit->name) . ' wajib diisi.');
                    continue;
                }

                $qty   = (int) $item['unit_qty'];
                $price = (int) $item['unit_price'];

                $rows[] = [
                    'id'           => isset($item['id']) ? (int) $item['id'] : null,
                    'category_id'  => $category->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'product_variant_id' => $variant?->id,
                    'variant_label'      => $variant?->label(),
                    'profit_harga_id'    => ! empty($item['profit_harga_id']) ? (int) $item['profit_harga_id'] : null,
                    'unit_id'      => $unit->id,
                    'unit_name'    => $unit->name,
                    'unit_qty'     => $qty,
                    'pcs_per_unit' => $pcsPerUnit,
                    'qty_pcs'      => $qty * $pcsPerUnit,
                    'unit_price'   => $price,
                    'total_price'  => $qty * $price,
                ];
            }
        });

        $validator->validate();

        return [
            'sales'         => $sales,
            'purchase_date' => $request->input('purchase_date'),
            'note'          => $request->input('note'),
            'items'         => $rows,
            'total'         => array_sum(array_column($rows, 'total_price')),
        ];
    }
}
