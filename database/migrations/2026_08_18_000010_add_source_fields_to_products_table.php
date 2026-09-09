<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('source_platform')->nullable()->after('image');
            $table->string('source_external_id')->nullable()->after('source_platform');
            $table->text('source_url')->nullable()->after('source_external_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['source_platform', 'source_external_id', 'source_url']);
        });
    }
};
