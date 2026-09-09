<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('addresses', 'label_type')) {
                $table->string('label_type', 20)->default('home')->after('label');
            }
            if (! Schema::hasColumn('addresses', 'courier_notes')) {
                $table->text('courier_notes')->nullable()->after('street_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (Schema::hasColumn('addresses', 'courier_notes')) {
                $table->dropColumn('courier_notes');
            }
            if (Schema::hasColumn('addresses', 'label_type')) {
                $table->dropColumn('label_type');
            }
        });
    }
};
