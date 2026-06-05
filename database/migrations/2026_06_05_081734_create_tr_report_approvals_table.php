<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr_report_approvals', function (Blueprint $table) {

            $table->id();

            // RELATION ke report
            $table->foreignId('report_id')
                ->constrained('tr_report')
                ->cascadeOnDelete();

            // workflow approval level
            $table->integer('approval_level');

            // approver
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->unsignedBigInteger('approver_jabatan_id')->nullable();

            // status approval
            $table->string('status')->default('waiting');
            // waiting | approved | rejected | revision

            $table->text('notes')->nullable();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_report_approvals');
    }
};