<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // nullable + nullOnDelete: kalau lokasi kerja dihapus, histori absensi tetap ada
            $table->foreignId('work_location_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', ['masuk', 'pulang']);
            $table->string('photo_path'); // path di disk s3 (MinIO)

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // jarak & validitas disimpan sebagai snapshot saat absen terjadi,
            // supaya histori tidak berubah walau radius work_location diubah nanti
            $table->decimal('distance_meters', 8, 2)->nullable();
            $table->boolean('is_valid')->default(false);

            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['user_id', 'type', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};