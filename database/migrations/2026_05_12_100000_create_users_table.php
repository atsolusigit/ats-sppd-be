<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |-----------------------------------------
        | USERS (SPPD SYSTEM)
        |-----------------------------------------
        */
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // identity
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('username')->unique()->nullable();

            $table->timestamp('email_verified_at')->nullable();

            // employee data
            $table->string('nip', 50)->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();

            // auth
            $table->string('password');

            // status user
            $table->tinyInteger('status')->default(0);

            // profile
            $table->string('profile_img')->nullable();

            // relation
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();

            // session remember login
            $table->rememberToken();

            $table->timestamps();

            // FK department
            $table->foreign('department_id')
                ->references('id')
                ->on('mst_departments')
                ->nullOnDelete();

            // FK role
            $table->foreign('role_id')
                ->references('id')
                ->on('mst_roles')
                ->nullOnDelete();
        });
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->foreignId('user_id')
                ->nullable()
                ->index()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};