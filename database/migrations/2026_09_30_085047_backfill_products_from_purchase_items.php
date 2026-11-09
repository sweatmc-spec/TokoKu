<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pembelian sekarang wajib memilih produk dari Master Data > Produk Sales.
     * Data lama (yang produknya masih berupa teks bebas) diselamatkan di sini:
     *  1) item tanpa product_id dihubungkan ke produk dengan nama + kategori yang sama
     *     (dibuat kalau belum ada, stok 0),
     *  2) tiap produk dihubungkan ke sales yang pernah membelinya.
     * Aman kalau tabel masih kosong.
     */
    public function up(): void
    {
        $orphans = DB::table('purchase_items')
            ->whereNull('product_id')
            ->select('id', 'category_id', 'product_name')
            ->get();

        foreach ($orphans as $row) {
            $productId = DB::table('products')
                ->where('category_id', $row->category_id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($row->product_name)])
                ->value('id');

            if (! $productId) {
                $productId = DB::table('products')->insertGetId([
                    'category_id' => $row->category_id,
                    'name'        => $row->product_name,
                    'stock'       => 0,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            DB::table('purchase_items')->where('id', $row->id)->update(['product_id' => $productId]);
        }

        $links = DB::table('purchase_items as pi')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->whereNotNull('pi.product_id')
            ->select('pi.product_id', 'p.sales_id')
            ->distinct()
            ->get();

        foreach ($links as $link) {
            DB::table('product_sales')->insertOrIgnore([
                'product_id' => $link->product_id,
                'sales_id'   => $link->sales_id,
            ]);
        }
    }

    public function down(): void
    {
        // data hasil backfill dibiarkan
    }
};
