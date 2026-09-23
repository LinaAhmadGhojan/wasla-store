<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpressErrandRequest;
use App\Services\ExpressErrand\ExpressErrandPricingSettings;
use App\Services\ExpressErrand\ExpressErrandQuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpressErrandController extends Controller
{
    private const SERVICE_TYPES = ['local_errand', 'parcel_receive', 'parcel_send'];

    public function __construct(
        private ExpressErrandQuoteService $quotes,
        private ExpressErrandPricingSettings $pricingSettings,
    ) {
    }

    public function pricingConfig()
    {
        $cfg = $this->pricingSettings->all();
        $carriers = collect($cfg['carriers'] ?? [])->map(fn (array $c) => [
            'key' => $c['key'],
            'label' => $c['label'],
            'available' => (bool) ($c['available'] ?? true),
            'badge' => $c['badge'] ?? null,
        ])->values();

        return response()->json([
            'local_errand' => $cfg['local_errand'] ?? [],
            'parcel_sizes' => $cfg['parcel_sizes'] ?? [],
            'carriers' => $carriers,
            'same_governorate_fee_syp' => $cfg['same_governorate_fee_syp'] ?? null,
        ]);
    }

    public function quote(Request $request)
    {
        $data = $request->validate([
            'service_type' => ['required', Rule::in(self::SERVICE_TYPES)],
            'category' => 'nullable|string|in:grocery,pharmacy,produce,household,other',
            'items_count' => 'nullable|integer|min:1|max:40',
            'origin_governorate' => 'nullable|string|max:80',
            'destination_governorate' => 'nullable|string|max:80',
            'parcel_size' => 'nullable|string|in:small,medium,large,unknown',
            'shipping_carrier' => 'nullable|string|max:32',
            'urgency' => 'nullable|string|in:normal,urgent',
        ]);

        return response()->json($this->quotes->quote($data));
    }

    public function store(Request $request)
    {
        $serviceType = $request->input('service_type', 'local_errand');
        if (! in_array($serviceType, self::SERVICE_TYPES, true)) {
            $serviceType = 'local_errand';
        }

        $rules = [
            'service_type' => ['required', Rule::in(self::SERVICE_TYPES)],
            'delivery_address' => 'required|string|max:2000',
            'contact_phone' => 'required|string|max:32',
            'customer_note' => 'nullable|string|max:2000',
            'budget_syp' => 'nullable|integer|min:1000|max:50000000',
            'urgency' => 'nullable|string|in:normal,urgent',
            'destination_governorate' => 'nullable|string|max:80',
            'origin_governorate' => 'nullable|string|max:80',
            'origin_details' => 'nullable|string|max:2000',
            'parcel_description' => 'nullable|string|max:2000',
            'parcel_size' => 'nullable|string|in:small,medium,large,unknown',
            'shipping_carrier' => 'nullable|string|max:32',
            'quote_confirmed' => 'required|accepted',
        ];

        if ($serviceType === 'local_errand') {
            $rules += [
                'category' => 'required|string|in:grocery,pharmacy,produce,household,other',
                'items' => 'required|array|min:1|max:40',
                'items.*.name' => 'required|string|max:200',
                'items.*.qty' => 'nullable|string|max:40',
                'items.*.note' => 'nullable|string|max:200',
                'store_preference' => 'required|string|in:any,specific',
                'store_name' => 'nullable|required_if:store_preference,specific|string|max:200',
            ];
        }

        if ($serviceType === 'parcel_receive') {
            $rules += [
                'origin_governorate' => 'required|string|max:80',
                'origin_details' => 'required|string|max:2000',
                'destination_governorate' => 'required|string|max:80',
                'parcel_description' => 'required|string|max:2000',
                'parcel_size' => 'nullable|string|in:small,medium,large,unknown',
            ];
        }

        if ($serviceType === 'parcel_send') {
            $rules += [
                'origin_governorate' => 'required|string|max:80',
                'destination_governorate' => 'required|string|max:80',
                'parcel_description' => 'required|string|max:2000',
                'parcel_size' => 'nullable|string|in:small,medium,large,unknown',
            ];
        }

        $data = $request->validate($rules);

        $items = [];
        if ($serviceType === 'local_errand') {
            $items = collect($data['items'])->map(fn (array $row) => [
                'name' => trim($row['name']),
                'qty' => trim((string) ($row['qty'] ?? '1')),
                'note' => isset($row['note']) ? trim((string) $row['note']) : null,
            ])->values()->all();
        }

        $quoteInput = [
            'service_type' => $serviceType,
            'category' => $data['category'] ?? 'other',
            'items_count' => count($items) ?: 1,
            'origin_governorate' => $data['origin_governorate'] ?? null,
            'destination_governorate' => $data['destination_governorate'] ?? null,
            'parcel_size' => $data['parcel_size'] ?? 'unknown',
            'shipping_carrier' => $data['shipping_carrier'] ?? null,
            'urgency' => $data['urgency'] ?? 'normal',
        ];
        $quote = $this->quotes->quote($quoteInput);

        if (! ($quote['carrier_available'] ?? true) && in_array($serviceType, ['parcel_receive', 'parcel_send'], true)) {
            throw ValidationException::withMessages([
                'shipping_carrier' => 'شركة الشحن المختارة غير متاحة حالياً.',
            ]);
        }

        $user = $request->user('sanctum');

        $prefix = match ($serviceType) {
            'parcel_receive' => 'RCV',
            'parcel_send' => 'SND',
            default => 'MSH',
        };
        $reference = $prefix.'-'.now()->format('ymd').'-'.strtoupper(Str::random(5));

        while (ExpressErrandRequest::query()->where('reference', $reference)->exists()) {
            $reference = $prefix.'-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        }

        $message = match ($serviceType) {
            'parcel_receive' => 'تم تأكيد طلب استلام الطرد — المبلغ '.$quote['total_label'].' للخدمة والشحن.',
            'parcel_send' => 'تم تأكيد إرسال الطرد — '.$quote['total_label'].' للشحن.',
            default => 'تم تأكيد المشوار — '.$quote['total_label'].' لأجور الخدمة والتوصيل.',
        };

        $errand = ExpressErrandRequest::query()->create([
            'user_id' => $user?->id,
            'reference' => $reference,
            'service_type' => $serviceType,
            'category' => $data['category'] ?? 'other',
            'items' => $items,
            'store_preference' => $data['store_preference'] ?? 'any',
            'store_name' => $data['store_name'] ?? null,
            'origin_governorate' => $data['origin_governorate'] ?? null,
            'origin_details' => isset($data['origin_details']) ? trim($data['origin_details']) : null,
            'destination_governorate' => $data['destination_governorate'] ?? null,
            'parcel_description' => isset($data['parcel_description']) ? trim($data['parcel_description']) : null,
            'parcel_size' => $data['parcel_size'] ?? 'unknown',
            'delivery_address' => trim($data['delivery_address']),
            'contact_phone' => trim($data['contact_phone']),
            'customer_note' => isset($data['customer_note']) ? trim($data['customer_note']) : null,
            'budget_syp' => $data['budget_syp'] ?? null,
            'urgency' => $data['urgency'] ?? 'normal',
            'quote_total_syp' => $quote['total_syp'],
            'quote_breakdown' => $quote,
            'shipping_carrier' => $quote['shipping_carrier'] ?? null,
            'quote_confirmed_at' => now(),
            'status' => ExpressErrandRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'id' => $errand->id,
            'reference' => $errand->reference,
            'service_type' => $errand->service_type,
            'status' => $errand->status,
            'quote' => $quote,
            'message' => $message,
        ], 201);
    }

    public function mine(Request $request)
    {
        $labels = [
            'local_errand' => 'مشوار',
            'parcel_receive' => 'استلام طرد',
            'parcel_send' => 'إرسال طرد',
        ];

        $rows = ExpressErrandRequest::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn (ExpressErrandRequest $row) => [
                'id' => $row->id,
                'reference' => $row->reference,
                'service_type' => $row->service_type,
                'service_label' => $labels[$row->service_type] ?? $row->service_type,
                'category' => $row->category,
                'items_count' => count($row->items ?? []),
                'origin_governorate' => $row->origin_governorate,
                'destination_governorate' => $row->destination_governorate,
                'quote_total_syp' => $row->quote_total_syp,
                'status' => $row->status,
                'urgency' => $row->urgency,
                'created_at' => $row->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $rows]);
    }
}
