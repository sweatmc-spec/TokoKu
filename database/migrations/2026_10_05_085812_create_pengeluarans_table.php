<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluarans', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->string('sumber', 20)->index();           // pembelian | operasional

            // Satu Cek Paket (purchases) yang selesai = satu baris Pengeluaran (unique).
            // restrict: catatan uang tidak boleh hilang diam-diam kalau pembeliannya dihapus.
            // (Pembelian yang selesai memang sudah tidak bisa dihapus lewat aplikasi.)
            $table->foreignId('purchase_id')->nullable()->unique()
                ->constrained('purchases')->restrictOnDelete();

            // restrict: kategori yang sudah dipakai tidak bisa dihapus
            $table->foreignId('kategori_pengeluaran_id')
                ->constrained('kategori_pengeluarans')->restrictOnDelete();

            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('nominal');
            $table->string('bukti')->nullable();              // path foto nota (opsional, hanya Operasional)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluarans');
    }
};
