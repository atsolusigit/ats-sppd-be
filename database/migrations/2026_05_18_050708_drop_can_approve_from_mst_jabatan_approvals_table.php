<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_jabatan_approvals', function (Blueprint $table) {
            $table->dropColumn('can_approve');
        });
    }

    public function down(): void
    {
        Schema::table('mst_jabatan_approvals', function (Blueprint $table) {
            $table->boolean('can_approve')->default(true);
        });
    }
};