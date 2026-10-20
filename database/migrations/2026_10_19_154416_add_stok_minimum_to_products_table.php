<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Batas "stok menipis" per barang (dalam pcs). Barang menipis kalau 0 < stok <= stok_minimum.
            // Default 5 untuk semua produk yang sudah ada; bisa diatur per produk di Master Data > Produk Sales.
            $table->unsignedInteger('stok_minimum')->default(5)->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stok_minimum');
        });
    }
};