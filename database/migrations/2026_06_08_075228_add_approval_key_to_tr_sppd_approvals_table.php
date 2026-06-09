<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tr_sppd_approvals', function (Blueprint $table) {

            $table->string('approval_key', 100)
                ->nullable()
                ->after('approval_level');

        });
    }

    public function down(): void
    {
        Schema::table('tr_sppd_approvals', function (Blueprint $table) {

            $table->dropColumn('approval_key');

        });
    }
};