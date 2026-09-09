<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StoreSettings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(StoreSettings $settings)
    {
        $s = $settings->all();
        $allMethods = config('payments.methods');
        $instructions = config('payments.instructions');

        $enabled = $s['payment_methods_enabled'] ?? array_keys($allMethods);
        $enabledMethods = [];
        foreach ($allMethods as $key => $label) {
            $enabledMethods[$key] = in_array($key, $enabled, true);
        }

        $homeSections = $settings->homeSections();
        $homeSectionLimit = $settings->homeSectionLimit();
        $reviewRewards = $settings->reviewRewards();

        return view('admin.settings.index', compact(
            's',
            'allMethods',
            'instructions',
            'enabledMethods',
            'homeSections',
            'homeSectionLimit',
            'reviewRewards'
        ));
    }

    public function update(Request $request, StoreSettings $settings)
    {
        $request->validate([
            'store_name' => 'nullable|string|max:100',
            'whatsapp_support' => 'nullable|string|max:30',
            'display_currency' => 'nullable|string|max:10',
            'default_delivery_fee' => 'nullable|integer|min:0',
            'syriatel_number' => 'nullable|string|max:20',
            'mtn_number' => 'nullable|string|max:20',
            'sham_number' => 'nullable|string|max:20',
            'bank_iban' => 'nullable|string|max:60',
            'bank_holder' => 'nullable|string|max:100',
            'home_section_limit' => 'nullable|integer|min:4|max:24',
            'home_sections' => 'nullable|array',
            'review_reward_text_syp' => 'nullable|integer|min:0|max:1000000',
            'review_reward_photo_syp' => 'nullable|integer|min:0|max:1000000',
            'return_days_default' => 'nullable|integer|min:1|max:90',
            'exchange_days_default' => 'nullable|integer|min:1|max:90',
        ]);

        $s = $settings->all();

        foreach ([
            'store_name', 'whatsapp_support', 'display_currency', 'default_delivery_fee',
            'syriatel_number', 'mtn_number', 'sham_number', 'bank_iban', 'bank_holder',
        ] as $field) {
            $s[$field] = $request->input($field);
        }

        foreach (['maintenance_mode', 'show_out_of_stock', 'allow_guest_cart', 'show_prices_in_syp'] as $flag) {
            $s[$flag] = $request->boolean($flag);
        }

        $s['payment_methods_enabled'] = $request->input('payment_methods', []);

        $defaults = StoreSettings::defaultHomeSections();
        $posted = $request->input('home_sections', []);
        $home = [];
        foreach ($defaults as $key => $def) {
            $row = is_array($posted[$key] ?? null) ? $posted[$key] : [];
            $home[$key] = [
                'enabled' => ! empty($row['enabled']),
                'eyebrow' => trim((string) ($row['eyebrow'] ?? $def['eyebrow'])) ?: $def['eyebrow'],
                'title' => trim((string) ($row['title'] ?? $def['title'])) ?: $def['title'],
            ];
        }
        $s['home_sections'] = $home;
        $s['home_section_limit'] = max(4, min(24, (int) $request->input('home_section_limit', 8)));
        $s['review_reward_text_syp'] = max(0, (int) $request->input('review_reward_text_syp', 500));
        $s['review_reward_photo_syp'] = max(0, (int) $request->input('review_reward_photo_syp', 1000));
        $s['return_days_default'] = max(1, min(90, (int) $request->input('return_days_default', 7) ?: 7));
        $s['exchange_days_default'] = max(1, min(90, (int) $request->input('exchange_days_default', 7) ?: 7));

        $settings->put($s);

        return redirect()->route('admin.settings.index')->with('success', 'تم حفظ الإعدادات بنجاح.');
    }
}
