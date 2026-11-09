<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            // terisi saat pembelian selesai (produk dibuat / stok ditambah)
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            $table->string('product_name', 150);
            $table->string('unit', 20);                          // pcs / lusin / kodi / box
            $table->unsignedInteger('unit_qty');                 // jumlah dalam satuan yang dipilih
            $table->unsignedInteger('pcs_per_unit');             // isi per satuan (snapshot)
            $table->unsignedInteger('qty_pcs');                  // unit_qty * pcs_per_unit
            $table->unsignedBigInteger('unit_price');            // harga per satuan (Rupiah)
            $table->unsignedBigInteger('total_price');           // unit_qty * unit_price

            $table->timestamp('received_at')->nullable();        // centang "barang sudah datang"
            $table->timestamps();

            $table->index(['category_id', 'product_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
