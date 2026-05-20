<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_jabatans', function (Blueprint $table) {

            // kode jabatan (HR, FIN, MGR, DIR)
            if (!Schema::hasColumn('mst_jabatans', 'code')) {
                $table->string('code', 50)->nullable()->after('name');
            }

            // level jabatan (struktur organisasi / approval level)
            if (!Schema::hasColumn('mst_jabatans', 'level')) {
                $table->integer('level')->default(1)->after('code');
            }

            // relasi ke departemen
            if (!Schema::hasColumn('mst_jabatans', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('level');
            }

            // struktur organisasi (atasan langsung)
            if (!Schema::hasColumn('mst_jabatans', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('department_id');
            }

            // flag boleh approve atau tidak
            if (!Schema::hasColumn('mst_jabatans', 'can_approve')) {
                $table->boolean('can_approve')->default(false)->after('parent_id');
            }

            // optional foreign key (kalau sudah siap relasi)
            // $table->foreign('department_id')->references('id')->on('mst_departments')->nullOnDelete();
            // $table->foreign('parent_id')->references('id')->on('mst_jabatans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mst_jabatans', function (Blueprint $table) {

            $columns = [
                'code',
                'level',
                'department_id',
                'parent_id',
                'can_approve'
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('mst_jabatans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};