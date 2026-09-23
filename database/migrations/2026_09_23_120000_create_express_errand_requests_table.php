<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('express_errand_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference', 32)->unique();
            $table->string('category', 32)->default('grocery');
            $table->json('items');
            $table->string('store_preference', 32)->default('any');
            $table->string('store_name')->nullable();
            $table->text('delivery_address');
            $table->string('contact_phone', 32);
            $table->text('customer_note')->nullable();
            $table->unsignedInteger('budget_syp')->nullable();
            $table->string('urgency', 16)->default('normal');
            $table->string('status', 24)->default('pending');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('express_errand_requests');
    }
};
