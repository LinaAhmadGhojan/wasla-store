<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('allow_return')->default(true)->after('is_active');
            $table->boolean('allow_exchange')->default(true)->after('allow_return');
            $table->unsignedSmallInteger('return_days')->nullable()->after('allow_exchange');
            $table->unsignedSmallInteger('exchange_days')->nullable()->after('return_days');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['allow_return', 'allow_exchange', 'return_days', 'exchange_days']);
        });
    }
};
