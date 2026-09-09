<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->decimal('estimated_price_syp', 14, 0)->nullable()->after('estimated_price');
            $table->decimal('final_price_syp', 14, 0)->nullable()->after('final_price');
            $table->decimal('exchange_rate', 14, 2)->nullable()->after('final_price_syp');
            $table->timestamp('quoted_at')->nullable()->after('exchange_rate');
        });

        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->decimal('final_price_syp', 14, 0)->nullable()->after('final_price');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropColumn('final_price_syp');
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['estimated_price_syp', 'final_price_syp', 'exchange_rate', 'quoted_at']);
        });
    }
};
