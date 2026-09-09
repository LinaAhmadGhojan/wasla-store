<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Hierarchical fashion / general-marketplace category tree.
     * Each node: name, slug, icon (top-level only), description, children (optional).
     */
    public static array $categories = [
        [
            'name' => 'نساء', 'slug' => 'women', 'icon' => '👗',
            'description' => 'أزياء وإكسسوارات نسائية',
            'children' => [
                [
                    'name' => 'ملابس نسائية', 'slug' => 'women-clothing',
                    'description' => 'ملابس نسائية متنوعة',
                    'children' => [
                        ['name' => 'فساتين', 'slug' => 'women-clothing-dresses', 'description' => 'فساتين كاجوال وسهرة'],
                        ['name' => 'بلوزات وتوبات', 'slug' => 'women-clothing-tops', 'description' => 'بلوزات وتوبات نسائية'],
                        ['name' => 'بناطيل نسائية', 'slug' => 'women-clothing-pants', 'description' => 'بناطيل نسائية'],
                        ['name' => 'جينز نسائي', 'slug' => 'women-clothing-jeans', 'description' => 'بناطيل جينز نسائية'],
                        ['name' => 'جاكيتات نسائية', 'slug' => 'women-clothing-jackets', 'description' => 'جاكيتات ومعاطف نسائية'],
                    ],
                ],
                [
                    'name' => 'إكسسوارات نسائية', 'slug' => 'women-accessories',
                    'description' => 'إكسسوارات نسائية متنوعة',
                    'children' => [
                        ['name' => 'مجوهرات', 'slug' => 'women-accessories-jewelry', 'description' => 'مجوهرات وإكسسوارات نسائية'],
                        ['name' => 'ساعات نسائية', 'slug' => 'women-accessories-watches', 'description' => 'ساعات يد نسائية'],
                        ['name' => 'أوشحة وشالات', 'slug' => 'women-accessories-scarves', 'description' => 'أوشحة وشالات نسائية'],
                    ],
                ],
            ],
        ],
        [
            'name' => 'رجال', 'slug' => 'men', 'icon' => '👔',
            'description' => 'أزياء وإكسسوارات رجالية',
            'children' => [
                [
                    'name' => 'ملابس رجالية', 'slug' => 'men-clothing',
                    'description' => 'ملابس رجالية متنوعة',
                    'children' => [
                        ['name' => 'قمصان', 'slug' => 'men-clothing-shirts', 'description' => 'قمصان رجالية رسمية وكاجوال'],
                        ['name' => 'تيشيرتات', 'slug' => 'men-clothing-tshirts', 'description' => 'تيشيرتات رجالية'],
                        ['name' => 'بناطيل رجالية', 'slug' => 'men-clothing-pants', 'description' => 'بناطيل رجالية'],
                        ['name' => 'جينز رجالي', 'slug' => 'men-clothing-jeans', 'description' => 'بناطيل جينز رجالية'],
                        ['name' => 'جاكيتات رجالية', 'slug' => 'men-clothing-jackets', 'description' => 'جاكيتات ومعاطف رجالية'],
                    ],
                ],
                [
                    'name' => 'إكسسوارات رجالية', 'slug' => 'men-accessories',
                    'description' => 'إكسسوارات رجالية متنوعة',
                    'children' => [
                        ['name' => 'ساعات رجالية', 'slug' => 'men-accessories-watches', 'description' => 'ساعات يد رجالية'],
                        ['name' => 'أحزمة', 'slug' => 'men-accessories-belts', 'description' => 'أحزمة جلدية رجالية'],
                        ['name' => 'محافظ', 'slug' => 'men-accessories-wallets', 'description' => 'محافظ جلدية رجالية'],
                    ],
                ],
            ],
        ],
        [
            'name' => 'أحذية', 'slug' => 'shoes', 'icon' => '👟',
            'description' => 'أحذية رجالية ونسائية',
            'children' => [
                [
                    'name' => 'أحذية نسائية', 'slug' => 'shoes-women',
                    'description' => 'أحذية نسائية متنوعة',
                    'children' => [
                        ['name' => 'سنيكرز نسائي', 'slug' => 'shoes-women-sneakers', 'description' => 'أحذية رياضية نسائية'],
                        ['name' => 'كعب عالي', 'slug' => 'shoes-women-heels', 'description' => 'أحذية كعب عالي'],
                        ['name' => 'صنادل نسائية', 'slug' => 'shoes-women-sandals', 'description' => 'صنادل نسائية'],
                    ],
                ],
                [
                    'name' => 'أحذية رجالية', 'slug' => 'shoes-men',
                    'description' => 'أحذية رجالية متنوعة',
                    'children' => [
                        ['name' => 'سنيكرز رجالي', 'slug' => 'shoes-men-sneakers', 'description' => 'أحذية رياضية رجالية'],
                        ['name' => 'أحذية رسمية', 'slug' => 'shoes-men-formal', 'description' => 'أحذية رسمية رجالية'],
                        ['name' => 'صنادل رجالية', 'slug' => 'shoes-men-sandals', 'description' => 'صنادل رجالية'],
                    ],
                ],
            ],
        ],
        [
            'name' => 'حقائب', 'slug' => 'bags', 'icon' => '👜',
            'description' => 'حقائب نسائية ورجالية',
            'children' => [
                ['name' => 'حقائب يد', 'slug' => 'bags-handbags', 'description' => 'حقائب يد نسائية'],
                ['name' => 'حقائب ظهر', 'slug' => 'bags-backpacks', 'description' => 'حقائب ظهر عملية'],
                ['name' => 'حقائب سفر', 'slug' => 'bags-travel', 'description' => 'حقائب سفر وشنط سفر'],
            ],
        ],
        [
            'name' => 'تجميل', 'slug' => 'beauty', 'icon' => '💄',
            'description' => 'منتجات تجميل وعناية',
            'children' => [
                ['name' => 'العناية بالبشرة', 'slug' => 'beauty-skincare', 'description' => 'منتجات العناية بالبشرة'],
                ['name' => 'مكياج', 'slug' => 'beauty-makeup', 'description' => 'مستحضرات المكياج'],
                ['name' => 'العناية بالشعر', 'slug' => 'beauty-haircare', 'description' => 'منتجات العناية بالشعر'],
                ['name' => 'عطور', 'slug' => 'beauty-fragrance', 'description' => 'عطور رجالية ونسائية'],
            ],
        ],
        [
            'name' => 'المنزل', 'slug' => 'home', 'icon' => '🏠',
            'description' => 'مستلزمات وديكورات المنزل',
            'children' => [
                ['name' => 'ديكور', 'slug' => 'home-decor', 'description' => 'قطع ديكور منزلية'],
                ['name' => 'أدوات مطبخ', 'slug' => 'home-kitchen', 'description' => 'أدوات ومستلزمات المطبخ'],
                ['name' => 'مفروشات', 'slug' => 'home-bedding', 'description' => 'مفروشات ومستلزمات غرف النوم'],
            ],
        ],
        [
            'name' => 'أطفال', 'slug' => 'kids', 'icon' => '👶',
            'description' => 'ملابس ومستلزمات الأطفال',
            'children' => [
                [
                    'name' => 'ملابس أطفال', 'slug' => 'kids-clothing',
                    'description' => 'ملابس أطفال بنات وأولاد',
                    'children' => [
                        ['name' => 'فساتين بنات', 'slug' => 'kids-clothing-dresses', 'description' => 'فساتين وأطقم بناتية'],
                    ],
                ],
            ],
        ],
        [
            'name' => 'إلكترونيات', 'slug' => 'electronics', 'icon' => '📱',
            'description' => 'إلكترونيات وإكسسوارات تقنية',
            'children' => [
                ['name' => 'جوالات وإكسسوارات', 'slug' => 'electronics-mobiles', 'description' => 'جوالات وإكسسواراتها'],
                ['name' => 'صوتيات', 'slug' => 'electronics-audio', 'description' => 'سماعات ومكبرات صوت'],
                ['name' => 'أجهزة قابلة للارتداء', 'slug' => 'electronics-wearables', 'description' => 'ساعات ذكية وأجهزة قابلة للارتداء'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::$categories as $category) {
            $this->createCategory($category, null);
        }
    }

    /**
     * Recursively create/update a category node and its children, keyed by slug.
     */
    private function createCategory(array $data, ?int $parentId): void
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        $category = Category::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'icon' => $data['icon'] ?? null,
                'description' => $data['description'] ?? null,
                'parent_id' => $parentId,
                'is_active' => true,
            ]
        );

        foreach ($children as $child) {
            $this->createCategory($child, $category->id);
        }
    }

    /**
     * Flatten the tree into a slug => node map (without the nested 'children' key).
     */
    public static function flattenSlugs(array $nodes = null): array
    {
        $nodes = $nodes ?? self::$categories;
        $result = [];

        foreach ($nodes as $node) {
            $children = $node['children'] ?? [];
            $flatNode = $node;
            unset($flatNode['children']);
            $result[$node['slug']] = $flatNode;

            if (! empty($children)) {
                $result += self::flattenSlugs($children);
            }
        }

        return $result;
    }
}
