<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('express_errand_requests', function (Blueprint $table) {
            $table->unsignedInteger('quote_total_syp')->nullable()->after('budget_syp');
            $table->json('quote_breakdown')->nullable()->after('quote_total_syp');
            $table->string('shipping_carrier', 32)->nullable()->after('quote_breakdown');
            $table->timestamp('quote_confirmed_at')->nullable()->after('shipping_carrier');
        });
    }

    public function down(): void
    {
        Schema::table('express_errand_requests', function (Blueprint $table) {
            $table->dropColumn([
                'quote_total_syp',
                'quote_breakdown',
                'shipping_carrier',
                'quote_confirmed_at',
            ]);
        });
    }
};
