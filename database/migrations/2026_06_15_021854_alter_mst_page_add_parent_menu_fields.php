<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mst_page', function (Blueprint $table) {

            $table->unsignedBigInteger('parent_id')
                ->nullable()
                ->after('head_url');

            $table->string('icon')
                ->nullable()
                ->after('parent_id');

            $table->integer('sort_order')
                ->default(0)
                ->after('icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_page', function (Blueprint $table) {

            $table->dropColumn([
                'parent_id',
                'icon',
                'sort_order'
            ]);
        });
    }
};