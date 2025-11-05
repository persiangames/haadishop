<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->bigInteger('min_points')->default(0);
            $table->json('benefits')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('source', ['order', 'referral', 'manual']);
            $table->bigInteger('points');
            $table->dateTime('occurred_at');
            $table->dateTime('expires_at')->nullable();
            $table->json('meta')->nullable();
        });

        Schema::create('loyalty_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->bigInteger('points_spent');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_redemptions');
        Schema::dropIfExists('loyalty_points');
        Schema::dropIfExists('loyalty_tiers');
    }
};

