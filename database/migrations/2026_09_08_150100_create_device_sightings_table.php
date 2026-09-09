<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_sightings', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_name')->nullable();
            $table->string('platform', 20)->nullable();
            $table->string('app', 40)->default('customer');
            $table->dateTime('first_seen_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['token_hash', 'user_id']);
            $table->index('token_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_sightings');
    }
};
