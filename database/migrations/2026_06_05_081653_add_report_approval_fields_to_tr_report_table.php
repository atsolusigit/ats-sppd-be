<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tr_report', function (Blueprint $table) {

            // status workflow report
            $table->string('status')->default('draft')
                ->after('lampiran');
            // draft | submitted | in_review | approved

            // link approval flow (pakai MST flow)
            $table->unsignedBigInteger('approval_flow_id')
                ->nullable()
                ->after('status');

            // tracking level approval
            $table->integer('current_approval_level')
                ->default(0)
                ->after('approval_flow_id');

            // timestamps workflow
            $table->timestamp('submitted_at')
                ->nullable()
                ->after('current_approval_level');

            $table->timestamp('approved_at')
                ->nullable()
                ->after('submitted_at');

        });
    }

    public function down(): void
    {
        Schema::table('tr_report', function (Blueprint $table) {

            $table->dropColumn([
                'status',
                'approval_flow_id',
                'current_approval_level',
                'submitted_at',
                'approved_at',
            ]);

        });
    }
};