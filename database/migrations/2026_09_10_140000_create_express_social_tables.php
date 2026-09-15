<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('express_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('express_menu_item_id')->constrained('express_menu_items')->cascadeOnDelete();
            $table->foreignId('express_store_id')->nullable()->constrained('express_stores')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('reward_syp')->default(0);
            $table->string('status', 20)->default('approved');
            $table->timestamps();

            $table->unique(['user_id', 'express_menu_item_id']);
            $table->index(['express_menu_item_id', 'status', 'created_at']);
            $table->index(['express_store_id', 'status']);
        });

        Schema::create('express_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('express_menu_item_id')->constrained('express_menu_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['express_menu_item_id', 'is_public']);
        });

        Schema::create('express_store_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('express_store_id')->constrained('express_stores')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'express_store_id']);
        });

        Schema::create('express_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('express_menu_item_id')->constrained('express_menu_items')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'express_menu_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('express_favorites');
        Schema::dropIfExists('express_store_follows');
        Schema::dropIfExists('express_questions');
        Schema::dropIfExists('express_reviews');
    }
};
