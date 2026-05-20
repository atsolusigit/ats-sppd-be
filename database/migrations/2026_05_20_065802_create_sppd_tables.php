<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | TR SPPD (HEADER)
        |--------------------------------------------------------------------------
        */

        Schema::create('tr_sppd', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | DOCUMENT
            |--------------------------------------------------------------------------
            */

            $table->string('sppd_number')->unique();

            // contoh:
            // SPPD REGULER
            // SPPD TRAINING
            // SPPD KUNJUNGAN
            $table->string('jenis_dokumen')->nullable();

            // DOMESTIK / LUAR_NEGERI
            $table->enum('jenis_perjalanan', [
                'domestik',
                'luar_negeri'
            ])->default('domestik');

            $table->string('cost_center')->nullable();

            $table->string('kegiatan')->nullable();

            $table->text('ringkasan_agenda')->nullable();

            /*
            |--------------------------------------------------------------------------
            | REQUESTER SNAPSHOT
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('requester_id');

            $table->unsignedBigInteger('requester_department_id')
                ->nullable();

            $table->unsignedBigInteger('requester_jabatan_id')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | TOTAL CACHE
            |--------------------------------------------------------------------------
            */

            $table->decimal('total_transport', 18, 2)
                ->default(0);

            $table->decimal('total_accommodation', 18, 2)
                ->default(0);

            $table->decimal('grand_total', 18, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | APPROVAL
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('approval_flow_id')
                ->nullable();

            $table->integer('current_approval_level')
                ->default(0);

            $table->enum('approval_status', [
                'draft',
                'submitted',
                'approved',
                'rejected',
                'revision',
                'cancelled',
                'completed'
            ])->default('draft');

            $table->timestamp('submitted_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | AUDIT
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('created_by')
                ->nullable();

            $table->unsignedBigInteger('updated_by')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | FOREIGN KEY
            |--------------------------------------------------------------------------
            */

            $table->foreign('requester_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('requester_department_id')
                ->references('id')
                ->on('mst_departments')
                ->nullOnDelete();

            $table->foreign('requester_jabatan_id')
                ->references('id')
                ->on('mst_jabatans')
                ->nullOnDelete();

            $table->foreign('approval_flow_id')
                ->references('id')
                ->on('mst_approval_flows')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | TD SPPD PARTICIPANTS
        |--------------------------------------------------------------------------
        */

        Schema::create('td_sppd_participants', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('sppd_id');

            /*
            |--------------------------------------------------------------------------
            | USER SNAPSHOT
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('user_id')
                ->nullable();

            $table->string('nama')->nullable();

            $table->string('nip')->nullable();

            $table->string('jabatan')->nullable();

            /*
            |--------------------------------------------------------------------------
            | TRAVEL
            |--------------------------------------------------------------------------
            */

            $table->string('kota_asal')->nullable();

            $table->string('kota_tujuan')->nullable();

            $table->string('tempat_sppd')->nullable();

            $table->date('dari_tanggal')->nullable();

            $table->date('sampai_tanggal')->nullable();

            /*
            |--------------------------------------------------------------------------
            | TOTAL CACHE
            |--------------------------------------------------------------------------
            */

            $table->decimal('total_transport', 18, 2)
                ->default(0);

            $table->decimal('total_accommodation', 18, 2)
                ->default(0);

            $table->decimal('total_estimation', 18, 2)
                ->default(0);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | FOREIGN KEY
            |--------------------------------------------------------------------------
            */

            $table->foreign('sppd_id')
                ->references('id')
                ->on('tr_sppd')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | TD SPPD TRANSPORTATIONS
        |--------------------------------------------------------------------------
        */

        Schema::create('td_sppd_transportations', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('participant_id');

            $table->string('jenis_transportasi')->nullable();

            $table->string('nama_travel')->nullable();

            $table->string('asal_keberangkatan')->nullable();

            $table->string('tujuan_keberangkatan')->nullable();

            $table->dateTime('waktu')->nullable();

            $table->decimal('estimasi_biaya', 18, 2)
                ->default(0);

            $table->text('keterangan')->nullable();

            $table->string('nama_lengkap')->nullable();

            $table->string('no_hp')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | FOREIGN KEY
            |--------------------------------------------------------------------------
            */

            $table->foreign('participant_id')
                ->references('id')
                ->on('td_sppd_participants')
                ->cascadeOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | TD SPPD ACCOMMODATIONS
        |--------------------------------------------------------------------------
        */

        Schema::create('td_sppd_accommodations', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('participant_id');

            $table->string('jenis_penginapan')->nullable();

            $table->string('nama_tempat')->nullable();

            $table->string('lokasi')->nullable();

            $table->date('check_in')->nullable();

            $table->date('check_out')->nullable();

            $table->decimal('estimasi_biaya', 18, 2)
                ->default(0);

            $table->text('keterangan')->nullable();

            $table->string('nama_lengkap')->nullable();

            $table->string('no_hp')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | FOREIGN KEY
            |--------------------------------------------------------------------------
            */

            $table->foreign('participant_id')
                ->references('id')
                ->on('td_sppd_participants')
                ->cascadeOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | TR SPPD APPROVALS
        |--------------------------------------------------------------------------
        */

        Schema::create('tr_sppd_approvals', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('sppd_id');

            $table->integer('approval_level');

            $table->unsignedBigInteger('approver_id')
                ->nullable();

            $table->unsignedBigInteger('approver_jabatan_id')
                ->nullable();

            $table->enum('status', [
                'waiting',
                'approved',
                'rejected',
                'revision'
            ])->default('waiting');

            $table->text('notes')->nullable();

            $table->timestamp('approved_at')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | FOREIGN KEY
            |--------------------------------------------------------------------------
            */

            $table->foreign('sppd_id')
                ->references('id')
                ->on('tr_sppd')
                ->cascadeOnDelete();

            $table->foreign('approver_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('approver_jabatan_id')
                ->references('id')
                ->on('mst_jabatans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_sppd_approvals');
        Schema::dropIfExists('td_sppd_accommodations');
        Schema::dropIfExists('td_sppd_transportations');
        Schema::dropIfExists('td_sppd_participants');
        Schema::dropIfExists('tr_sppd');
    }
};