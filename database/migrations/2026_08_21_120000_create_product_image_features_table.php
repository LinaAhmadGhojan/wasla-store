<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_image_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_path', 500);
            $table->string('dhash', 16); // 64-bit hex
            $table->json('color_hist'); // normalized histogram buckets
            $table->timestamps();

            $table->unique(['product_id', 'image_path']);
            $table->index('dhash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_image_features');
    }
};
