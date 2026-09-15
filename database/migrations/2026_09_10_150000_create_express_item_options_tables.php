<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('express_menu_items', function (Blueprint $table) {
            if (! Schema::hasColumn('express_menu_items', 'unit_label')) {
                $table->string('unit_label', 50)->nullable()->after('description');
            }
            if (! Schema::hasColumn('express_menu_items', 'serving_note')) {
                $table->string('serving_note', 500)->nullable()->after('unit_label');
            }
            if (! Schema::hasColumn('express_menu_items', 'ingredients')) {
                $table->text('ingredients')->nullable()->after('serving_note');
            }
        });

        if (! Schema::hasTable('express_menu_item_variants')) {
            Schema::create('express_menu_item_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('express_menu_item_id')->constrained('express_menu_items')->cascadeOnDelete();
                $table->string('label');
                $table->string('unit_label', 50)->nullable();
                $table->unsignedInteger('price_syp');
                $table->unsignedInteger('sale_price_syp')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['express_menu_item_id', 'is_active', 'sort_order'], 'ex_item_var_idx');
            });
        }

        if (! Schema::hasTable('express_menu_item_extras')) {
            Schema::create('express_menu_item_extras', function (Blueprint $table) {
                $table->id();
                $table->foreignId('express_menu_item_id')->constrained('express_menu_items')->cascadeOnDelete();
                $table->string('group_name');
                $table->string('label');
                $table->integer('price_delta_syp')->default(0);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['express_menu_item_id', 'group_name', 'is_active'], 'ex_item_extra_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('express_menu_item_extras');
        Schema::dropIfExists('express_menu_item_variants');

        Schema::table('express_menu_items', function (Blueprint $table) {
            foreach (['ingredients', 'serving_note', 'unit_label'] as $col) {
                if (Schema::hasColumn('express_menu_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
