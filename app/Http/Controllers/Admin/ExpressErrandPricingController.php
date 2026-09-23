<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ExpressErrand\ExpressErrandPricingSettings;
use Illuminate\Http\Request;

class ExpressErrandPricingController extends Controller
{
    public function edit(ExpressErrandPricingSettings $settings)
    {
        $p = $settings->all();

        return view('admin.express.pricing', ['p' => $p]);
    }

    public function update(Request $request, ExpressErrandPricingSettings $settings)
    {
        $request->validate([
            'local_base_fee' => 'required|integer|min:0',
            'local_per_item' => 'required|integer|min:0',
            'local_urgent_multiplier' => 'required|numeric|min:1|max:3',
            'same_governorate_fee' => 'required|integer|min:0',
            'default_inter_fee' => 'required|integer|min:0',
            'size_small' => 'required|integer|min:0',
            'size_medium' => 'required|integer|min:0',
            'size_large' => 'required|integer|min:0',
            'size_unknown' => 'required|integer|min:0',
            'routes' => 'nullable|array',
            'routes.*.from' => 'nullable|string|max:80',
            'routes.*.to' => 'nullable|string|max:80',
            'routes.*.fee_syp' => 'nullable|integer|min:0',
            'routes.*.carrier' => 'nullable|string|max:32',
        ]);

        $current = $settings->all();

        $current['local_errand']['base_fee_syp'] = (int) $request->input('local_base_fee');
        $current['local_errand']['per_item_syp'] = (int) $request->input('local_per_item');
        $current['local_errand']['urgent_multiplier'] = (float) $request->input('local_urgent_multiplier');

        foreach (['grocery', 'pharmacy', 'produce', 'household', 'other'] as $cat) {
            $val = $request->input('cat_'.$cat);
            if ($val !== null && $val !== '') {
                $current['local_errand']['category_fees'][$cat] = (int) $val;
            }
        }

        $current['same_governorate_fee_syp'] = (int) $request->input('same_governorate_fee');
        $current['default_inter_governorate_fee_syp'] = (int) $request->input('default_inter_fee');

        $current['parcel_sizes'] = [
            'small' => (int) $request->input('size_small'),
            'medium' => (int) $request->input('size_medium'),
            'large' => (int) $request->input('size_large'),
            'unknown' => (int) $request->input('size_unknown'),
        ];

        $routes = [];
        foreach ($request->input('routes', []) as $row) {
            if (empty($row['from']) || empty($row['to']) || ! isset($row['fee_syp'])) {
                continue;
            }
            $routes[] = [
                'from' => trim($row['from']),
                'to' => trim($row['to']),
                'fee_syp' => (int) $row['fee_syp'],
                'carrier' => $row['carrier'] ?? 'qadmous',
            ];
        }
        $current['governorate_routes'] = $routes;

        $carriers = $current['carriers'] ?? [];
        foreach ($carriers as $i => $carrier) {
            $key = $carrier['key'] ?? '';
            $carriers[$i]['available'] = $request->boolean('carrier_'.$key.'_available');
            $markup = $request->input('carrier_'.$key.'_markup');
            if ($markup !== null && $markup !== '') {
                $carriers[$i]['fee_markup_syp'] = (int) $markup;
            }
        }
        $current['carriers'] = $carriers;

        $settings->put($current);

        return redirect()->route('admin.express-pricing.edit')->with('success', 'تم حفظ تسعير على بابك.');
    }
}
