<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\VariantOption;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /**
     * Satu controller untuk Stok Pakaian / ATK / Miscellaneous.
     * Kategori ditentukan lewat slug dari route (->defaults('categorySlug', ...)).
     * Stok hanya dibaca: bertambah otomatis saat pembelian di Cek Paket selesai.
     */
    public function index(Request $request, string $categorySlug)
    {
        $category         = Category::where('slug', $categorySlug)->firstOrFail();
        $search           = trim((string) $request->query('q'));
        $supportsVariants = $category->supportsVariants();

        $products = Product::query()
            ->where('category_id', $category->id)
            // Produk baru masuk daftar stok setelah ada barangnya yang dicek (dicentang datang) di Cek Paket.
            // Menambah produk di Master Data > Produk Sales tidak menambah apa pun ke stok.
            ->whereHas('purchaseItems', fn ($q) => $q->received())
            ->with('profitHarga:id,harga,code')                       // Harga Jual
            ->when($supportsVariants, fn ($q) => $q->withCount([
                'variants' => fn ($v) => $v->whereHas('purchaseItems', fn ($i) => $i->received()),
            ]))
            ->when($search !== '', fn ($q) => $q->whereLike('name', '%' . $search . '%'))   // tidak peka huruf besar/kecil
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Barang yang sudah dipesan tapi pembeliannya belum selesai (belum masuk stok)
        $pending = PurchaseItem::query()
            ->where('category_id', $category->id)
            ->whereNotNull('product_id')
            ->whereNull('received_at')                                // belum dicentang = menunggu datang
            ->selectRaw('product_id, SUM(qty_pcs) as pending_pcs')
            ->groupBy('product_id')
            ->pluck('pending_pcs', 'product_id')
            ->map(fn ($v) => (int) $v);   // key = product_id

        return view('stock.index', compact('category', 'products', 'pending', 'supportsVariants', 'search'));
    }

    /**
     * Halaman rincian satu produk: semua variannya + stok, dengan filter warna / ukuran / bahan / model.
     * Pilihan filter berasal dari Master Data > Variasi Pakaian.
     */
    // PENTING: urutan argumen mengikuti urutan parameter route. Laravel mengisi argumen berurutan:
    // parameter dari URL ({product}) dulu, baru parameter dari ->defaults() (categorySlug).
    public function show(Request $request, Product $product, string $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->firstOrFail();

        abort_unless($category->supportsVariants() && (int) $product->category_id === (int) $category->id, 404);

        // Produk yang belum pernah dicek belum ada di stok
        abort_unless($product->purchaseItems()->received()->exists(), 404);

        $product->load('sales:id,name');

        // Hanya varian yang sudah pernah dicek yang masuk stok
        $all = $product->variants()
            ->whereHas('purchaseItems', fn ($q) => $q->received())
            ->get();

        // ---------- Master Data: urutan, daftar pilihan filter, kode warna ----------
        $types  = ['color', 'size', 'material', 'style'];
        $master = VariantOption::ordered()->get()->groupBy('type');

        $rank = [];
        foreach ($types as $type) {
            $rank[$type] = $master->get($type, collect())->values()
                ->mapWithKeys(fn ($o, $i) => [mb_strtolower($o->name) => $i])
                ->all();
        }

        $colorHex = $master->get('color', collect())
            ->mapWithKeys(fn ($o) => [mb_strtolower($o->name) => $o->hex]);

        $noneLabels    = ['material' => 'Tanpa bahan', 'style' => 'Tanpa model'];
        $filterOptions = [];

        foreach ($types as $type) {
            $options = $master->get($type, collect())->map(fn ($o) => [
                'value' => $o->name,
                'label' => $o->name,
                // berapa varian produk INI yang memakai pilihan tersebut (0 = tidak dipakai produk ini)
                'count' => $all->filter(fn ($v) => mb_strtolower((string) $v->{$type}) === mb_strtolower($o->name))->count(),
            ])->values();

            // bahan / model boleh kosong: sediakan juga pilihan "Tanpa ..."
            if (isset($noneLabels[$type])) {
                $none = $all->filter(fn ($v) => $v->{$type} === null || $v->{$type} === '')->count();
                if ($none > 0) {
                    $options->push(['value' => '__none__', 'label' => $noneLabels[$type], 'count' => $none]);
                }
            }

            $filterOptions[$type] = $options;
        }

        // ---------- Filter dari query string ----------
        $filters = [];
        foreach ($types as $type) {
            $filters[$type] = trim((string) $request->query($type));
        }
        $stockFilter    = (string) $request->query('stock');
        $filters['stock'] = in_array($stockFilter, ['available', 'empty', 'waiting'], true) ? $stockFilter : '';

        // ---------- Barang yang sedang dipesan per varian ----------
        $pendingVariants = $all->isEmpty() ? collect() : PurchaseItem::query()
            ->whereIn('product_variant_id', $all->pluck('id'))
            ->whereNull('received_at')                                // belum dicentang = menunggu datang
            ->selectRaw('product_variant_id, SUM(qty_pcs) as pending_pcs')
            ->groupBy('product_variant_id')
            ->pluck('pending_pcs', 'product_variant_id')
            ->map(fn ($v) => (int) $v);

        // ---------- Terapkan filter, urutkan sesuai Master Data ----------
        $variants = $all->filter(function ($v) use ($filters, $types, $pendingVariants) {
            foreach ($types as $type) {
                $wanted = $filters[$type];

                if ($wanted === '') {
                    continue;
                }

                $value = (string) $v->{$type};

                if ($wanted === '__none__') {
                    if ($value !== '') {
                        return false;
                    }
                    continue;
                }

                if (mb_strtolower($value) !== mb_strtolower($wanted)) {
                    return false;
                }
            }

            $waiting = $pendingVariants[$v->id] ?? 0;

            return match ($filters['stock']) {
                'available' => $v->stock > 0,
                'empty'     => $v->stock <= 0,
                'waiting'   => $waiting > 0,
                default     => true,
            };
        })->sortBy(fn ($v) => [
            $rank['color'][mb_strtolower($v->color)] ?? 9999,
            $rank['size'][mb_strtolower($v->size)] ?? 9999,
            mb_strtolower((string) $v->material),
            mb_strtolower((string) $v->style),
        ])->values();

        // ---------- Ringkasan (seluruh varian produk, tidak terpengaruh filter) ----------
        $summary = [
            'variants'      => $all->count(),
            'variant_stock' => (int) $all->sum('stock'),
            'waiting'       => (int) $pendingVariants->sum(),
            'unassigned'    => max(0, (int) $product->stock - (int) $all->sum('stock')),
        ];

        $filtersActive = collect($filters)->filter(fn ($v) => $v !== '')->isNotEmpty();

        return view('stock.variants', compact(
            'category', 'product', 'all', 'variants', 'pendingVariants', 'summary',
            'filterOptions', 'filters', 'filtersActive', 'colorHex'
        ));
    }
}
