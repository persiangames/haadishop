<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained()->onDelete('set null');
            $table->string('slug')->unique();
            $table->string('sku')->unique()->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_published')->default(false);
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('width', 10, 3)->nullable();
            $table->decimal('height', 10, 3)->nullable();
            $table->decimal('length', 10, 3)->nullable();
            $table->foreignId('tax_class_id')->nullable()->constrained()->onDelete('set null');
            $table->smallInteger('warranty_months')->nullable();
            $table->timestamps();
        });

        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('locale', 10);
            $table->string('title', 255);
            $table->text('short_desc')->nullable();
            $table->longText('long_desc')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_desc', 255)->nullable();
            $table->unique(['product_id', 'locale']);
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->primary(['product_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('product_translations');
        Schema::dropIfExists('products');
    }
};

