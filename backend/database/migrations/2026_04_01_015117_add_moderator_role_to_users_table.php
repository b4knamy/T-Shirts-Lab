<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL uses CHECK constraints for enum-like columns.
        // SQLite (used in tests) has no ALTER TABLE constraints, so skip.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['CUSTOMER', 'VENDOR', 'ADMIN', 'SUPER_ADMIN', 'MODERATOR']))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['CUSTOMER', 'VENDOR', 'ADMIN', 'SUPER_ADMIN']))");
        }
    }
};
