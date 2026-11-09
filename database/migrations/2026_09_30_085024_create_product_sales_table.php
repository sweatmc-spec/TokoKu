<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Produk mana dijual oleh sales mana (satu produk boleh dijual banyak sales). */
    public function up(): void
    {
        Schema::create('product_sales', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('sales_id')->constrained('sales')->cascadeOnDelete();
            $table->primary(['product_id', 'sales_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sales');
    }
};
