<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->string('product_name');
            $table->foreignId('external_product_id')->nullable()->constrained('external_products')->nullOnDelete();
            $table->json('variant_data')->nullable(); // free-text color/size/etc since import isn't structured
            $table->integer('quantity')->default(1);
            $table->decimal('source_price', 10, 2)->nullable();
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('final_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
