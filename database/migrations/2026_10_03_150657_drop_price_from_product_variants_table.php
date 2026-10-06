<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Harga jual per varian dihapus: tidak dipakai Tambah Produk, dan Harga Jual produk
     * sekarang berasal dari Master Data > Profit Harga (products.profit_harga_id).
     */
    public function up(): void
    {
        if (Schema::hasColumn('product_variants', 'price')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedBigInteger('price')->nullable()->after('style');   // nilai lama tidak kembali
        });
    }
};
