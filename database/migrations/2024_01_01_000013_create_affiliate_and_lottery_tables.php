<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ref_code', 32);
            $table->string('landing_url', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->decimal('commission_amount', 18, 2)->default(0);
            $table->string('commission_currency', 3);
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->timestamps();
            $table->foreign('commission_currency')->references('code')->on('currencies');
        });

        Schema::create('lotteries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('target_pool_amount', 18, 2);
            $table->decimal('current_pool_amount', 18, 2)->default(0);
            $table->string('currency_code', 3);
            $table->boolean('is_active')->default(true);
            $table->tinyInteger('auto_draw_threshold_percent')->default(100);
            $table->timestamps();
            $table->foreign('currency_code')->references('code')->on('currencies');
        });

        Schema::create('lottery_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('buyer_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('affiliate_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('lottery_code', 32)->unique();
            $table->integer('weight')->default(1);
            $table->timestamps();
        });

        Schema::create('lottery_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_id')->constrained()->onDelete('cascade');
            $table->integer('draw_number');
            $table->dateTime('drawn_at')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->unique(['lottery_id', 'draw_number']);
        });

        Schema::create('lottery_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_draw_id')->constrained()->onDelete('cascade');
            $table->foreignId('lottery_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_claimed')->default(false);
            $table->dateTime('claimed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_winners');
        Schema::dropIfExists('lottery_draws');
        Schema::dropIfExists('lottery_entries');
        Schema::dropIfExists('lotteries');
        Schema::dropIfExists('affiliate_referrals');
        Schema::dropIfExists('affiliate_clicks');
    }
};

