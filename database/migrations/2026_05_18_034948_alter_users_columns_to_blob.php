<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | DROP UNIQUE INDEX
        |--------------------------------------------------------------------------
        */

        try {
            DB::statement("
                ALTER TABLE users
                DROP INDEX users_email_unique
            ");
        } catch (\Throwable $e) {}

        try {
            DB::statement("
                ALTER TABLE users
                DROP INDEX users_username_unique
            ");
        } catch (\Throwable $e) {}

        /*
        |--------------------------------------------------------------------------
        | ALTER COLUMN
        |--------------------------------------------------------------------------
        */

        DB::statement("
            ALTER TABLE users
            MODIFY name VARBINARY(255) NULL,
            MODIFY email VARBINARY(255) NULL,
            MODIFY username VARBINARY(255) NULL,
            MODIFY nip VARBINARY(255) NULL,
            MODIFY phone_number VARBINARY(255) NULL,
            MODIFY password BLOB NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE users
            MODIFY name VARCHAR(255) NULL,
            MODIFY email VARCHAR(255) NULL,
            MODIFY username VARCHAR(255) NULL,
            MODIFY nip VARCHAR(50) NULL,
            MODIFY phone_number VARCHAR(30) NULL,
            MODIFY password VARCHAR(255) NOT NULL
        ");

        DB::statement("
            ALTER TABLE users
            ADD UNIQUE users_email_unique (email)
        ");

        DB::statement("
            ALTER TABLE users
            ADD UNIQUE users_username_unique (username)
        ");
    }
};