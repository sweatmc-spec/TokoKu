<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseItem;
use App\Models\VariantOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductVariantController extends Controller
{
    /** Halaman varian satu produk (hanya untuk kategori yang punya varian, mis. Pakaian). */
    public function show(Product $product)
    {
        $product->load(['category:id,name,slug', 'sales:id,name']);

        abort_unless($product->category->supportsVariants(), 404);

        $variants = $product->variants()->orderBy('id')->get();

        // Pilihan berasal dari Master Data > Variasi Pakaian
        $options = VariantOption::ordered()->get()->groupBy('type');
        $names   = fn (string $type) => $options->get($type, collect())->pluck('name')->values();

        $config = [
            'saveUrl'  => route('master-data.products.variants.sync', $product),
            'canEdit'  => (bool) auth()->user()?->can('master-produk-sales.edit'),
            'variants' => $variants->map(fn ($v) => [
                'id'       => $v->id,
                'size'     => $v->size,
                'color'    => $v->color,
                'material' => $v->material,
                'style'    => $v->style,
                'stock'    => $v->stock,
            ])->values(),
            'sizes'  => $names('size'),
            'colors' => $options->get('color', collect())->mapWithKeys(fn ($o) => [$o->name => $o->hex]),
        ];

        return view('master-data.products.show', [
            'product'   => $product,
            'config'    => $config,
            'materials' => $names('material'),
            'styles'    => $names('style'),
        ]);
    }

    /**
     * Simpan seluruh daftar varian sekaligus (tambah / ubah / hapus).
     * Ukuran, warna, bahan, dan model WAJIB berasal dari Master Data > Variasi Pakaian.
     * Stok TIDAK pernah diterima dari form: varian baru mulai dari 0 dan stok
     * hanya bertambah lewat Tambah Produk.
     */
    public function sync(Request $request, Product $product): JsonResponse
    {
        $product->load('category:id,name,slug');

        abort_unless($product->category->supportsVariants(), 404);

        $validator = Validator::make($request->all(), [
            'variants'            => ['present', 'array', 'max:500'],
            'variants.*.id'       => ['nullable', 'integer'],
            'variants.*.size'     => ['required', 'string', 'max:50'],
            'variants.*.color'    => ['required', 'string', 'max:50'],
            'variants.*.material' => ['nullable', 'string', 'max:50'],
            'variants.*.style'    => ['nullable', 'string', 'max:50'],
        ], [
            'variants.max'             => 'Maksimal 500 varian per produk.',
            'variants.*.size.required' => 'ukuran wajib diisi.',
            'variants.*.color.required' => 'warna wajib diisi.',
        ]);

        // nama resmi (huruf besar/kecil sesuai master) per jenis
        $lookup = ['size' => [], 'color' => [], 'material' => [], 'style' => []];
        foreach (VariantOption::all() as $o) {
            $lookup[$o->type][mb_strtolower($o->name)] = $o->name;
        }

        $labels = ['size' => 'Ukuran', 'color' => 'Warna', 'material' => 'Bahan', 'style' => 'Model'];
        $rows   = [];

        $validator->after(function ($v) use ($request, $lookup, $labels, &$rows) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $seen = [];

            foreach ((array) $request->input('variants') as $i => $row) {
                $resolved = [];
                $failed   = false;

                foreach (['size', 'color', 'material', 'style'] as $type) {
                    $raw = $this->clean($row[$type] ?? '');

                    if ($raw === '') {
                        $resolved[$type] = null;          // bahan & model boleh kosong; ukuran & warna sudah 'required'
                        continue;
                    }

                    $official = $lookup[$type][mb_strtolower($raw)] ?? null;

                    if ($official === null) {
                        $v->errors()->add("variants.$i.$type", strtolower($labels[$type]) . " \"$raw\" tidak ada di Master Data > Variasi Pakaian > {$labels[$type]}.");
                        $failed = true;
                        continue;
                    }

                    $resolved[$type] = $official;
                }

                if ($failed) {
                    continue;
                }

                $key = ProductVariant::makeKey($resolved['size'], $resolved['color'], $resolved['material'], $resolved['style']);

                if (isset($seen[$key])) {
                    $v->errors()->add("variants.$i.size", 'kombinasi ukuran, warna, bahan, dan model ini sudah ada di daftar.');
                    continue;
                }
                $seen[$key] = true;

                $rows[] = [
                    'id'          => isset($row['id']) ? (int) $row['id'] : null,
                    'size'        => $resolved['size'],
                    'color'       => $resolved['color'],
                    'material'    => $resolved['material'],
                    'style'       => $resolved['style'],
                    'variant_key' => $key,
                ];
            }
        });

        $validator->validate();

        DB::transaction(function () use ($product, $rows) {
            $locked   = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $existing = $locked->variants()->get()->keyBy('id');
            $keepIds  = collect($rows)->pluck('id')->filter()->all();

            // 1) hapus dulu yang dibuang dari daftar (varian yang stoknya masih ada tidak boleh dihapus)
            foreach ($existing as $id => $variant) {
                if (in_array($id, $keepIds, true)) {
                    continue;
                }
                if ($variant->stock > 0) {
                    abort(422, "Varian {$variant->label()} tidak bisa dihapus karena stoknya masih ada.");
                }

                $inPending = PurchaseItem::where('product_variant_id', $id)
                    ->whereHas('purchase', fn ($q) => $q->whereNull('completed_at'))
                    ->exists();

                if ($inPending) {
                    abort(422, "Varian {$variant->label()} tidak bisa dihapus karena dipakai di pembelian yang belum selesai (Cek Paket).");
                }

                $variant->delete();
            }

            // 2) kunci sementara untuk varian yang kunci barunya berubah, supaya tukar-menukar kombinasi tidak bentrok
            foreach ($rows as $row) {
                if ($row['id'] && $existing->has($row['id']) && $existing[$row['id']]->variant_key !== $row['variant_key']) {
                    $existing[$row['id']]->forceFill(['variant_key' => '__tmp_' . $row['id']])->saveQuietly();
                }
            }

            // 3) ubah yang lama, tambah yang baru (stok tidak disentuh)
            foreach ($rows as $row) {
                $id = $row['id'];
                unset($row['id']);

                if ($id && $existing->has($id)) {
                    $existing[$id]->update($row);

                    // label di item pembelian ikut mengikuti perubahan varian
                    PurchaseItem::where('product_variant_id', $id)
                        ->update(['variant_label' => $existing[$id]->fresh()->label()]);
                } else {
                    $locked->variants()->create($row + ['stock' => 0]);
                }
            }
        });

        session()->flash('success', 'Varian ' . $product->name . ' berhasil disimpan.');

        return response()->json(['redirect' => route('master-data.products.show', $product)]);
    }

    private function clean(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }
}
