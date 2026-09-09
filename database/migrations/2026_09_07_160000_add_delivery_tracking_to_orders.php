<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('delivery_otp', 10)->nullable()->after('tracking_number');
            $table->timestamp('delivery_otp_verified_at')->nullable()->after('delivery_otp');
            $table->unsignedTinyInteger('delivery_otp_attempts')->default(0)->after('delivery_otp_verified_at');
            $table->string('signature_path')->nullable()->after('delivery_otp_attempts');
            $table->string('proof_photo_path')->nullable()->after('signature_path');
            $table->string('failure_reason')->nullable()->after('proof_photo_path');
            $table->timestamp('out_for_delivery_at')->nullable()->after('delivered_at');
            $table->timestamp('failed_at')->nullable()->after('out_for_delivery_at');
            $table->timestamp('assigned_at')->nullable()->after('failed_at');
            $table->timestamp('picked_up_at')->nullable()->after('assigned_at');
        });

        Schema::create('delivery_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status');
            $table->string('title');
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_events');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driver_id');
            $table->dropColumn([
                'delivery_otp',
                'delivery_otp_verified_at',
                'delivery_otp_attempts',
                'signature_path',
                'proof_photo_path',
                'failure_reason',
                'out_for_delivery_at',
                'failed_at',
                'assigned_at',
                'picked_up_at',
            ]);
        });
    }
};
