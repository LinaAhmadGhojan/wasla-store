<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StoreFollow;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class StoreFollowController extends Controller
{
    public function index(Request $request)
    {
        $rows = StoreFollow::query()
            ->with('vendor')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (StoreFollow $f) => $this->serializeVendor($f->vendor, true));

        return response()->json(['stores' => $rows->filter()->values()]);
    }

    public function follow(Request $request, Vendor $vendor)
    {
        StoreFollow::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'vendor_id' => $vendor->id,
        ]);

        return response()->json(['ok' => true, 'following' => true]);
    }

    public function unfollow(Request $request, Vendor $vendor)
    {
        StoreFollow::query()
            ->where('user_id', $request->user()->id)
            ->where('vendor_id', $vendor->id)
            ->delete();

        return response()->json(['ok' => true, 'following' => false]);
    }

    public function status(Request $request, Vendor $vendor)
    {
        $user = $this->optionalUser($request);
        $following = $user
            ? StoreFollow::query()->where('user_id', $user->id)->where('vendor_id', $vendor->id)->exists()
            : false;

        return response()->json([
            'following' => $following,
            'followers_count' => StoreFollow::query()->where('vendor_id', $vendor->id)->count(),
        ]);
    }

    public function show(Request $request, Vendor $vendor)
    {
        $tab = $request->query('tab', 'all');
        $limit = min(48, max(8, (int) $request->query('per_page', 16)));

        $query = Product::with(['brand', 'category', 'images'])
            ->where('vendor_id', $vendor->id)
            ->where('is_active', true);

        if ($tab === 'offers') {
            $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price')
                ->orderByDesc('created_at');
        } elseif ($tab === 'new') {
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('is_featured')->orderByDesc('created_at');
        }

        $products = $query->paginate($limit);
        $user = $this->optionalUser($request);
        $following = $user
            ? StoreFollow::query()->where('user_id', $user->id)->where('vendor_id', $vendor->id)->exists()
            : false;

        $newCount7d = Product::query()
            ->where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return response()->json([
            'store' => $this->serializeVendor($vendor, $following),
            'tab' => $tab,
            'new_last_7_days' => $newCount7d,
            'products' => [
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'total' => $products->total(),
                ],
            ],
        ]);
    }

    public function feed(Request $request)
    {
        $vendorIds = StoreFollow::query()
            ->where('user_id', $request->user()->id)
            ->pluck('vendor_id');

        if ($vendorIds->isEmpty()) {
            return response()->json(['updates' => []]);
        }

        $updates = Product::query()
            ->selectRaw('vendor_id, COUNT(*) as new_count, MAX(created_at) as last_at')
            ->whereIn('vendor_id', $vendorIds)
            ->where('is_active', true)
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('vendor_id')
            ->havingRaw('COUNT(*) > 0')
            ->orderByDesc('last_at')
            ->get();

        $vendors = Vendor::query()->whereIn('id', $updates->pluck('vendor_id'))->get()->keyBy('id');

        $out = $updates->map(function ($row) use ($vendors) {
            $v = $vendors->get($row->vendor_id);
            if (! $v) {
                return null;
            }

            return [
                'vendor_id' => $v->id,
                'store_name' => $v->store_name,
                'new_count' => (int) $row->new_count,
                'message' => $v->store_name.' أضاف '.((int) $row->new_count).' منتجات جديدة.',
                'url' => url('/stores/'.$v->id.'?tab=new'),
                'last_at' => $row->last_at,
            ];
        })->filter()->values();

        return response()->json(['updates' => $out]);
    }

    private function optionalUser(Request $request)
    {
        if ($request->user()) {
            return $request->user();
        }
        $plain = $request->bearerToken();
        if (! $plain) {
            return null;
        }

        return PersonalAccessToken::findToken($plain)?->tokenable;
    }

    private function serializeVendor(?Vendor $vendor, bool $following): ?array
    {
        if (! $vendor) {
            return null;
        }

        return [
            'id' => $vendor->id,
            'store_name' => $vendor->store_name,
            'slug' => $vendor->slug,
            'logo' => $vendor->logo,
            'banner' => $vendor->banner,
            'description' => $vendor->description,
            'following' => $following,
            'url' => url('/stores/'.$vendor->id),
        ];
    }
}
