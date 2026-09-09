<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        // Make product_id nullable for external (SHEIN/Temu…) lines inside the same order
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE order_items MODIFY product_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE order_items ALTER COLUMN product_id DROP NOT NULL');
        } else {
            // sqlite / others: rebuild not needed if already flexible; try change
            Schema::table('order_items', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->nullable()->change();
            });
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->string('source_type')->default('local')->after('order_id'); // local|external
            $table->foreignId('platform_id')->nullable()->after('source_type')->constrained('external_platforms')->nullOnDelete();
            $table->string('product_name')->nullable()->after('product_variant_id');
            $table->string('external_url', 2048)->nullable()->after('product_name');
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('customer_id')->constrained('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('platform_id');
            $table->dropColumn(['source_type', 'product_name', 'external_url']);
            $table->dropForeign(['product_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE order_items MODIFY product_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }
};
