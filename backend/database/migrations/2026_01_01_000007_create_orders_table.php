<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('orders', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('order_number')->unique();
      $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
      $table->decimal('subtotal', 10, 2)->default(0);
      $table->decimal('discount_amount', 10, 2)->default(0);
      $table->decimal('tax_amount', 10, 2)->default(0);
      $table->decimal('shipping_cost', 10, 2)->default(0);
      $table->decimal('total', 10, 2)->default(0);
      $table->enum('status', ['PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELLED', 'REFUNDED'])->default('PENDING');
      $table->enum('payment_status', ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'REFUNDED'])->default('PENDING');
      $table->foreignUuid('shipping_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
      $table->foreignUuid('billing_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
      $table->text('customer_notes')->nullable();
      $table->text('admin_notes')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('orders');
  }
};
