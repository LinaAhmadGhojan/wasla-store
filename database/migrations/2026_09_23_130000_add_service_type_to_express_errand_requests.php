<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('express_errand_requests', function (Blueprint $table) {
            $table->string('service_type', 32)->default('local_errand')->after('reference');
            $table->string('origin_governorate', 80)->nullable()->after('store_name');
            $table->text('origin_details')->nullable()->after('origin_governorate');
            $table->string('destination_governorate', 80)->nullable()->after('origin_details');
            $table->text('parcel_description')->nullable()->after('destination_governorate');
            $table->string('parcel_size', 24)->nullable()->after('parcel_description');
        });
    }

    public function down(): void
    {
        Schema::table('express_errand_requests', function (Blueprint $table) {
            $table->dropColumn([
                'service_type',
                'origin_governorate',
                'origin_details',
                'destination_governorate',
                'parcel_description',
                'parcel_size',
            ]);
        });
    }
};
