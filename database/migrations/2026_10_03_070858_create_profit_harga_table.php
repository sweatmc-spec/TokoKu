<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Profit Harga = daftar HARGA JUAL per pcs.
     * (Harga beli dari sales tetap terpisah, di purchase_items.unit_price.)
     */
    public function up(): void
    {
        Schema::create('profit_harga', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('harga');          // Rupiah, per pcs
            $table->string('code', 50)->nullable();       // opsional, mis. XQC
            $table->timestamps();

            $table->index('harga');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_harga');
    }
};
