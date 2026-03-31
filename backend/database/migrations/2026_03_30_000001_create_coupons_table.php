<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('coupons', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('code')->unique();
      $table->string('description')->nullable();
      $table->enum('type', ['PERCENTAGE', 'FIXED'])->default('PERCENTAGE');
      $table->decimal('value', 10, 2);               // % or $ amount
      $table->decimal('min_order_amount', 10, 2)->nullable();
      $table->decimal('max_discount_amount', 10, 2)->nullable(); // cap for %
      $table->integer('usage_limit')->nullable();     // max total uses
      $table->integer('usage_count')->default(0);
      $table->integer('per_user_limit')->default(1);
      $table->boolean('is_active')->default(true);
      $table->boolean('is_public')->default(false);   // show in promo banner
      $table->timestamp('starts_at')->nullable();
      $table->timestamp('expires_at')->nullable();
      $table->timestamps();
    });

    // Track per-user usage
    Schema::create('coupon_usages', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('coupon_id')->constrained('coupons')->cascadeOnDelete();
      $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
      $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
      $table->timestamps();
    });

    // Add coupon reference to orders
    Schema::table('orders', function (Blueprint $table) {
      $table->foreignUuid('coupon_id')->nullable()->after('admin_notes')
        ->constrained('coupons')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('orders', function (Blueprint $table) {
      $table->dropConstrainedForeignId('coupon_id');
    });
    Schema::dropIfExists('coupon_usages');
    Schema::dropIfExists('coupons');
  }
};
