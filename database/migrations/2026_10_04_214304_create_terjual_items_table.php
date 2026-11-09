<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terjual_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('terjual_id')->constrained('terjuals')->cascadeOnDelete();

            // Sama seperti purchase_items: relasi + snapshot nama, jadi riwayat tetap utuh
            // walau produk / varian dihapus dari Master Data.
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_label')->nullable();

            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('unit_price');    // harga satuan DISALIN saat transaksi (dari Profit Harga)
            $table->unsignedBigInteger('total_price');   // qty x unit_price
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terjual_items');
    }
};
