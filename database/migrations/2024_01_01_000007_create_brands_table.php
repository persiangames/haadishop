<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('brand_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->string('locale', 10);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unique(['brand_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_translations');
        Schema::dropIfExists('brands');
    }
};

