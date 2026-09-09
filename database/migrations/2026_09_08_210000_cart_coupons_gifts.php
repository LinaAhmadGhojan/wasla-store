<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            if (! Schema::hasColumn('cart_items', 'saved_for_later')) {
                $table->boolean('saved_for_later')->default(false)->after('quantity');
            }
            if (! Schema::hasColumn('cart_items', 'price_snapshot')) {
                $table->decimal('price_snapshot', 10, 2)->nullable()->after('saved_for_later');
            }
        });

        if (! Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name')->nullable();
                $table->string('type', 20)->default('percent'); // percent|fixed
                $table->decimal('value', 10, 2);
                $table->decimal('min_subtotal', 10, 2)->nullable();
                $table->decimal('max_discount', 10, 2)->nullable();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('coupon_redemptions')) {
            Schema::create('coupon_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'customer_notes')) {
                $table->text('customer_notes')->nullable()->after('shipping_method');
            }
            if (! Schema::hasColumn('orders', 'is_gift')) {
                $table->boolean('is_gift')->default(false)->after('customer_notes');
            }
            if (! Schema::hasColumn('orders', 'gift_wrapping')) {
                $table->boolean('gift_wrapping')->default(false)->after('is_gift');
            }
            if (! Schema::hasColumn('orders', 'gift_message')) {
                $table->text('gift_message')->nullable()->after('gift_wrapping');
            }
            if (! Schema::hasColumn('orders', 'gift_recipient_name')) {
                $table->string('gift_recipient_name')->nullable()->after('gift_message');
            }
            if (! Schema::hasColumn('orders', 'gift_recipient_phone')) {
                $table->string('gift_recipient_phone', 50)->nullable()->after('gift_recipient_name');
            }
            if (! Schema::hasColumn('orders', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->after('gift_recipient_phone')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code', 60)->nullable()->after('coupon_id');
            }
            if (! Schema::hasColumn('orders', 'wallet_amount')) {
                $table->decimal('wallet_amount', 10, 2)->default(0)->after('discount_amount');
            }
            if (! Schema::hasColumn('orders', 'points_used')) {
                $table->unsignedInteger('points_used')->default(0)->after('wallet_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['customer_notes', 'is_gift', 'gift_wrapping', 'gift_message', 'gift_recipient_name', 'gift_recipient_phone', 'coupon_code', 'wallet_amount', 'points_used'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('orders', 'coupon_id')) {
                $table->dropConstrainedForeignId('coupon_id');
            }
        });
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
        Schema::table('cart_items', function (Blueprint $table) {
            if (Schema::hasColumn('cart_items', 'saved_for_later')) {
                $table->dropColumn('saved_for_later');
            }
            if (Schema::hasColumn('cart_items', 'price_snapshot')) {
                $table->dropColumn('price_snapshot');
            }
        });
    }
};
