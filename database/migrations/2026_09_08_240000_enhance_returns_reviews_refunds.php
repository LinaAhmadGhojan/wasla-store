<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_exchange_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('return_exchange_requests', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('type');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'images')) {
                $table->json('images')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'pickup_at')) {
                $table->timestamp('pickup_at')->nullable()->after('images');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'pickup_address')) {
                $table->text('pickup_address')->nullable()->after('pickup_at');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'exchange_variant_id')) {
                $table->foreignId('exchange_variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            }
            if (! Schema::hasColumn('return_exchange_requests', 'exchange_size_label')) {
                $table->string('exchange_size_label', 120)->nullable()->after('exchange_variant_id');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'from_size_label')) {
                $table->string('from_size_label', 120)->nullable()->after('exchange_size_label');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'track_status')) {
                $table->string('track_status', 40)->default('requested')->after('status');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'refund_status')) {
                $table->string('refund_status', 40)->nullable()->after('track_status');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'refund_amount')) {
                $table->decimal('refund_amount', 10, 2)->nullable()->after('refund_status');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'refund_method')) {
                $table->string('refund_method', 60)->nullable()->after('refund_amount');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'refund_requested_at')) {
                $table->timestamp('refund_requested_at')->nullable()->after('refund_method');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'refund_approved_at')) {
                $table->timestamp('refund_approved_at')->nullable()->after('refund_requested_at');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'refund_processing_at')) {
                $table->timestamp('refund_processing_at')->nullable()->after('refund_approved_at');
            }
            if (! Schema::hasColumn('return_exchange_requests', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('refund_processing_at');
            }
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('product_reviews', 'fit_feedback')) {
                $table->string('fit_feedback', 20)->nullable()->after('body');
            }
            if (! Schema::hasColumn('product_reviews', 'video_path')) {
                $table->string('video_path')->nullable()->after('image_path');
            }
            if (! Schema::hasColumn('product_reviews', 'image_paths')) {
                $table->json('image_paths')->nullable()->after('video_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('return_exchange_requests', function (Blueprint $table) {
            foreach ([
                'quantity', 'images', 'pickup_at', 'pickup_address', 'exchange_size_label', 'from_size_label',
                'track_status', 'refund_status', 'refund_amount', 'refund_method',
                'refund_requested_at', 'refund_approved_at', 'refund_processing_at', 'refunded_at',
            ] as $col) {
                if (Schema::hasColumn('return_exchange_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('return_exchange_requests', 'exchange_variant_id')) {
                $table->dropConstrainedForeignId('exchange_variant_id');
            }
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            foreach (['fit_feedback', 'video_path', 'image_paths'] as $col) {
                if (Schema::hasColumn('product_reviews', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
