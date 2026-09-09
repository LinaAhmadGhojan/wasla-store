<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->string('type')->default('marketplace'); // marketplace, fashion, general...
            $table->string('status')->default('active'); // active, inactive
            $table->string('currency', 10)->default('USD');
            $table->string('country', 10)->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0.00);
            $table->decimal('markup_rate', 5, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_platforms');
    }
};
