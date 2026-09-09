<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained('external_platforms')->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->text('external_url');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('status')->default('pending'); // pending, fetched, unavailable
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_products');
    }
};
