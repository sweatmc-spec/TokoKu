<?php

namespace App\Services;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Terjual;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya tempat stok berubah karena penjualan.
 *
 * store / update / destroy semuanya memakai pola yang sama:
 *   1. kunci (lockForUpdate) baris produk & varian yang terlibat, urut berdasarkan id
 *   2. kembalikan stok transaksi lama (kalau ada) di memori
 *   3. cek & kurangi stok baru di memori
 *   4. kalau ada yang gagal -> ValidationException, DB::transaction rollback, tidak ada yang tersimpan
 *   5. simpan stok + transaksi + detail
 *
 * Harga TIDAK pernah diterima dari browser: harga satuan diambil dari Profit Harga produk
 * (atau harga lama transaksi ini untuk barang yang sudah ada di transaksi saat diedit).
 */
class TerjualService
{
    /**
     * Daftar barang yang bisa dijual untuk dropdown kasir.
     * Aturan sama dengan halaman Stok: hanya produk yang sudah pernah dicentang datang di Cek Paket.
     * Kategori yang punya varian (Pakaian) dijual per varian; kategori lain per produk.
     *
     * Saat edit, stok yang ditampilkan = stok sekarang + qty lama transaksi ini, dan barang lama
     * tetap memakai harga lamanya.
     *
     * @return array<int, array{id:string, group:string, name:string, variant:?string, label:string, stock:int, price:?int, price_locked:bool}>
     */
    public function catalog(?Terjual $editing = null): array
    {
        $categories = Category::all()->keyBy('id');
        $old = $editing ? $editing->items->keyBy(fn ($item) => $item->item_key) : collect();

        $products = Product::query()
            ->whereHas('purchaseItems', fn ($q) => $q->received())
            ->with([
                'profitHarga:id,harga,code',
                'variants' => fn ($q) => $q->whereHas('purchaseItems', fn ($i) => $i->received()),
            ])
            ->orderBy('name')
            ->get();

        $entries = [];

        foreach ($products as $product) {
            $category = $categories->get($product->category_id);
            $group    = $category?->name ?? 'Lainnya';
            $price    = $product->profitHarga?->harga;

            if ($category?->supportsVariants()) {
                foreach ($product->variants as $variant) {
                    $entries[] = $this->entry(
                        'v:' . $variant->id, $group, $product->name, $variant->label(),
                        min((int) $variant->stock, (int) $product->stock), $price, $old
                    );
                }
            } else {
                $entries[] = $this->entry('p:' . $product->id, $group, $product->name, null, (int) $product->stock, $price, $old);
            }
        }

        // Barang di transaksi ini yang sudah tidak ada di daftar (mis. produknya dihapus): tetap tampil
        // supaya barisnya bisa dibaca; saat disimpan, service akan menolaknya dengan pesan yang jelas.
        $known = collect($entries)->pluck('id')->all();
        foreach ($old as $key => $item) {
            if (! in_array($key, $known, true)) {
                $label = $item->variant_label ? $item->product_name . ' — ' . $item->variant_label : $item->product_name;
                $entries[] = [
                    'id' => $key, 'group' => 'Lainnya', 'name' => $item->product_name, 'variant' => $item->variant_label,
                    'label' => $label, 'stock' => (int) $item->qty, 'price' => (int) $item->unit_price, 'price_locked' => true,
                ];
            }
        }

        return $entries;
    }

    private function entry(string $key, string $group, string $name, ?string $variant, int $stock, $price, Collection $old): array
    {
        $existing = $old->get($key);

        return [
            'id'           => $key,
            'group'        => $group,
            'name'         => $name,
            'variant'      => $variant,
            'label'        => $variant ? $name . ' — ' . $variant : $name,
            'stock'        => $stock + (int) ($existing->qty ?? 0),
            'price'        => $existing ? (int) $existing->unit_price : ($price !== null ? (int) $price : null),
            'price_locked' => (bool) $existing,
        ];
    }

    // ------------------------------------------------------------------ CRUD

    public function store(array $data, int $userId): Terjual
    {
        return DB::transaction(function () use ($data, $userId) {
            $terjual = new Terjual(['code' => $this->nextCode(), 'user_id' => $userId]);

            return $this->persist($terjual, $data);
        }, 3);
    }

