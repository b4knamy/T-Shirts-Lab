<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('first_name', length: 80);
            $table->string('last_name', length: 80);
            $table->string('phone', length: 40)->nullable();
            $table->enum('role', ['CUSTOMER', 'VENDOR', 'ADMIN', 'SUPER_ADMIN', 'MODERATOR'])->default('CUSTOMER');
            $table->boolean('is_active')->default(true);
            $table->string('profile_picture_url')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
