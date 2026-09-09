<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use App\Services\CurrencyService;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        if (ExchangeRate::current()) {
            return;
        }

        app(CurrencyService::class)->setCurrentRate(
            (float) config('currency.default_aed_to_syp', 14500),
            'سعر افتراضي — عدّليه من لوحة الإدارة',
        );
    }
}
