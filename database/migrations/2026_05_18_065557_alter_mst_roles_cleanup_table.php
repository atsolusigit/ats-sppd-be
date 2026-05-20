<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_roles', function (Blueprint $table) {

            // HAPUS kolom yang tidak dipakai
            if (Schema::hasColumn('mst_roles', 'access')) {
                $table->dropColumn('access');
            }

            if (Schema::hasColumn('mst_roles', 'level')) {
                $table->dropColumn('level');
            }

            if (Schema::hasColumn('mst_roles', 'created_by')) {
                $table->dropColumn('created_by');
            }

            // TAMBAH kolom yang penting (kalau belum ada)
            if (!Schema::hasColumn('mst_roles', 'code')) {
                $table->string('code', 50)->after('name');
            }

            if (!Schema::hasColumn('mst_roles', 'description')) {
                $table->text('description')->nullable()->after('code');
            }

            if (!Schema::hasColumn('mst_roles', 'status')) {
                $table->tinyInteger('status')->default(1)->after('description');
            }

            if (!Schema::hasColumn('mst_roles', 'is_default')) {
                $table->boolean('is_default')->default(0)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mst_roles', function (Blueprint $table) {

            // rollback tambah kolom
            if (Schema::hasColumn('mst_roles', 'code')) {
                $table->dropColumn('code');
            }

            if (Schema::hasColumn('mst_roles', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::hasColumn('mst_roles', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('mst_roles', 'is_default')) {
                $table->dropColumn('is_default');
            }

            // optional rollback (kalau mau restore lama)
            $table->string('access')->nullable();
            $table->integer('level')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
        });
    }
};