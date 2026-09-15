<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('express_categories')) {
            Schema::create('express_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('icon')->nullable();
                $table->string('image')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        }

        if (! Schema::hasTable('express_stores')) {
            Schema::create('express_stores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('express_category_id')->nullable()->constrained('express_categories')->nullOnDelete();
                $table->string('store_name');
                $table->string('slug')->unique();
                $table->string('cuisine')->nullable();
                $table->text('description')->nullable();
                $table->string('logo')->nullable();
                $table->string('banner')->nullable();
                $table->string('area')->nullable();
                $table->string('street_address')->nullable();
                $table->unsignedInteger('delivery_fee_syp')->default(0);
                $table->unsignedSmallInteger('eta_min_minutes')->default(20);
                $table->unsignedSmallInteger('eta_max_minutes')->default(35);
                $table->decimal('rating', 3, 2)->default(0);
                $table->unsignedInteger('total_reviews')->default(0);
                $table->decimal('commission_rate', 5, 2)->default(15);
                $table->enum('status', ['pending', 'active', 'suspended', 'closed'])->default('pending');
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_verified')->default(false);
                $table->boolean('is_open')->default(true);
                $table->timestamps();

                $table->index(['status', 'is_open']);
                $table->index(['express_category_id', 'status']);
                $table->index('rating');
            });
        }

        if (! Schema::hasTable('express_menu_items')) {
            Schema::create('express_menu_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('express_store_id')->constrained('express_stores')->cascadeOnDelete();
                $table->foreignId('express_category_id')->nullable()->constrained('express_categories')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->unsignedInteger('price_syp');
                $table->unsignedInteger('sale_price_syp')->nullable();
                $table->unsignedSmallInteger('eta_min_minutes')->nullable();
                $table->boolean('is_offer')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->decimal('rating', 3, 2)->default(0);
                $table->unsignedInteger('total_reviews')->default(0);
                $table->timestamps();

                $table->index(['express_store_id', 'is_active']);
                $table->index(['express_category_id', 'is_active']);
                $table->index(['is_offer', 'is_active']);
                $table->index('price_syp');
                $table->index('eta_min_minutes');
            });
        }

        if (! Schema::hasColumn('users', 'preferred_channel')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('preferred_channel', 20)->default('both')->after('preferred_currency');
                $table->index('preferred_channel');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'preferred_channel')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['preferred_channel']);
                $table->dropColumn('preferred_channel');
            });
        }

        Schema::dropIfExists('express_menu_items');
        Schema::dropIfExists('express_stores');
        Schema::dropIfExists('express_categories');
    }
};
