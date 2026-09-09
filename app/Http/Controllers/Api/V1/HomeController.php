<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Wishlist;
use App\Services\StoreSettings;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class HomeController extends Controller
{
    public function __invoke(Request $request, StoreSettings $settings)
    {
        $sectionsCfg = $settings->homeSections();
        $limit = $settings->homeSectionLimit();
        $user = $request->user('sanctum') ?? $this->userFromBearer($request);

        $out = [];
        foreach ($sectionsCfg as $key => $cfg) {
            if (! ($cfg['enabled'] ?? false)) {
                continue;
            }

            $section = [
                'key' => $key,
                'eyebrow' => $cfg['eyebrow'],
                'title' => $cfg['title'],
                'client_fill' => in_array($key, ['recently_viewed'], true),
                'requires_auth' => $key === 'favorites',
                'products' => [],
                'empty_hint' => null,
            ];

            if ($key === 'recently_viewed') {
                $section['empty_hint'] = 'تصفّحي المنتجات لتظهر هنا.';
                $out[] = $section;
                continue;
            }

            if ($key === 'favorites') {
                if (! $user) {
                    $section['empty_hint'] = 'سجّلي دخولك لعرض المفضلة.';
                    $section['login_required'] = true;
                } else {
                    $ids = Wishlist::query()
                        ->where('user_id', $user->id)
                        ->latest()
                        ->limit($limit)
                        ->pluck('product_id');
                    $section['products'] = $this->productsByIds($ids->all());
                    $section['empty_hint'] = $ids->isEmpty() ? 'ما في منتجات بمفضلتك بعد. اضغطي ♡ على أي منتج.' : null;
                }
                $out[] = $section;
                continue;
            }

            $section['products'] = $this->catalogSection($key, $limit);
            $out[] = $section;
        }

        return response()->json([
            'sections' => $out,
            'limit' => $limit,
        ]);
    }

    /** @return list<\App\Models\Product> */
    private function catalogSection(string $key, int $limit): array
    {
        $query = Product::with(['vendor', 'category.parent', 'brand'])
            ->where('is_active', true);

        switch ($key) {
            case 'trending':
                $sales = OrderItem::query()
                    ->selectRaw('product_id, SUM(quantity) as qty')
                    ->whereNotNull('product_id')
                    ->groupBy('product_id')
                    ->orderByDesc('qty')
                    ->limit($limit)
                    ->pluck('product_id');
                if ($sales->isNotEmpty()) {
                    $products = $this->productsByIds($sales->all());
                    if (count($products) >= 4) {
                        return $products;
                    }
                }
                $query->orderByDesc('is_featured')->orderByDesc('created_at');
                break;
            case 'new_arrivals':
                $query->orderByDesc('created_at');
                break;
            case 'recommended':
                $query->orderByDesc('is_featured')->orderByDesc('created_at');
                break;
            case 'top_rated':
                $query->orderByDesc('rating')->orderByDesc('total_reviews')->orderByDesc('created_at');
                break;
            default:
                $query->orderByDesc('created_at');
        }

        return $query->limit($limit)->get()->all();
    }

    /** @param list<int|string> $ids */
    private function productsByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        $products = Product::with(['vendor', 'category.parent', 'brand'])
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $ordered = [];
        foreach ($ids as $id) {
            if ($products->has($id)) {
                $ordered[] = $products->get($id);
            }
        }

        return $ordered;
    }

    private function userFromBearer(Request $request)
    {
        $plain = $request->bearerToken();
        if (! $plain) {
            return null;
        }
        $access = PersonalAccessToken::findToken($plain);

        return $access?->tokenable;
    }
}
