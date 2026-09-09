<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_groups', function (Blueprint $table) {
            $table->string('whatsapp_chat_id', 80)
                ->nullable()
                ->after('invite_link')
                ->comment('WhatsApp group id e.g. 120363xxx@g.us — required for auto-send to group');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_groups', function (Blueprint $table) {
            $table->dropColumn('whatsapp_chat_id');
        });
    }
};
