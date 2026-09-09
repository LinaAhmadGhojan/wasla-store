<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('whatsapp_published_at')->nullable()->after('is_active');
            $table->string('whatsapp_published_target', 20)->nullable()->after('whatsapp_published_at')
                ->comment('phone or group');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_published_at', 'whatsapp_published_target']);
        });
    }
};
