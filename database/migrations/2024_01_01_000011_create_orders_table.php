<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->string('order_number', 32)->unique();
            $table->enum('status', ['pending', 'paid', 'fulfilled', 'cancelled', 'refunded'])->default('pending');
            $table->string('currency_code', 3);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('shipping_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('paid_total', 18, 2)->default(0);
            $table->decimal('due_total', 18, 2)->default(0);
            $table->dateTime('placed_at')->nullable();
            $table->timestamps();
            $table->foreign('currency_code')->references('code')->on('currencies');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained();
            $table->integer('quantity');
            $table->decimal('unit_price', 18, 2);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2);
        });

        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['billing', 'shipping']);
            $table->string('name');
            $table->string('phone', 32);
            $table->string('country', 64);
            $table->string('province', 64);
            $table->string('city', 64);
            $table->string('address_line', 255);
            $table->string('postal_code', 32);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};

