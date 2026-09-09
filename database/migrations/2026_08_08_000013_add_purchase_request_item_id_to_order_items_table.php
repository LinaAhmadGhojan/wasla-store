<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: product_id remains NOT NULL for now (changing column nullability
        // requires doctrine/dbal, which isn't available in this environment).
        // Forward-compatible plan: when full mixed-cart/order support lands,
        // add a follow-up migration (with doctrine/dbal installed) to make
        // product_id nullable so external-only order items can omit it.
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('purchase_request_item_id')->nullable()->after('product_variant_id')
                ->constrained('purchase_request_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_request_item_id']);
            $table->dropColumn('purchase_request_item_id');
        });
    }
};
