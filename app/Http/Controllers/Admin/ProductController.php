<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\SyncProductCatalogAction;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\WhatsappGroup;
use App\Services\CurrencyService;
use App\Services\Whatsapp\WhatsappGatewayClient;
use App\Services\Whatsapp\WhatsappMessageBuilder;
use App\Services\Whatsapp\WhatsappPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request, WhatsappPublisher $publisher)
    {
            $query = Product::with(['vendor', 'category.parent', 'images'])->withCount('variants')->orderBy('created_at', 'desc');

        if ($search = $request->query('q')) {
            $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
        }

        $group = WhatsappGroup::defaultGroup();

        return view('admin.products.index', [
            'products' => $query->paginate(15)->withQueryString(),
            'search' => $search,
            'group' => $group,
            'gatewayReady' => $publisher->gatewayReady(),
            'recipientPhone' => $publisher->recipientPhone($group),
            'canSendToGroup' => $publisher->canSendToGroup($group),
            'defaultSendTarget' => config('whatsapp.default_send_target', 'phone'),
            'bulkMaxProducts' => max(1, (int) config('whatsapp.bulk_max_products', 8)),
        ]);
    }

    public function create()
    {
        return view('admin.products.create', [
            'product' => new Product(),
            'vendors' => Vendor::orderBy('store_name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_id' => 'required|exists:stores,id',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'is_featured' => 'sometimes|boolean',
            'is_active' => 'required|boolean',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_featured'] = filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN);

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product, CurrencyService $currency)
    {
        $product->load(['vendor', 'category.parent', 'brand', 'images', 'variants']);

        return view('admin.products.edit', [
            'product' => $product,
            'colorCatalog' => $product->colorCatalog(),
            'vendors' => Vendor::orderBy('store_name')->get(),
            'categories' => Category::with('parent.parent')->orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'currency' => $currency,
            'exchangeRate' => $currency->currentAedToSypRate(),
            'productFee' => $currency->productFeeAed(),
            'accessoryFee' => $currency->accessoryFeeAed(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $this->normalizeMoneyFields($request);

        $data = $request->validate([
            'vendor_id' => 'required|exists:stores,id',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'pricing_type' => 'nullable|in:product,accessory',
            'is_featured' => 'sometimes|boolean',
            'is_active' => 'required|boolean',
            'allow_return' => 'sometimes|boolean',
            'allow_exchange' => 'sometimes|boolean',
            'return_days' => 'nullable|integer|min:1|max:90',
            'exchange_days' => 'nullable|integer|min:1|max:90',
            'source_url' => 'nullable|string|max:2000',
            'source_external_id' => 'nullable|string|max:80',
            'specs' => 'nullable|array',
            'specs.*.label' => 'nullable|string|max:120',
            'specs.*.value' => 'nullable|string|max:255',
            'size_chart' => 'nullable|array',
            'size_chart.unit' => 'nullable|string|max:10',
            'size_chart.note' => 'nullable|string|max:500',
            'size_chart.columns' => 'nullable|array',
            'size_chart.columns.*' => 'nullable|string|max:80',
            'size_chart.rows' => 'nullable|array',
            'size_chart.rows.*' => 'nullable|array',
            'size_chart.rows.*.*' => 'nullable|string|max:40',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['is_featured'] = filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN);
        $data['allow_return'] = $request->boolean('allow_return');
        $data['allow_exchange'] = $request->boolean('allow_exchange');
        $data['return_days'] = $request->filled('return_days') ? (int) $request->input('return_days') : null;
        $data['exchange_days'] = $request->filled('exchange_days') ? (int) $request->input('exchange_days') : null;
        $data['specs'] = collect($data['specs'] ?? [])
            ->map(fn ($row) => [
                'label' => trim((string) ($row['label'] ?? '')),
                'value' => trim((string) ($row['value'] ?? '')),
            ])
            ->filter(fn ($row) => $row['label'] !== '' || $row['value'] !== '')
            ->values()
            ->all();
        $columns = array_values(array_filter(array_map('trim', $data['size_chart']['columns'] ?? []), fn ($c) => $c !== ''));
        $rows = [];
        foreach ($data['size_chart']['rows'] ?? [] as $row) {
            $cells = array_values($row ?? []);
            if (implode('', $cells) === '') {
                continue;
            }
            $rows[] = $cells;
        }
        $data['size_chart'] = [
            'unit' => $data['size_chart']['unit'] ?? 'cm',
            'note' => trim((string) ($data['size_chart']['note'] ?? '')),
            'columns' => $columns,
            'rows' => $rows,
        ];
        if ($rows === []) {
            $data['size_chart'] = null;
        }

        $product->update(collect($data)->only([
            'vendor_id', 'category_id', 'brand_id', 'name', 'slug', 'description',
            'specs', 'size_chart',
            'price', 'sale_price', 'pricing_type', 'is_featured', 'is_active',
            'allow_return', 'allow_exchange', 'return_days', 'exchange_days',
            'source_url', 'source_external_id',
        ])->all());

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'تم حفظ بيانات المنتج.');
    }

    public function updateColor(Request $request, Product $product, SyncProductCatalogAction $sync, CurrencyService $currency)
    {
        $this->normalizeColorMoney($request);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'key' => 'nullable|string|max:80',
            'original_key' => 'nullable|string|max:80',
            'hex' => 'nullable|string|max:20',
            'sizes' => 'nullable|array',
            'sizes.*.id' => 'nullable|integer',
            'sizes.*.size_key' => 'nullable|string|max:80',
            'sizes.*.size_name' => 'required_with:sizes|string|max:80',
            'sizes.*.sku' => 'nullable|string|max:80',
            'sizes.*.price' => 'required_with:sizes|numeric|min:0',
            'sizes.*.stock_qty' => 'required_with:sizes|integer|min:0',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'integer|exists:product_images,id',
            'images' => 'nullable|array',
            'images.*' => 'image|max:5120',
        ]);

        $files = $request->file('images', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }

        $colorKey = $sync->syncOneColor($product, [
            'name' => $data['name'],
            'key' => $data['key'] ?? '',
            'original_key' => $data['original_key'] ?? '',
            'hex' => $data['hex'] ?? null,
            'sizes' => $data['sizes'] ?? [],
        ], $files, $data['delete_images'] ?? []);

        $product->refresh()->load(['images', 'variants', 'category.parent']);

        return response()->json([
            'ok' => true,
            'message' => 'تم حفظ لون «'.$data['name'].'».',
            'color' => $this->serializeColorGroup($product, $colorKey, $currency),
        ]);
    }

    public function destroyColor(Request $request, Product $product, SyncProductCatalogAction $sync)
    {
        $data = $request->validate([
            'key' => 'required|string|max:80',
        ]);

        $sync->deleteColor($product, $data['key']);

        return response()->json([
            'ok' => true,
            'message' => 'تم حذف اللون.',
        ]);
    }

    private function serializeColorGroup(Product $product, string $key, CurrencyService $currency): ?array
    {
        foreach ($product->colorCatalog() as $group) {
            if ($group['key'] !== $key) {
                continue;
            }

            return [
                'key' => $group['key'],
                'name' => $group['name'],
                'hex' => $group['hex'],
                'images' => $group['images']->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => $image->url,
                ])->values()->all(),
                'sizes' => collect($group['sizes'])->map(function ($row) use ($product, $currency) {
                    $variant = $row['variant'];

                    return [
                        'id' => $variant->id,
                        'size_name' => $row['size_name'],
                        'size_key' => $row['size_key'],
                        'sku' => $variant->sku,
                        'price' => (float) $variant->price,
                        'stock_qty' => (int) $variant->stock_qty,
                        'price_syp' => $currency->formatCustomer((float) $variant->price, $product->pricingKind()),
                    ];
                })->values()->all(),
            ];
        }

        return null;
    }

    private function normalizeColorMoney(Request $request): void
    {
        $strip = fn ($v) => $v === null || $v === '' ? $v : str_replace([',', '،', ' '], '', (string) $v);
        $sizes = $request->input('sizes', []);
        if (! is_array($sizes)) {
            return;
        }

        foreach ($sizes as $index => $row) {
            if (isset($row['price'])) {
                $sizes[$index]['price'] = $strip($row['price']);
            }
        }

        $request->merge(['sizes' => $sizes]);
    }

    private function normalizeMoneyFields(Request $request): void
    {
        $strip = fn ($v) => $v === null || $v === '' ? $v : str_replace([',', '،', ' '], '', (string) $v);

        $request->merge([
            'price' => $strip($request->input('price')),
            'sale_price' => $strip($request->input('sale_price')),
            'pricing_type' => $request->input('pricing_type') ?: null,
        ]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }

    public function whatsappShare(Product $product, WhatsappMessageBuilder $builder, WhatsappPublisher $publisher)
    {
        $product->load(['vendor', 'category.parent', 'variants']);
        $group = WhatsappGroup::defaultGroup();
        $message = $builder->fromProduct($product, $group);

        return view('admin.products.whatsapp-share', [
            'product' => $product,
            'group' => $group,
            'message' => $message,
            'recipientPhone' => $publisher->recipientPhone($group),
            'shareUrl' => $builder->shareUrl($message, $group),
            'gatewayReady' => $publisher->gatewayReady(),
            'gatewayError' => app(WhatsappGatewayClient::class)->status()['error'] ?? null,
            'canSendToGroup' => $publisher->canSendToGroup($group),
            'defaultSendTarget' => config('whatsapp.default_send_target', 'phone'),
        ]);
    }

    public function whatsappSend(Product $product, Request $request, WhatsappPublisher $publisher)
    {
        set_time_limit(120);

        $validated = $request->validate([
            'target' => 'required|in:phone,group',
        ]);

        try {
            $publisher->sendProduct($product, $validated['target'], includeImage: true);

            $label = $validated['target'] === 'group'
                ? 'مجموعة «'.(WhatsappGroup::defaultGroup()?->name ?? 'واتساب').'»'
                : 'رقم +'.$publisher->recipientPhone();

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'تم إرسال «'.$product->name.'» (مع الصورة) إلى '.$label.' — منشور ✓');
        } catch (\Throwable $e) {
            $hint = 'فشل الإرسال.';
            $raw = $e->getMessage();
            if (str_contains($raw, 'not_ready')) {
                $hint = 'واتساب غير متصل. اربطي الحساب من صفحة الاتصال وامسحي QR.';
            } elseif (preg_match('/detached|session_detached|Target closed|Session closed|Execution context|getChat|getChats/i', $raw)) {
                $hint = 'جلسة واتساب انقطعت. افتحي صفحة الاتصال، وإذا ما اشتغل اضغطي إعادة الربط وامسحي QR من جديد ثم أرسلي.';
            } else {
                $hint = $raw;
            }

            return redirect()
                ->route('admin.products.index')
                ->with('error', 'فشل إرسال «'.$product->name.'»: '.$hint);
        }
    }

    public function whatsappBulkSend(Request $request, WhatsappPublisher $publisher)
    {
        set_time_limit(120);

        $max = max(1, (int) config('whatsapp.bulk_max_products', 8));

        $validated = $request->validate([
            'product_ids' => 'required|array|min:1|max:'.$max,
            'product_ids.*' => 'integer|exists:products,id',
            'target' => 'required|in:phone,group',
        ], [
            'product_ids.max' => "الحد الأقصى {$max} منتجات بمرة واحدة (لتجنب timeout). أرسلي على دفعات.",
        ]);

        $result = $publisher->sendMany(
            $validated['product_ids'],
            $validated['target'],
            includeImages: false,
        );

        if ($result['sent'] === 0) {
            return redirect()->route('admin.products.index')
                ->with('error', 'ما انبعت ولا منتج. '.implode(' | ', $result['failed']));
        }

        $message = "تم إرسال {$result['sent']} منتج وعلّمناهم «منشور».";
        if ($result['failed'] !== []) {
            $message .= ' فشل: '.count($result['failed']).' — '.implode(' | ', array_slice($result['failed'], 0, 3));
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', $message);
    }
}
