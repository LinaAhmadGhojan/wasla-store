<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public static array $brands = [
        ['name' => 'Nike', 'description' => 'Sportswear, shoes and athletic apparel.'],
        ['name' => 'Adidas', 'description' => 'Sportswear and lifestyle apparel.'],
        ['name' => 'Zara', 'description' => 'Fast-fashion clothing and accessories.'],
        ['name' => 'Samsung', 'description' => 'Consumer electronics and mobile devices.'],
        ['name' => 'Apple', 'description' => 'Consumer electronics, computers and accessories.'],
        ["name" => "L'Oreal Paris", 'description' => 'Beauty and skincare products.'],
        ['name' => 'SHEIN', 'description' => 'أزياء SHEIN — وسيط شراء عبر وصلة.'],
        ['name' => 'HolaBebe', 'description' => 'ملابس أطفال — HolaBebe.', 'logo' => '/brands/hola-bebe.svg'],
    ];

    public function run(): void
    {
        foreach (self::$brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => Str::slug($brand['name'])],
                [
                    'name' => $brand['name'],
                    'description' => $brand['description'],
                    'logo' => $brand['logo'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }
}
