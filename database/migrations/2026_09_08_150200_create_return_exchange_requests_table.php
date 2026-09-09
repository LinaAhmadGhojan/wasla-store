<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_exchange_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // return | exchange
            $table->string('status', 20)->default('pending'); // pending | approved | rejected | completed
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['order_item_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_exchange_requests');
    }
};
