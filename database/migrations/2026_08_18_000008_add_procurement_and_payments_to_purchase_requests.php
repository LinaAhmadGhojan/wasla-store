<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreignId('procurement_batch_id')->nullable()->after('platform_id')
                ->constrained('procurement_batches')->nullOnDelete();
            $table->timestamp('customer_approved_at')->nullable()->after('quoted_at');
        });

        Schema::create('purchase_request_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_syp', 14, 0);
            $table->string('method', 40);
            $table->string('status', 20)->default('submitted');
            $table->string('receipt_path')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_payments');

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('procurement_batch_id');
            $table->dropColumn('customer_approved_at');
        });
    }
};
