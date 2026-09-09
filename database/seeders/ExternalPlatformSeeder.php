<?php

namespace Database\Seeders;

use App\Models\ExternalPlatform;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExternalPlatformSeeder extends Seeder
{
    public static array $platforms = [
        [
            'name' => 'SHEIN',
            'type' => 'fashion',
            'currency' => 'AED',
            'country' => 'AE',
            'website' => 'https://www.shein.com',
            'logo' => '/images/platforms/shein.svg',
            'commission_rate' => 10,
            'markup_rate' => 15,
        ],
        [
            'name' => 'Trendyol',
            'type' => 'fashion',
            'currency' => 'AED',
            'country' => 'AE',
            'website' => 'https://www.trendyol.com',
            'logo' => '/images/platforms/trendyol.svg',
            'commission_rate' => 10,
            'markup_rate' => 15,
        ],
        [
            'name' => 'Temu',
            'type' => 'general',
            'currency' => 'AED',
            'country' => 'AE',
            'website' => 'https://www.temu.com',
            'logo' => '/images/platforms/temu.svg',
            'commission_rate' => 10,
            'markup_rate' => 15,
        ],
        [
            'name' => 'Noon',
            'type' => 'general',
            'currency' => 'AED',
            'country' => 'AE',
            'website' => 'https://www.noon.com/uae-en',
            'logo' => '/images/platforms/noon.svg',
            'commission_rate' => 8,
            'markup_rate' => 12,
        ],
        [
            'name' => 'Amazon',
            'type' => 'general',
            'currency' => 'AED',
            'country' => 'AE',
            'website' => 'https://www.amazon.ae',
            'logo' => '/images/platforms/amazon.svg',
            'commission_rate' => 8,
            'markup_rate' => 12,
        ],
    ];

    public function run(): void
    {
        foreach (self::$platforms as $platform) {
            ExternalPlatform::updateOrCreate(
                ['slug' => Str::slug($platform['name'])],
                [
                    'name' => $platform['name'],
                    'type' => $platform['type'],
                    'status' => 'active',
                    'currency' => $platform['currency'],
                    'country' => $platform['country'],
                    'website' => $platform['website'],
                    'logo' => $platform['logo'],
                    'commission_rate' => $platform['commission_rate'],
                    'markup_rate' => $platform['markup_rate'],
                    'is_active' => true,
                ]
            );
        }
    }
}
