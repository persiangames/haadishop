<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->decimal('rate', 18, 8);
            $table->dateTime('fetched_at');
            $table->string('provider', 64);
            $table->unique(['base_currency', 'quote_currency', 'fetched_at'], 'ux_pair_time');
            $table->foreign('base_currency')->references('code')->on('currencies');
            $table->foreign('quote_currency')->references('code')->on('currencies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};

