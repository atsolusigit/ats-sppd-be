<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_jabatan_approvals', function (Blueprint $table) {

            $table->dropForeign([
                'jabatan_id'
            ]);


            $table->renameColumn(
                'approval_level',
                'approval_order'
            );

            $table->dropColumn([
                'jabatan_id',
            ]);

            $table->enum('approval_mode', [
                'hierarchy',
                'jabatan',
                'department',
                'role',
                'user',
            ])->default('hierarchy')
              ->after('approval_order');

            $table->integer('target_level')
                ->nullable()
                ->after('approval_mode');

            $table->unsignedBigInteger('target_jabatan_id')
                ->nullable()
                ->after('target_level');

            $table->unsignedBigInteger('target_department_id')
                ->nullable()
                ->after('target_jabatan_id');

            $table->unsignedBigInteger('target_role_id')
                ->nullable()
                ->after('target_department_id');

            $table->unsignedBigInteger('target_user_id')
                ->nullable()
                ->after('target_role_id');

            $table->boolean('is_required')
                ->default(true)
                ->after('target_user_id');

            $table->boolean('can_reject')
                ->default(true)
                ->after('is_required');

            $table->boolean('can_revision')
                ->default(true)
                ->after('can_reject');
        });


        Schema::table('mst_jabatan_approvals', function (Blueprint $table) {

            $table->foreign('target_jabatan_id')
                ->references('id')
                ->on('mst_jabatans')
                ->nullOnDelete();

            $table->foreign('target_department_id')
                ->references('id')
                ->on('mst_departments')
                ->nullOnDelete();

            $table->foreign('target_role_id')
                ->references('id')
                ->on('mst_roles')
                ->nullOnDelete();

            $table->foreign('target_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index([
                'approval_flow_id',
                'approval_order'
            ], 'idx_flow_order');
        });
    }

    public function down(): void
    {
        Schema::table('mst_jabatan_approvals', function (Blueprint $table) {

            $table->dropForeign([
                'target_jabatan_id'
            ]);

            $table->dropForeign([
                'target_department_id'
            ]);

            $table->dropForeign([
                'target_role_id'
            ]);

            $table->dropForeign([
                'target_user_id'
            ]);

            $table->dropIndex(
                'idx_flow_order'
            );


            $table->dropColumn([
                'approval_mode',
                'target_level',
                'target_jabatan_id',
                'target_department_id',
                'target_role_id',
                'target_user_id',
                'is_required',
                'can_reject',
                'can_revision',
            ]);

            $table->unsignedBigInteger('jabatan_id');

            $table->boolean('can_approve')
                ->default(true);


            $table->renameColumn(
                'approval_order',
                'approval_level'
            );
        });


        Schema::table('mst_jabatan_approvals', function (Blueprint $table) {

            $table->foreign('jabatan_id')
                ->references('id')
                ->on('mst_jabatans')
                ->cascadeOnDelete();
        });
    }
};