<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr_attachments', function (Blueprint $table) {

            $table->id();

            // Relasi utama SPPD
            $table->unsignedBigInteger('sppd_id');

            // Kalau attachment melekat ke child (transport / penginapan / report item)
            $table->unsignedBigInteger('reference_id')->nullable();

            /**
             * module:
             * - sppd
             * - realisasi
             * - report
             */
            $table->string('module');

            /**
             * category:
             * - transport
             * - accommodation
             * - general
             */
            $table->string('category')->nullable();

            /**
             * type:
             * - file
             * - link
             */
            $table->enum('type', ['file', 'link']);

            // FILE
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();

            // LINK
            $table->text('url')->nullable();

            // AUDIT
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            /**
             * INDEXES
             */
            $table->index(['sppd_id']);
            $table->index(['reference_id']);
            $table->index(['module']);
            $table->index(['category']);

            /**
             * FOREIGN KEY (optional kalau tabel kamu sudah konsisten)
             */
            $table->foreign('sppd_id')
                ->references('id')
                ->on('tr_sppd')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_attachments');
    }
};