<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('variants');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            $table->timestamps();
        });

        Schema::create('ab_test_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id', 100)->nullable();
            $table->string('variant');
            $table->dateTime('assigned_at');
            $table->index(['ab_test_id', 'user_id']);
            $table->index(['ab_test_id', 'session_id']);
        });

        Schema::create('ab_test_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id', 100)->nullable();
            $table->string('event_key');
            $table->decimal('value', 18, 2)->default(1);
            $table->dateTime('occurred_at');
            $table->index(['ab_test_id', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_test_metrics');
        Schema::dropIfExists('ab_test_assignments');
        Schema::dropIfExists('ab_tests');
    }
};

