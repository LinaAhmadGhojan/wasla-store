<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wishlist_lists')) {
            Schema::create('wishlist_lists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug', 80)->nullable();
                $table->boolean('is_default')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['user_id', 'is_default']);
            });
        }

        if (Schema::hasTable('wishlists') && ! Schema::hasColumn('wishlists', 'list_id')) {
            Schema::table('wishlists', function (Blueprint $table) {
                $table->foreignId('list_id')->nullable()->after('user_id')->constrained('wishlist_lists')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('wishlists') && Schema::hasColumn('wishlists', 'list_id')) {
            $userIds = DB::table('wishlists')->whereNull('list_id')->distinct()->pluck('user_id');
            foreach ($userIds as $userId) {
                $listId = DB::table('wishlist_lists')->where('user_id', $userId)->where('is_default', 1)->value('id');
                if (! $listId) {
                    $listId = DB::table('wishlist_lists')->insertGetId([
                        'user_id' => $userId,
                        'name' => 'مفضلتي',
                        'slug' => 'default',
                        'is_default' => true,
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                DB::table('wishlists')->where('user_id', $userId)->whereNull('list_id')->update(['list_id' => $listId]);
            }

            // Also create default lists for any users who already have list_id from prior partial run
            $usersNeedingDefault = DB::table('wishlists')->distinct()->pluck('user_id');
            foreach ($usersNeedingDefault as $userId) {
                $exists = DB::table('wishlist_lists')->where('user_id', $userId)->where('is_default', 1)->exists();
                if (! $exists) {
                    DB::table('wishlist_lists')->insert([
                        'user_id' => $userId,
                        'name' => 'مفضلتي',
                        'slug' => 'default',
                        'is_default' => true,
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $indexes = collect(DB::select('SHOW INDEX FROM wishlists'));
            $hasOldUnique = $indexes->contains(fn ($i) => $i->Key_name === 'wishlists_user_id_product_id_unique');
            $hasNewUnique = $indexes->contains(fn ($i) => $i->Key_name === 'wishlists_list_id_product_id_unique');

            if ($hasOldUnique && ! $hasNewUnique) {
                $this->dropFkIfExists('wishlists', 'wishlists_product_id_foreign');
                $this->dropFkIfExists('wishlists', 'wishlists_user_id_foreign');
                $this->dropFkIfExists('wishlists', 'wishlists_list_id_foreign');
                DB::statement('ALTER TABLE wishlists DROP INDEX wishlists_user_id_product_id_unique');
                DB::statement('ALTER TABLE wishlists ADD UNIQUE wishlists_list_id_product_id_unique (list_id, product_id)');
                DB::statement('ALTER TABLE wishlists ADD CONSTRAINT wishlists_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
                DB::statement('ALTER TABLE wishlists ADD CONSTRAINT wishlists_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE');
                DB::statement('ALTER TABLE wishlists ADD CONSTRAINT wishlists_list_id_foreign FOREIGN KEY (list_id) REFERENCES wishlist_lists(id) ON DELETE CASCADE');
            }
        }

        if (! Schema::hasTable('store_follows')) {
            Schema::create('store_follows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('stores')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'vendor_id']);
            });
        }

        if (! Schema::hasTable('product_questions')) {
            Schema::create('product_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('question');
                $table->text('answer')->nullable();
                $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('answered_at')->nullable();
                $table->boolean('is_public')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('products', 'video_url')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('video_url')->nullable()->after('image');
            });
        }
    }

    private function dropFkIfExists(string $table, string $fk): void
    {
        try {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$fk}");
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_questions');
        Schema::dropIfExists('store_follows');
    }
};