    public function update(Terjual $terjual, array $data): Terjual
    {
        return DB::transaction(function () use ($terjual, $data) {
            $terjual = Terjual::whereKey($terjual->getKey())->lockForUpdate()->firstOrFail();

            return $this->persist($terjual, $data);
        }, 3);
    }

    public function destroy(Terjual $terjual): void
    {
        DB::transaction(function () use ($terjual) {
            $terjual = Terjual::whereKey($terjual->getKey())->lockForUpdate()->firstOrFail();
            $old     = $terjual->items()->get();

            [$products, $variants] = $this->lockStock($old, collect());
            $this->restore($old, $products, $variants);
            $this->saveStock($products, $variants);

            $terjual->delete();   // detail ikut terhapus (cascade)
        }, 3);
    }

    // -------------------------------------------------------------- internals

    private function persist(Terjual $terjual, array $data): Terjual
    {
        $wanted = $this->parseItems($data['items']);
        $old    = $terjual->exists ? $terjual->items()->get() : collect();

        [$products, $variants] = $this->lockStock($old, $wanted);

        // 2. stok lama kembali dulu (di memori), baru stok baru dicek
        $this->restore($old, $products, $variants);

        // barang yang sudah ada di transaksi ini tetap memakai harga lamanya
        $keepPrice = $old->mapWithKeys(fn ($item) => [$item->item_key => (int) $item->unit_price]);

        // ...dan harga modalnya juga (termasuk yang null): laba transaksi lama tidak berubah walau harga beli berubah.
        // Dipakai has() (bukan isset): modal yang memang null tetap dianggap "sudah ada", tidak dihitung ulang.
        $keepModal = $old->mapWithKeys(fn ($item) => [$item->item_key => $item->harga_modal]);

        $lines  = [];
        $errors = [];

        foreach ($wanted as $i => $row) {
            $variant = $row['type'] === 'v' ? $variants->get($row['id']) : null;
            $product = $row['type'] === 'v'
                ? ($variant ? $products->get($variant->product_id) : null)
                : $products->get($row['id']);

            if (! $product) {
                $errors["items.$i.item"] = 'Barang tidak ditemukan atau sudah dihapus dari Master Data. Hapus baris ini.';
                continue;
            }

            $variantLabel = $variant?->label();
            $name         = $variantLabel ? $product->name . ' — ' . $variantLabel : $product->name;
            $available    = $variant ? min((int) $variant->stock, (int) $product->stock) : (int) $product->stock;

            if ($row['qty'] > $available) {
                $errors["items.$i.qty"] = "Stok {$name} tidak cukup (tersisa {$available}).";
                continue;
            }

            $price = $keepPrice[$row['key']] ?? $product->profitHarga?->harga;

            if ($price === null) {
                $errors["items.$i.item"] = "{$name} belum punya Profit Harga, jadi belum bisa dijual.";
                continue;
            }

            $price = (int) $price;

            // Harga modal per pcs: baris lama tetap pakai modalnya; barang baru = harga beli terakhir produk
            // (products.last_cost, diisi saat barang dicentang datang di Cek Paket).
            $modal = $keepModal->has($row['key'])
                ? $keepModal->get($row['key'])
                : (($product->last_cost !== null && (float) $product->last_cost > 0) ? round((float) $product->last_cost, 2) : null);

            $product->stock -= $row['qty'];
            if ($variant) {
                $variant->stock -= $row['qty'];
            }

            $lines[] = [
                'product_id'         => $product->id,
                'product_variant_id' => $variant?->id,
                'product_name'       => $product->name,
                'variant_label'      => $variantLabel,
                'qty'                => $row['qty'],
                'unit_price'         => $price,
                'harga_modal'        => $modal,
                'total_price'        => $price * $row['qty'],
            ];
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        // 3. total, diskon, bayar
        $subtotal = (int) collect($lines)->sum('total_price');
        [$discountType, $discountValue, $discountAmount] = $this->discount(
            $subtotal, $data['discount_type'] ?? null, $data['discount_value'] ?? 0
        );

        $total = $subtotal - $discountAmount;
        $paid  = (int) $data['paid'];

        if ($paid < $total) {
            throw ValidationException::withMessages([
                'paid' => 'Jumlah bayar kurang dari total (' . Terjual::rupiah($total) . ').',
            ]);
        }

        $method = PaymentMethod::find($data['payment_method_id']);

        // 4. simpan
        $this->saveStock($products, $variants);

        $terjual->fill([
            'sold_at'             => $data['sold_at'] ?? ($terjual->sold_at ?? now()),
            'customer_name'       => trim((string) ($data['customer_name'] ?? '')) ?: 'Umum',
            'payment_method_id'   => $method?->id,
            'payment_method_name' => $method?->name,
            'subtotal'            => $subtotal,
            'discount_type'       => $discountType,
            'discount_value'      => $discountValue,
            'discount_amount'     => $discountAmount,
            'total'               => $total,
            'paid_amount'         => $paid,
            'change_amount'       => $paid - $total,
            'note'                => $data['note'] ?? null,
        ])->save();

        $terjual->items()->delete();
        $terjual->items()->createMany($lines);

        // catat otomatis ke Pemasukan (satu transaction dengan penyimpanan transaksi dan stok)
        app(KeuanganService::class)->catatPenjualan($terjual);

        return $terjual->load('items');
    }

    /**
     * Kunci semua produk & varian yang terlibat (transaksi lama + baru) dalam urutan id yang sama
     * di setiap request, supaya dua kasir yang bekerja bersamaan tidak saling deadlock.
     *
     * @return array{0: Collection, 1: Collection} [produk by id, varian by id]
     */
    private function lockStock(Collection $old, Collection|array $wanted): array
    {
        $wanted = collect($wanted);

        $variantIds = $wanted->where('type', 'v')->pluck('id')
            ->merge($old->pluck('product_variant_id'))
            ->filter()->unique()->values();

        $productIds = $wanted->where('type', 'p')->pluck('id')
            ->merge($old->pluck('product_id'))
            ->merge($variantIds->isEmpty() ? [] : ProductVariant::whereIn('id', $variantIds)->pluck('product_id'))
            ->filter()->unique()->values();

        $products = Product::with('profitHarga')
            ->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        $variants = ProductVariant::whereIn('id', $variantIds)
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        return [$products, $variants];
    }

    /** Kembalikan stok barang-barang transaksi lama (hanya di memori, belum disimpan). */
    private function restore(Collection $old, Collection $products, Collection $variants): void
    {
        foreach ($old as $item) {
            if ($item->product_id && $product = $products->get($item->product_id)) {
                $product->stock += $item->qty;
            }
            if ($item->product_variant_id && $variant = $variants->get($item->product_variant_id)) {
                $variant->stock += $item->qty;
            }
        }
    }

    private function saveStock(Collection $products, Collection $variants): void
    {
        foreach ($products as $product) {
            $product->save();   // hanya menulis kalau ada perubahan
        }
        foreach ($variants as $variant) {
            $variant->save();
        }
    }

    /** "v:34" / "p:12" -> ['key','type','id','qty']. Kunci array (index baris di form) dipertahankan untuk pesan error. */
    private function parseItems(array $items): Collection
    {
        return collect($items)->map(function ($row) {
            [$type, $id] = explode(':', $row['item']);

            return ['key' => $row['item'], 'type' => $type, 'id' => (int) $id, 'qty' => (int) $row['qty']];
        });
    }

    /** @return array{0: ?string, 1: int, 2: int} [tipe, nilai input, nominal Rp] */
    private function discount(int $subtotal, ?string $type, $value): array
    {
        $value = max(0, (int) $value);

        if ($value === 0 || ! in_array($type, ['nominal', 'persen'], true)) {
            return [null, 0, 0];
        }

        $amount = $type === 'persen'
            ? (int) round($subtotal * min($value, 100) / 100)
            : $value;

        if ($amount > $subtotal) {
            throw ValidationException::withMessages([
                'discount_value' => 'Diskon tidak boleh lebih besar dari subtotal (' . Terjual::rupiah($subtotal) . ').',
            ]);
        }

        return [$type, $value, $amount];
    }

    /** TRX-YYYYMMDD-0001, urutan mulai lagi dari 0001 setiap hari. Dipanggil di dalam transaction. */
    private function nextCode(): string
    {
        $prefix = 'TRX-' . now()->format('Ymd') . '-';

        $last = Terjual::where('code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
