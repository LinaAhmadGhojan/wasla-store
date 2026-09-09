<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Support\ProductImageCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class WaslaSeeder extends Seeder
{
    public function run(): void
    {
        $customerRole = Role::firstWhere('name', 'customer');
        $sellerRole = Role::firstWhere('name', 'seller');
        $driverRole = Role::firstWhere('name', 'driver');
        $adminRole = Role::firstWhere('name', 'admin');

        $admin = User::firstOrCreate([
            'email' => 'admin@wasla.test'
        ], [
            'name' => 'Wasla Admin',
            'phone' => '+971500000001',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);

        $customer = User::firstOrCreate([
            'email' => 'customer@wasla.test'
        ], [
            'name' => 'Rana Customer',
            'phone' => '+971500000002',
            'password' => Hash::make('password'),
            'role_id' => $customerRole->id,
        ]);

        $seller = User::firstOrCreate([
            'email' => 'seller@wasla.test'
        ], [
            'name' => 'Zain Vendor',
            'phone' => '+971500000003',
            'password' => Hash::make('password'),
            'role_id' => $sellerRole->id,
        ]);

        $driver = User::firstOrCreate([
            'email' => 'driver@wasla.test'
        ], [
            'name' => 'Nora Driver',
            'phone' => '+971500000004',
            'password' => Hash::make('password'),
            'role_id' => $driverRole->id,
        ]);

        // create example stores
        $seller2 = User::firstOrCreate([
            'email' => 'seller2@wasla.test'
        ], [
            'name' => 'Maya Shop',
            'phone' => '+971500000005',
            'password' => Hash::make('password'),
            'role_id' => $sellerRole->id,
        ]);

        $seller3 = User::firstOrCreate([
            'email' => 'seller3@wasla.test'
        ], [
            'name' => 'Ali Mart',
            'phone' => '+971500000006',
            'password' => Hash::make('password'),
            'role_id' => $sellerRole->id,
        ]);

        $store1 = Vendor::firstOrCreate([
            'slug' => 'wasla-fashion-hub'
        ], [
            'owner_id' => $seller->id,
            'store_name' => 'Wasla Fashion Hub',
            'description' => 'Stylish apparel and beauty products delivered fast.',
            'logo' => 'wasla-fashion-logo.png',
            'banner' => 'wasla-fashion-banner.png',
            'commission_rate' => 18.00,
            'status' => 'active',
        ]);

        $store2 = Vendor::firstOrCreate([
            'slug' => 'maya-beauty'
        ], [
            'owner_id' => $seller2->id,
            'store_name' => 'Maya Beauty',
            'description' => 'Quality beauty and skincare.',
            'logo' => 'maya-logo.png',
            'banner' => 'maya-banner.png',
            'commission_rate' => 15.00,
            'status' => 'active',
        ]);

        $store3 = Vendor::firstOrCreate([
            'slug' => 'ali-mart'
        ], [
            'owner_id' => $seller3->id,
            'store_name' => 'Ali Mart',
            'description' => 'Everyday essentials and groceries.',
            'logo' => 'ali-logo.png',
            'banner' => 'ali-banner.png',
            'commission_rate' => 12.00,
            'status' => 'active',
        ]);

        $stores = [$store1, $store2, $store3];

        // product name pools keyed by leaf category slug
        $productNamesByCategory = [
            'women-clothing-dresses' => ['فستان صيفي منقوش', 'فستان سهرة أنيق', 'فستان كاجوال قطن', 'فستان ماكسي مطبع', 'فستان دانتيل فاخر', 'فستان قصير أنيق'],
            'women-clothing-tops' => ['بلوزة حرير أنيقة', 'توب رياضي', 'قميص نسائي كلاسيك', 'كروب توب صيفي', 'بلوزة دانتيل'],
            'women-clothing-pants' => ['بنطلون واسع نسائي', 'بنطلون قماش أنيق', 'بنطلون كاجوال', 'بنطلون سباق'],
            'women-clothing-jeans' => ['جينز سكيني نسائي', 'جينز مقصوص عصري', 'جينز واسع الساق', 'جينز بوي فريند', 'جينز ممزق نسائي'],
            'women-clothing-jackets' => ['جاكيت جلد نسائي', 'جاكيت جينز', 'معطف شتوي أنيق', 'بليزر نسائي'],
            'women-accessories-jewelry' => ['طقم مجوهرات فضي', 'قلادة ذهبية أنيقة', 'أقراط لؤلؤ', 'أسورة كريستال', 'خاتم فضي'],
            'women-accessories-watches' => ['ساعة يد نسائية كلاسيك', 'ساعة رقمية نسائية', 'ساعة جلد نسائية أنيقة', 'ساعة سوار نسائية'],
            'women-accessories-scarves' => ['وشاح حرير منقوش', 'شال صوفي شتوي', 'طرحة أنيقة'],
            'men-clothing-shirts' => ['قميص رسمي أبيض', 'قميص كاروهات كاجوال', 'قميص كتان صيفي', 'قميص رجالي مقلم', 'قميص أكسفورد'],
            'men-clothing-tshirts' => ['تيشيرت قطن أساسي', 'تيشيرت مطبوع عصري', 'تيشيرت رياضي', 'تيشيرت بولو', 'تيشيرت أوفر سايز'],
            'men-clothing-pants' => ['بنطلون قماش رجالي', 'بنطلون كاجوال', 'شورت رجالي صيفي', 'بنطلون رياضي'],
            'men-clothing-jeans' => ['جينز رجالي كلاسيك', 'جينز سليم فيت', 'جينز ممزق عصري', 'جينز رجالي واسع'],
            'men-clothing-jackets' => ['جاكيت جلد رجالي', 'معطف شتوي رجالي', 'بليزر رسمي', 'هوديز رجالي'],
            'men-accessories-belts' => ['حزام جلد طبيعي', 'حزام كلاسيك أسود', 'حزام رياضي', 'حزام جلد لامع'],
            'men-accessories-watches' => ['ساعة يد رجالية فاخرة', 'ساعة رياضية رجالية', 'ساعة كلاسيك جلد', 'ساعة معدنية أنيقة'],
            'men-accessories-wallets' => ['محفظة جلد رجالية', 'محفظة كاجوال', 'محفظة بطاقات'],
            'shoes-women-sneakers' => ['سنيكرز أبيض كاجوال', 'سنيكرز رياضي نسائي', 'سنيكرز ملون عصري', 'سنيكرز جلد نسائي', 'سنيكرز شبكي'],
            'shoes-women-heels' => ['حذاء كعب عالي أسود', 'صندل كعب سهرة', 'حذاء كعب متوسط أنيق', 'حذاء كعب لامع'],
            'shoes-women-sandals' => ['صندل نسائي صيفي', 'صندل فلات مريح', 'صندل منصة'],
            'shoes-men-sneakers' => ['سنيكرز رجالي رياضي', 'سنيكرز جلد رجالي', 'سنيكرز كاجوال أبيض', 'سنيكرز شبك تنفس', 'سنيكرز راننج'],
            'shoes-men-formal' => ['حذاء رسمي جلد', 'حذاء أوكسفورد كلاسيك', 'حذاء رسمي أسود', 'حذاء لوفر جلد'],
            'shoes-men-sandals' => ['صندل رجالي صيفي', 'شبشب جلد رجالي', 'صندل رياضي'],
            'bags-handbags' => ['حقيبة يد جلدية', 'حقيبة كتف عصرية', 'حقيبة كروس صغيرة', 'حقيبة يد فاخرة', 'حقيبة كلاتش سهرة'],
            'bags-backpacks' => ['حقيبة ظهر مدرسية', 'حقيبة ظهر رياضية', 'حقيبة ظهر جلد', 'حقيبة ظهر لابتوب'],
            'bags-travel' => ['حقيبة سفر متوسطة', 'حقيبة ترولي', 'حقيبة يد سفر'],
            'beauty-skincare' => ['كريم ترطيب الوجه', 'غسول منظف للبشرة', 'سيروم فيتامين سي', 'ماسك ترطيب مكثف', 'واقي شمس'],
            'beauty-makeup' => ['أحمر شفاه مطفي', 'كريم أساس طويل الثبات', 'ماسكارا تكثيف الرموش', 'بلاشر خدود', 'آيلاينر أسود'],
            'beauty-haircare' => ['شامبو مغذي', 'بلسم شعر', 'زيت شعر طبيعي', 'سبراي حماية حرارية'],
            'beauty-fragrance' => ['عطر نسائي فاخر', 'عطر رجالي كلاسيك', 'معطر جسم', 'عطر خشبي'],
            'home-decor' => ['شمعة معطرة فاخرة', 'لوحة جدارية ديكور', 'مزهرية سيراميك', 'إطار صور خشبي', 'وسادة ديكور'],
            'home-kitchen' => ['طقم أواني طهي', 'عصارة كهربائية', 'مجموعة سكاكين مطبخ', 'طقم أكواب زجاج', 'خلاط يدوي'],
            'home-bedding' => ['طقم ملايات قطن', 'لحاف شتوي', 'مخدة مريحة', 'غطاء سرير'],
            'electronics-mobiles' => ['سماعة بلوتوث لاسلكية', 'شاحن سريع 20 واط', 'باور بانك 10000 مللي أمبير', 'كفر جوال حماية', 'حامل جوال سيارة'],
            'electronics-audio' => ['سماعة رأس لاسلكية', 'مكبر صوت بلوتوث محمول', 'سماعة أذن سلكية', 'سماعة أذن لاسلكية رياضية'],
            'electronics-wearables' => ['ساعة ذكية', 'سوار لياقة', 'نظارة بلوتوث'],
        ];

        // categories are seeded by CategorySeeder; load the relevant leaf categories for product assignment
        $categories = Category::whereIn('slug', array_keys($productNamesByCategory))->get()->keyBy('slug');

        // optional bonus: assign a random existing brand to generated products, if the column/model exist
        $brandIds = (Schema::hasColumn('products', 'brand_id') && class_exists(Brand::class))
            ? Brand::pluck('id')->all()
            : [];

        // Flatten and expand name pools until we have ~100 unique product slots
        $productSlots = [];
        foreach ($productNamesByCategory as $categorySlug => $productNames) {
            foreach ($productNames as $name) {
                $productSlots[] = [$categorySlug, $name];
            }
        }

        // Pad to 100 by cycling through pools with numbered suffixes
        $baseCount = count($productSlots);
        $slotIndex = 0;
        while (count($productSlots) < 100 && $baseCount > 0) {
            [$categorySlug, $baseName] = $productSlots[$slotIndex % $baseCount];
            $extra = intdiv(count($productSlots), $baseCount) + 1;
            $productSlots[] = [$categorySlug, $baseName . ' ' . $extra];
            $slotIndex++;
        }
        $productSlots = array_slice($productSlots, 0, 100);

        $productCounter = 1;
        foreach ($productSlots as [$categorySlug, $name]) {
            $category = $categories->get($categorySlug);

            if (! $category) {
                continue;
            }

            $variantOptions = $this->variantOptionsForCategory($categorySlug);
            $slug = 'wasla-product-' . $productCounter;
            $price = rand(1999, 19999) / 100; // 19.99 - 199.99
            $salePrice = rand(0, 1) ? round($price * (rand(60, 85) / 100), 2) : null;
            $storeForProduct = $stores[array_rand($stores)];

            $image = ProductImageCatalog::urlFor($categorySlug, $productCounter);

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'vendor_id' => $storeForProduct->id,
                    'category_id' => $category->id,
                    'brand_id' => ! empty($brandIds) ? $brandIds[array_rand($brandIds)] : null,
                    'name' => $name,
                    'description' => "$name — {$category->name} من وصلة Wasla.",
                    'image' => $image,
                    'price' => $price,
                    'sale_price' => $salePrice,
                    'is_featured' => $productCounter <= 12,
                    'is_active' => true,
                    'rating' => rand(3, 5),
                    'total_reviews' => rand(5, 250),
                ]
            );

            // ensure at least 2 variants
            $variantCount = min(3, count($variantOptions['values']));
            foreach (array_slice($variantOptions['values'], 0, $variantCount) as $index => $optionValue) {
                $skuBase = strtoupper('WAS-' . substr(str_replace('-', '', $category->slug), 0, 6) . '-' . $productCounter . '-' . $index);
                $variantPrice = round($product->price + ($index * (rand(200, 500) / 100)), 2);

                ProductVariant::firstOrCreate(
                    ['sku' => $skuBase],
                    [
                        'product_id' => $product->id,
                        'option_name' => $variantOptions['option'],
                        'option_value' => $optionValue,
                        'price' => $variantPrice,
                        'sale_price' => null,
                        'stock_qty' => rand(5, 50),
                    ]
                );
            }

            $productCounter++;
        }

        // Hide older demo products so the storefront shows the fresh 100-item catalog
        Product::where('slug', 'not like', 'wasla-product-%')->update(['is_active' => false]);

        // Backfill image on any leftover older products that have no image yet
        if (Schema::hasColumn('products', 'image')) {
            Product::where(function ($q) {
                $q->whereNull('image')->orWhere('image', '');
            })->each(function (Product $product) {
                $product->loadMissing('category');
                $product->update([
                    'image' => ProductImageCatalog::urlFor($product->category?->slug, (int) $product->id),
                ]);
            });

            Product::where('image', 'like', '%picsum.photos%')->each(function (Product $product) {
                $product->loadMissing('category');
                $product->update([
                    'image' => ProductImageCatalog::urlFor($product->category?->slug, (int) $product->id),
                ]);
            });
        }

        // Avoid duplicating demo orders on every seed run
        if (! Order::where('user_id', $customer->id)->exists()) {
            $firstProduct = Product::first();
            $firstVariant = ProductVariant::first();

            if ($firstProduct && $firstVariant) {
                $order = Order::create([
                    'user_id' => $customer->id,
                    'vendor_id' => $store1->id,
                    'status' => 'delivered',
                    'subtotal' => 49.99,
                    'shipping_cost' => 5.00,
                    'tax_amount' => 2.50,
                    'discount_amount' => 0.00,
                    'total' => 57.49,
                    'shipping_address_id' => Address::firstOrCreate(
                        ['user_id' => $customer->id, 'label' => 'Home'],
                        [
                            'recipient_name' => $customer->name,
                            'phone' => $customer->phone,
                            'country' => 'UAE',
                            'city' => 'Dubai',
                            'state' => 'Dubai',
                            'postal_code' => '00000',
                            'street_address' => '123 Wasla Street',
                            'is_default' => true,
                        ]
                    )->id,
                    'billing_address_id' => null,
                    'tracking_number' => 'WAS' . rand(1000000, 9999999),
                    'shipping_method' => 'Standard',
                    'placed_at' => now()->subDays(4),
                    'delivered_at' => now()->subDays(1),
                ]);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $firstProduct->id,
                    'product_variant_id' => $firstVariant->id,
                    'quantity' => 1,
                    'unit_price' => 49.99,
                    'line_total' => 49.99,
                ]);

                Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => 'cash_on_delivery',
                    'transaction_id' => null,
                    'amount' => 57.49,
                    'status' => 'paid',
                    'paid_at' => now()->subDays(3),
                ]);
            }
        }
    }

    /**
     * Pick a variant dimension (option name + values) appropriate for a leaf category slug:
     * clothing sizes for clothing leaves, shoe sizes for shoe leaves, color otherwise.
     */
    private function variantOptionsForCategory(string $slug): array
    {
        if (str_contains($slug, 'clothing')) {
            return ['option' => 'المقاس', 'values' => ['S', 'M', 'L', 'XL']];
        }

        if (str_starts_with($slug, 'shoes-')) {
            return ['option' => 'المقاس', 'values' => ['38', '39', '40', '41', '42']];
        }

        return ['option' => 'اللون', 'values' => ['أسود', 'أبيض', 'بني', 'أحمر', 'أزرق']];
    }
}
