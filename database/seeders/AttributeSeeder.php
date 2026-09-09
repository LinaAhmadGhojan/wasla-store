<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    public static array $attributes = [
        'Color' => ['type' => 'color', 'values' => ['Black', 'White', 'Red', 'Blue', 'Green', 'Beige']],
        'Size' => ['type' => 'text', 'values' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']],
        'Material' => ['type' => 'text', 'values' => ['Cotton', 'Polyester', 'Leather', 'Denim', 'Wool']],
    ];

    public function run(): void
    {
        foreach (self::$attributes as $name => $definition) {
            $attribute = Attribute::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'type' => $definition['type']]
            );

            foreach ($definition['values'] as $value) {
                $attribute->values()->firstOrCreate(['value' => $value]);
            }
        }
    }
}
