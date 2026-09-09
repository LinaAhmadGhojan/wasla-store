<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_batches', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->string('title');
            $table->string('status', 30)->default('open');
            $table->decimal('total_aed', 12, 2)->nullable();
            $table->string('external_order_ref')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_batches');
    }
};
