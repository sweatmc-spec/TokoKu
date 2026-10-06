<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            // alasan admin saat mengubah keputusan (terima <-> tolak) lewat tombol Edit
            $table->text('alasan_edit')->nullable()->after('catatan_admin');
            $table->timestamp('edited_at')->nullable()->after('alasan_edit');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn(['alasan_edit', 'edited_at']);
        });
    }
};