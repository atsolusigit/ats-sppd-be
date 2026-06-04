<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('td_sppd_transportations', function (Blueprint $table) {

            $table->decimal('actual_biaya', 18, 2)
                ->nullable()
                ->default(null)
                ->change();
        });

        Schema::table('td_sppd_accommodations', function (Blueprint $table) {

            $table->decimal('actual_biaya', 18, 2)
                ->nullable()
                ->default(null)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('td_sppd_transportations', function (Blueprint $table) {

            $table->decimal('actual_biaya', 18, 2)
                ->default(0)
                ->change();
        });

        Schema::table('td_sppd_accommodations', function (Blueprint $table) {

            $table->decimal('actual_biaya', 18, 2)
                ->default(0)
                ->change();
        });
    }
};