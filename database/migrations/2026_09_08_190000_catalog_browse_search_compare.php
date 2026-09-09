<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'view_count')) {
                $table->unsignedInteger('view_count')->default(0)->after('total_reviews');
            }
            if (! Schema::hasColumn('products', 'sales_count')) {
                $table->unsignedInteger('sales_count')->default(0)->after('view_count');
            }
            if (! Schema::hasColumn('products', 'keywords')) {
                $table->text('keywords')->nullable()->after('description');
            }
            if (! Schema::hasColumn('products', 'is_flash_sale')) {
                $table->boolean('is_flash_sale')->default(false)->after('is_featured');
            }
            if (! Schema::hasColumn('products', 'flash_ends_at')) {
                $table->timestamp('flash_ends_at')->nullable()->after('is_flash_sale');
            }
            if (! Schema::hasColumn('products', 'gender')) {
                $table->string('gender', 30)->nullable()->after('flash_ends_at');
            }
            if (! Schema::hasColumn('products', 'fast_delivery')) {
                $table->boolean('fast_delivery')->default(false)->after('gender');
            }
        });

        if (! Schema::hasTable('collections')) {
            Schema::create('collections', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('collection_product')) {
            Schema::create('collection_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['collection_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('search_queries')) {
            Schema::create('search_queries', function (Blueprint $table) {
                $table->id();
                $table->string('query', 190);
                $table->unsignedInteger('hits')->default(1);
                $table->timestamp('last_searched_at')->nullable();
                $table->timestamps();
                $table->unique('query');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');

        Schema::table('products', function (Blueprint $table) {
            foreach (['view_count', 'sales_count', 'keywords', 'is_flash_sale', 'flash_ends_at', 'gender', 'fast_delivery'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
