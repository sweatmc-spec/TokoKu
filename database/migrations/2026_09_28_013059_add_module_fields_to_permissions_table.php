<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->foreignId('module_id')->nullable()->after('name')->constrained('modules')->nullOnDelete();
            $table->string('action')->nullable()->after('module_id'); // view, create, edit, delete, dst
            $table->string('desc')->nullable()->after('action');
        });

        // Kolom lama dari iterasi sebelumnya, sekarang digantikan relasi module_id.
        if (Schema::hasColumn('permissions', 'feature')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropColumn('feature');
            });
        }
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('module_id');
            $table->dropColumn(['action', 'desc']);
            $table->string('feature')->nullable();
        });
    }
};