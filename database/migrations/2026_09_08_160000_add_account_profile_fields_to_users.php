<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 10)->default('ar')->after('avatar');
            }
            if (! Schema::hasColumn('users', 'preferred_currency')) {
                $table->string('preferred_currency', 10)->default('SYP')->after('locale');
            }
            if (! Schema::hasColumn('users', 'privacy_settings')) {
                $table->json('privacy_settings')->nullable()->after('preferred_currency');
            }
            if (! Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('privacy_settings');
            }
            if (! Schema::hasColumn('users', 'apple_id')) {
                $table->string('apple_id')->nullable()->unique()->after('google_id');
            }
            if (! Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone');
            }
        });

        Schema::create('auth_otps', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20); // phone | email
            $table->string('destination');
            $table->string('purpose', 40); // login | verify_phone | verify_email | reset
            $table->string('code', 10);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['destination', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_otps');
        Schema::table('users', function (Blueprint $table) {
            foreach (['locale', 'preferred_currency', 'privacy_settings', 'google_id', 'apple_id', 'phone_verified_at'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
