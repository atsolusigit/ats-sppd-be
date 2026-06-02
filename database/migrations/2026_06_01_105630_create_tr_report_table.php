<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr_report', function (Blueprint $table) {

            $table->id();

            $table->foreignId('sppd_id')
                ->constrained('tr_sppd')
                ->cascadeOnDelete();

            $table->text('tujuan_perjalanan')->nullable();

            $table->text('ringkasan_hasil_kegiatan')->nullable();

            $table->json('lampiran')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_report');
    }
};
