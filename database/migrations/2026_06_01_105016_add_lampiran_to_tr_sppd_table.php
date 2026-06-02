<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tr_sppd', function (Blueprint $table) {

            $table->json('lampiran')
                ->nullable()
                ->after('ringkasan_agenda');

        });
    }

    public function down(): void
    {
        Schema::table('tr_sppd', function (Blueprint $table) {

            $table->dropColumn('lampiran');

        });
    }
};