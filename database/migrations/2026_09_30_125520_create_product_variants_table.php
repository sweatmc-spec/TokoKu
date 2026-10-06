<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('size', 30);
            $table->string('color', 40);
            $table->string('material', 50)->nullable();
            $table->string('style', 50)->nullable();
            $table->unsignedBigInteger('price')->nullable();      // harga jual per pcs (opsional)

            // Tidak bisa diisi manual: hanya bertambah lewat Tambah Produk (pembelian dari sales).
            $table->unsignedInteger('stock')->default(0);

            // ukuran|warna|bahan|model dalam huruf kecil, supaya kombinasi yang sama tidak dobel
            $table->string('variant_key');

            $table->timestamps();

            $table->unique(['product_id', 'variant_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
