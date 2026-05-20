<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_jabatan_approvals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('jabatan_id');
            $table->unsignedBigInteger('approval_flow_id');

            // level approval (1,2,3)
            $table->integer('approval_level')->default(1);

            $table->boolean('can_approve')->default(true);

            $table->timestamps();

            // foreign key
            $table->foreign('jabatan_id')
                ->references('id')
                ->on('mst_jabatans')
                ->onDelete('cascade');

            $table->foreign('approval_flow_id')
                ->references('id')
                ->on('mst_approval_flows')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_jabatan_approvals');
    }
};