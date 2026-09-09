<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->decimal('product_fee_aed', 10, 2)->default(75)->after('rate');
            $table->decimal('accessory_fee_aed', 10, 2)->default(50)->after('product_fee_aed');
        });
    }

    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropColumn(['product_fee_aed', 'accessory_fee_aed']);
        });
    }
};
