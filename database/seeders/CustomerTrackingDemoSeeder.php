<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\DeliveryEvent;
use App\Models\ExpressErrandRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Delivery\OrderDeliveryService;
use App\Services\ExpressErrand\ExpressErrandQuoteService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerTrackingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@wasla.test')->first();
        if (! $customer) {
            return;
        }

        $driver = User::where('email', 'driver@wasla.test')->first();
        $vendor = Vendor::query()->first();
        $products = Product::query()->where('is_active', true)->limit(3)->get();

        DB::transaction(function () use ($customer, $driver, $vendor, $products) {
            $orderIds = Order::where('user_id', $customer->id)->pluck('id');
            if ($orderIds->isNotEmpty()) {
                DeliveryEvent::whereIn('order_id', $orderIds)->delete();
                OrderItem::whereIn('order_id', $orderIds)->delete();
                Payment::whereIn('order_id', $orderIds)->delete();
                Order::whereIn('id', $orderIds)->delete();
            }

            ExpressErrandRequest::where('user_id', $customer->id)->delete();

            $address = Address::updateOrCreate(
                ['user_id' => $customer->id, 'label' => 'البيت — دمشق'],
                [
                    'recipient_name' => $customer->name,
                    'phone' => '0991234567',
                    'country' => 'سوريا',
                    'city' => 'دمشق',
                    'state' => 'المزة',
                    'postal_code' => '-',
                    'street_address' => 'شارع baghdad، بناء 12، طابق 3',
                    'latitude' => 33.5220,
                    'longitude' => 36.2915,
                    'is_default' => true,
                ]
            );

            $delivery = app(OrderDeliveryService::class);

            $geoDamascus = [
                'origin' => ['lat' => 33.5138, 'lng' => 36.2765, 'label' => 'مستودع وصلة — دمشق'],
                'destination' => ['lat' => 33.5220, 'lng' => 36.2915, 'label' => 'دمشق · المزة'],
                'driver' => ['lat' => 33.5185, 'lng' => 36.2840, 'label' => 'المندوب'],
            ];

            // 1) قيد التجهيز
            if ($products->count() >= 1 && $vendor) {
                $p = $products[0];
                $v = ProductVariant::where('product_id', $p->id)->first();
                $orderPrep = Order::create([
                    'user_id' => $customer->id,
                    'vendor_id' => $vendor->id,
                    'status' => 'preparing',
                    'subtotal' => 89000,
                    'shipping_cost' => 15000,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total' => 104000,
                    'shipping_address_id' => $address->id,
                    'tracking_number' => 'WAS000101DEMO',
                    'shipping_method' => 'express',
                    'placed_at' => now()->subHours(3),
                    'delivery_geo' => [
                        'origin' => $geoDamascus['origin'],
                        'destination' => $geoDamascus['destination'],
                    ],
                ]);
                OrderItem::create([
                    'order_id' => $orderPrep->id,
                    'product_id' => $p->id,
                    'product_variant_id' => $v?->id,
                    'quantity' => 1,
                    'unit_price' => 89000,
                    'line_total' => 89000,
                ]);
                Payment::create([
                    'order_id' => $orderPrep->id,
                    'payment_method' => 'cash_on_delivery',
                    'amount' => 104000,
                    'status' => 'paid',
                    'paid_at' => now()->subHours(2),
                ]);
                $delivery->ensureInitialEvent($orderPrep);
                $delivery->addLogisticsNote($orderPrep, 'local_warehouse');
                $delivery->addLogisticsNote($orderPrep, 'packing');
            }

            // 2) خرج للتسليم + مندوب على الخريطة
            if ($products->count() >= 2 && $vendor && $driver) {
                $p = $products[1];
                $v = ProductVariant::where('product_id', $p->id)->first();
                $orderLive = Order::create([
                    'user_id' => $customer->id,
                    'vendor_id' => $vendor->id,
                    'driver_id' => $driver->id,
                    'status' => 'out_for_delivery',
                    'subtotal' => 125000,
                    'shipping_cost' => 18000,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total' => 143000,
                    'shipping_address_id' => $address->id,
                    'tracking_number' => 'WAS000102LIVE',
                    'shipping_method' => 'express',
                    'delivery_otp' => '482916',
                    'placed_at' => now()->subHours(5),
                    'assigned_at' => now()->subHours(2),
                    'out_for_delivery_at' => now()->subMinutes(25),
                    'delivery_geo' => $geoDamascus,
                ]);
                OrderItem::create([
                    'order_id' => $orderLive->id,
                    'product_id' => $p->id,
                    'product_variant_id' => $v?->id,
                    'quantity' => 2,
                    'unit_price' => 62500,
                    'line_total' => 125000,
                ]);
                Payment::create([
                    'order_id' => $orderLive->id,
                    'payment_method' => 'cash_on_delivery',
                    'amount' => 143000,
                    'status' => 'paid',
                    'paid_at' => now()->subHours(4),
                ]);
                $delivery->ensureInitialEvent($orderLive);
                foreach (['local_warehouse', 'packing', 'hub', 'out_with_driver'] as $logKey) {
                    $delivery->addLogisticsNote($orderLive, $logKey);
                }
            }

            $quotes = app(ExpressErrandQuoteService::class);
            $q1 = $quotes->quote([
                'service_type' => 'local_errand',
                'category' => 'grocery',
                'items_count' => 2,
                'urgency' => 'normal',
            ]);
            ExpressErrandRequest::create([
                'user_id' => $customer->id,
                'reference' => 'MSH-'.now()->format('ymd').'-DEMO1',
                'service_type' => 'local_errand',
                'category' => 'grocery',
                'items' => [
                    ['name' => 'موز', 'qty' => '1 كيلو'],
                    ['name' => 'خبز', 'qty' => '2'],
                ],
                'store_preference' => 'any',
                'delivery_address' => '[دمشق] المزة — شارع baghdad',
                'destination_governorate' => 'دمشق',
                'contact_phone' => '0991234567',
                'quote_total_syp' => $q1['total_syp'],
                'quote_breakdown' => $q1,
                'shipping_carrier' => 'wasla',
                'quote_confirmed_at' => now(),
                'status' => ExpressErrandRequest::STATUS_ACCEPTED,
                'urgency' => 'normal',
            ]);

            $q2 = $quotes->quote([
                'service_type' => 'parcel_receive',
                'origin_governorate' => 'حلب',
                'destination_governorate' => 'دمشق',
                'parcel_size' => 'medium',
                'shipping_carrier' => 'qadmous',
                'urgency' => 'normal',
            ]);
            ExpressErrandRequest::create([
                'user_id' => $customer->id,
                'reference' => 'RCV-'.now()->format('ymd').'-DEMO2',
                'service_type' => 'parcel_receive',
                'category' => 'other',
                'items' => [],
                'store_preference' => 'any',
                'origin_governorate' => 'حلب',
                'origin_details' => 'عند أخي — حي السفير',
                'destination_governorate' => 'دمشق',
                'parcel_description' => 'صندوق صغير — ملابس',
                'parcel_size' => 'medium',
                'delivery_address' => '[دمشق] المزة',
                'contact_phone' => '0991234567',
                'quote_total_syp' => $q2['total_syp'],
                'quote_breakdown' => $q2,
                'shipping_carrier' => 'qadmous',
                'quote_confirmed_at' => now(),
                'status' => ExpressErrandRequest::STATUS_PENDING,
                'urgency' => 'normal',
            ]);
        });
    }
}
