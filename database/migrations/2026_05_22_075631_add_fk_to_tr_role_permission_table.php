<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {

            $table->foreign('role_id')
                ->references('id')
                ->on('mst_roles')
                ->cascadeOnDelete();

            $table->foreign('permission_id')
                ->references('id')
                ->on('mst_permissions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {

            $table->dropForeign(['role_id']);

            $table->dropForeign(['permission_id']);
        });
    }
};