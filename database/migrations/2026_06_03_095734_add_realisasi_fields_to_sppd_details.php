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
                ->default(0)
                ->after('estimasi_biaya');

            $table->text('keterangan_realisasi')
                ->nullable()
                ->after('keterangan');

            $table->json('lampiran')
                ->nullable()
                ->after('keterangan_realisasi');
        });

        Schema::table('td_sppd_accommodations', function (Blueprint $table) {

            $table->decimal('actual_biaya', 18, 2)
                ->default(0)
                ->after('estimasi_biaya');

            $table->text('keterangan_realisasi')
                ->nullable()
                ->after('keterangan');

            $table->json('lampiran')
                ->nullable()
                ->after('keterangan_realisasi');
        });
    }

    public function down(): void
    {
        Schema::table('td_sppd_transportations', function (Blueprint $table) {

            $table->dropColumn([
                'actual_biaya',
                'keterangan_realisasi',
                'lampiran'
            ]);
        });

        Schema::table('td_sppd_accommodations', function (Blueprint $table) {

            $table->dropColumn([
                'actual_biaya',
                'keterangan_realisasi',
                'lampiran'
            ]);
        });
}
};
