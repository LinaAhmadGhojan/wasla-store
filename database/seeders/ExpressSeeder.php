<?php

namespace Database\Seeders;

use App\Models\ExpressCategory;
use App\Models\ExpressMenuItem;
use App\Models\ExpressMenuItemExtra;
use App\Models\ExpressMenuItemVariant;
use App\Models\ExpressStore;
use App\Services\Express\ExpressCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExpressSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'مطاعم', 'slug' => 'restaurants', 'sort_order' => 1],
            ['name' => 'مخابز', 'slug' => 'bakery', 'sort_order' => 2],
            ['name' => 'مشروبات', 'slug' => 'drinks', 'sort_order' => 3],
            ['name' => 'بقالة', 'slug' => 'grocery', 'sort_order' => 4],
        ];

        $catIds = [];
        foreach ($categories as $c) {
            $cat = ExpressCategory::query()->updateOrCreate(
                ['slug' => $c['slug']],
                array_merge($c, ['is_active' => true])
            );
            $catIds[$c['slug']] = $cat->id;
        }

        $stores = [
            [
                'store_name' => 'مطعم الشام الأصيل',
                'slug' => 'al-sham',
                'cuisine' => 'شامي',
                'category' => 'restaurants',
                'banner' => 'images/express/store-sham.jpg',
                'eta_min_minutes' => 25,
                'eta_max_minutes' => 35,
                'rating' => 4.8,
            ],
            [
                'store_name' => 'مخبز الندى',
                'slug' => 'al-nada-bakery',
                'cuisine' => 'مخبوزات',
                'category' => 'bakery',
                'banner' => 'images/express/store-bakery.jpg',
                'eta_min_minutes' => 15,
                'eta_max_minutes' => 25,
                'rating' => 4.7,
            ],
            [
                'store_name' => 'سوبر ماركت الحي',
                'slug' => 'neighborhood-market',
                'cuisine' => 'بقالة',
                'category' => 'grocery',
                'banner' => 'images/express/store-market.jpg',
                'eta_min_minutes' => 20,
                'eta_max_minutes' => 30,
                'rating' => 4.6,
            ],
            [
                'store_name' => 'عصائر فريش',
                'slug' => 'fresh-juice',
                'cuisine' => 'مشروبات',
                'category' => 'drinks',
                'banner' => 'images/express/store-juice.jpg',
                'eta_min_minutes' => 15,
                'eta_max_minutes' => 20,
                'rating' => 4.9,
            ],
            [
                'store_name' => 'بيتزا روما',
                'slug' => 'pizza-roma',
                'cuisine' => 'إيطالي',
                'category' => 'restaurants',
                'banner' => 'images/express/store-pizza.jpg',
                'eta_min_minutes' => 30,
                'eta_max_minutes' => 40,
                'rating' => 4.5,
            ],
        ];

        $storeIds = [];
        foreach ($stores as $s) {
            $store = ExpressStore::query()->updateOrCreate(
                ['slug' => $s['slug']],
                [
                    'express_category_id' => $catIds[$s['category']],
                    'store_name' => $s['store_name'],
                    'cuisine' => $s['cuisine'],
                    'banner' => $s['banner'],
                    'logo' => $s['banner'],
                    'eta_min_minutes' => $s['eta_min_minutes'],
                    'eta_max_minutes' => $s['eta_max_minutes'],
                    'rating' => $s['rating'],
                    'total_reviews' => 40,
                    'delivery_fee_syp' => 5000,
                    'commission_rate' => 15,
                    'status' => 'active',
                    'is_featured' => true,
                    'is_verified' => true,
                    'is_open' => true,
                    'area' => 'دمشق',
                ]
            );
            $storeIds[$s['slug']] = $store->id;
        }

        $items = [
            [
                'name' => 'شاورما دجاج', 'store' => 'al-sham', 'category' => 'restaurants', 'price' => 52000, 'sale' => 45000, 'eta' => 30, 'offer' => true, 'image' => 'images/express/shawarma.jpg', 'rating' => 4.8,
                'unit' => 'ساندويش',
                'description' => 'شاورما دجاج طازجة مع ثوم ومخلل.',
                'serving' => 'ثوم + مخلل + بطاطا',
                'ingredients' => 'دجاج، خبز عربي، ثوم، مخلل',
                'variants' => [
                    ['label' => 'ساندويش عادي', 'unit' => 'ساندويش', 'price' => 45000, 'sale' => null, 'default' => true],
                    ['label' => 'وجبة كبيرة', 'unit' => 'وجبة', 'price' => 65000, 'sale' => 58000, 'default' => false],
                ],
                'extras' => [
                    ['group' => 'التوابل', 'label' => 'عادي', 'delta' => 0, 'default' => true],
                    ['group' => 'التوابل', 'label' => 'حار', 'delta' => 0, 'default' => false],
                    ['group' => 'إضافات', 'label' => 'جبنة', 'delta' => 5000, 'default' => false],
                ],
            ],
            [
                'name' => 'دجاج مشوي', 'store' => 'al-sham', 'category' => 'restaurants', 'price' => 68000, 'sale' => null, 'eta' => 35, 'offer' => false, 'image' => 'images/express/grill.jpg', 'rating' => 4.7,
                'unit' => 'نصف فروج',
                'description' => 'دجاج مشوي على الفحم بتتبيلة شامية.',
                'serving' => 'ثوم + بطاطا + سلطة',
                'ingredients' => 'دجاج كامل، بهارات شامية، زيت زيتون',
                'variants' => [
                    ['label' => 'نصف فروج', 'unit' => 'نصف', 'price' => 68000, 'sale' => null, 'default' => true],
                    ['label' => 'فروج كامل', 'unit' => 'كامل', 'price' => 125000, 'sale' => null, 'default' => false],
                    ['label' => 'كيلو مشوي', 'unit' => 'كيلو', 'price' => 95000, 'sale' => null, 'default' => false],
                ],
                'extras' => [
                    ['group' => 'التوابل', 'label' => 'عادي', 'delta' => 0, 'default' => true],
                    ['group' => 'التوابل', 'label' => 'حار', 'delta' => 0, 'default' => false],
                    ['group' => 'إضافات', 'label' => 'ثوم إضافي', 'delta' => 3000, 'default' => false],
                    ['group' => 'إضافات', 'label' => 'بطاطا إضافية', 'delta' => 7000, 'default' => false],
                ],
            ],
            [
                'name' => 'بيتزا مارغريتا', 'store' => 'pizza-roma', 'category' => 'restaurants', 'price' => 62000, 'sale' => 55000, 'eta' => 35, 'offer' => true, 'image' => 'images/express/pizza.jpg', 'rating' => 4.5,
                'unit' => 'وسط',
                'description' => 'بيتزا إيطالية بجبنة موزاريلا وصلصة طماطم.',
                'serving' => 'قطع جاهزة للتقديم',
                'ingredients' => 'عجينة، طماطم، موزاريلا، ريحان',
                'variants' => [
                    ['label' => 'صغير', 'unit' => 'صغير', 'price' => 42000, 'sale' => null, 'default' => false],
                    ['label' => 'وسط', 'unit' => 'وسط', 'price' => 55000, 'sale' => null, 'default' => true],
                    ['label' => 'كبير', 'unit' => 'كبير', 'price' => 78000, 'sale' => 70000, 'default' => false],
                ],
                'extras' => [
                    ['group' => 'الحواف', 'label' => 'عادي', 'delta' => 0, 'default' => true],
                    ['group' => 'الحواف', 'label' => 'جبنة', 'delta' => 8000, 'default' => false],
                ],
            ],
            [
                'name' => 'مناقيش زعتر', 'store' => 'al-nada-bakery', 'category' => 'bakery', 'price' => 12000, 'sale' => null, 'eta' => 20, 'offer' => false, 'image' => 'images/express/manakish.jpg', 'rating' => 4.7,
                'unit' => 'حبة',
                'description' => 'مناقيش زعتر طازجة من الفرن.',
                'serving' => 'حارّة من الفرن',
                'ingredients' => 'عجينة، زعتر، زيت زيتون',
                'variants' => [
                    ['label' => 'حبة', 'unit' => 'حبة', 'price' => 12000, 'sale' => null, 'default' => true],
                    ['label' => 'نصف درزن', 'unit' => '6 حبات', 'price' => 65000, 'sale' => null, 'default' => false],
                ],
                'extras' => [],
            ],
            [
                'name' => 'كنافة', 'store' => 'al-nada-bakery', 'category' => 'bakery', 'price' => 32000, 'sale' => 28000, 'eta' => 25, 'offer' => true, 'image' => 'images/express/kunafa.jpg', 'rating' => 4.8,
                'unit' => 'حصة',
                'description' => 'كنافة نابلسية بالجبنة والقطر.',
                'serving' => 'مع قطر جانب',
                'ingredients' => 'كناه، جبنة، قطر، فستق',
                'variants' => [
                    ['label' => 'حصة فردية', 'unit' => 'حصة', 'price' => 28000, 'sale' => null, 'default' => true],
                    ['label' => 'صينية صغيرة', 'unit' => 'صينية', 'price' => 95000, 'sale' => null, 'default' => false],
                ],
                'extras' => [],
            ],
            [
                'name' => 'عصير ليمون ونعنع', 'store' => 'fresh-juice', 'category' => 'drinks', 'price' => 10000, 'sale' => null, 'eta' => 18, 'offer' => false, 'image' => 'images/express/juice.jpg', 'rating' => 4.9,
                'unit' => 'كأس',
                'description' => 'عصير ليمون طازج مع نعنع وثلج.',
                'serving' => 'بارد فوراً',
                'ingredients' => 'ليمون، نعنع، سكر، ثلج',
                'variants' => [
                    ['label' => 'كأس', 'unit' => 'كأس', 'price' => 10000, 'sale' => null, 'default' => true],
                    ['label' => 'ليتر', 'unit' => 'ليتر', 'price' => 28000, 'sale' => null, 'default' => false],
                ],
                'extras' => [
                    ['group' => 'السكر', 'label' => 'عادي', 'delta' => 0, 'default' => true],
                    ['group' => 'السكر', 'label' => 'بدون سكر', 'delta' => 0, 'default' => false],
                ],
            ],
        ];

        foreach ($items as $i => $item) {
            $slug = Str::slug($item['name']).'-'.$item['store'];
            $row = ExpressMenuItem::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'express_store_id' => $storeIds[$item['store']],
                    'express_category_id' => $catIds[$item['category']],
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'unit_label' => $item['unit'],
                    'serving_note' => $item['serving'],
                    'ingredients' => $item['ingredients'],
                    'image' => $item['image'],
                    'price_syp' => $item['price'],
                    'sale_price_syp' => $item['sale'],
                    'eta_min_minutes' => $item['eta'],
                    'is_offer' => $item['offer'],
                    'is_featured' => $i < 3,
                    'is_active' => true,
                    'sort_order' => $i,
                    'rating' => $item['rating'],
                    'total_reviews' => 20,
                ]
            );

            $row->variants()->delete();
            $row->extras()->delete();

            foreach ($item['variants'] as $vi => $v) {
                ExpressMenuItemVariant::query()->create([
                    'express_menu_item_id' => $row->id,
                    'label' => $v['label'],
                    'unit_label' => $v['unit'],
                    'price_syp' => $v['price'],
                    'sale_price_syp' => $v['sale'],
                    'is_default' => $v['default'],
                    'is_active' => true,
                    'sort_order' => $vi,
                ]);
            }

            foreach ($item['extras'] as $ei => $e) {
                ExpressMenuItemExtra::query()->create([
                    'express_menu_item_id' => $row->id,
                    'group_name' => $e['group'],
                    'label' => $e['label'],
                    'price_delta_syp' => $e['delta'],
                    'is_default' => $e['default'],
                    'is_active' => true,
                    'sort_order' => $ei,
                ]);
            }
        }

        ExpressCache::bump();
    }
}
