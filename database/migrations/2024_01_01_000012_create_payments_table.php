<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('provider', 50);
            $table->enum('status', ['init', 'succeeded', 'failed', 'refunded'])->default('init');
            $table->decimal('amount', 18, 2);
            $table->string('currency_code', 3);
            $table->timestamps();
            $table->foreign('currency_code')->references('code')->on('currencies');
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->string('gateway_txn_id', 128)->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('status', 50);
            $table->decimal('amount', 18, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
    }
};

