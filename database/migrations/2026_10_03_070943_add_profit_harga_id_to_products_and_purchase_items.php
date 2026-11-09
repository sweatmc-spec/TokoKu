<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - products.profit_harga_id       : Harga Jual yang tampil di halaman Stok (nullable, data lama aman).
     * - purchase_items.profit_harga_id : pilihan di Tambah Produk; disalin ke products saat paket selesai
     *                                    (stok baru bertambah setelah semua barang datang).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('profit_harga_id')->nullable()->after('category_id')
                ->constrained('profit_harga')->nullOnDelete();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('profit_harga_id')->nullable()->after('product_variant_id')
                ->constrained('profit_harga')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('profit_harga_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('profit_harga_id');
        });
    }
};
