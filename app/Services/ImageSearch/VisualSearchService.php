<?php

namespace App\Services\ImageSearch;

use App\Models\Product;
use App\Models\ProductImageFeature;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VisualSearchService
{
    public function __construct(
        private readonly LocalFeatureExtractor $extractor,
        private readonly ImagePathResolver $paths,
    ) {}

    /**
     * @return list<array{product: Product, score: float, match: string}>
     */
    public function searchByUpload(UploadedFile $file, ?int $limit = null): array
    {
        $limit = $limit ?? (int) config('image_search.limit', 24);
        $driver = config('image_search.driver', 'local');

        if ($driver === 'clip') {
            try {
                return $this->searchViaClip($file, $limit);
            } catch (RuntimeException $e) {
                // CLIP down / unreachable → same UX with local GD index
                if (str_contains($e->getMessage(), 'CLIP')) {
                    return $this->searchLocal($file, $limit);
                }
                throw $e;
            }
        }

        return $this->searchLocal($file, $limit);
    }

    /**
     * @return list<array{product: Product, score: float, match: string}>
     */
    private function searchLocal(UploadedFile $file, int $limit): array
    {
        $query = $this->extractor->extractFromUpload($file->getRealPath());
        $minScore = (float) config('image_search.min_score', 0.45);
        $wStruct = (float) config('image_search.local.structure_weight', 0.55);
        $wColor = (float) config('image_search.local.color_weight', 0.45);

        $features = ProductImageFeature::query()->with('product.brand', 'product.category', 'product.vendor')->get();
        if ($features->isEmpty()) {
            throw new RuntimeException('لا يوجد فهرس صور بعد. شغّلي: php artisan products:index-visual');
        }

        /** @var array<int, float> $bestByProduct */
        $bestByProduct = [];

        foreach ($features as $row) {
            if (! $row->product || ! $row->product->is_active) {
                continue;
            }

            $hamming = LocalFeatureExtractor::hammingDistance($query['dhash'], $row->dhash);
            $structure = 1.0 - min(64, $hamming) / 64.0;
            $color = LocalFeatureExtractor::cosineSimilarity($query['color_hist'], $row->color_hist ?? []);
            $score = ($wStruct * $structure) + ($wColor * $color);

            $pid = (int) $row->product_id;
            if (! isset($bestByProduct[$pid]) || $score > $bestByProduct[$pid]) {
                $bestByProduct[$pid] = $score;
            }
        }

        arsort($bestByProduct);

        $results = [];
        foreach ($bestByProduct as $productId => $score) {
            if ($score < $minScore) {
                continue;
            }
            $product = $features->firstWhere('product_id', $productId)?->product;
            if (! $product) {
                continue;
            }
            $results[] = [
                'product' => $product,
                'score' => round($score, 4),
                'match' => $this->matchLabel($score),
            ];
            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    /**
     * @return list<array{product: Product, score: float, match: string}>
     */
    private function searchViaClip(UploadedFile $file, int $limit): array
    {
        $base = rtrim((string) config('image_search.clip.base_url'), '/');
        $timeout = (int) config('image_search.clip.timeout', 60);

        try {
            $response = Http::timeout($timeout)
                ->attach('image', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post("{$base}/search", [
                    'limit' => $limit,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('خدمة CLIP غير متاحة على '.$base, 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('CLIP search failed: '.$response->body());
        }

        $hits = $response->json('results') ?? [];
        $productIds = collect($hits)->pluck('product_id')->filter()->unique()->values();
        $products = Product::with(['brand', 'category', 'vendor'])
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $results = [];
        foreach ($hits as $hit) {
            $pid = (int) ($hit['product_id'] ?? 0);
            $product = $products->get($pid);
            if (! $product) {
                continue;
            }
            $score = (float) ($hit['score'] ?? 0);
            $minScore = (float) config('image_search.clip.min_score', 0.22);
            if ($score < $minScore) {
                continue;
            }
            $results[] = [
                'product' => $product,
                'score' => round($score, 4),
                'match' => $this->matchLabelClip($score),
            ];
        }

        return $results;
    }

    private function matchLabel(float $score): string
    {
        if ($score >= 0.90) {
            return 'مطابق تقريباً';
        }
        if ($score >= 0.78) {
            return 'قريب جداً';
        }
        if ($score >= 0.62) {
            return 'مشابه';
        }

        return 'قريب';
    }

    /** CLIP cosine scores are typically lower than local combined scores. */
    private function matchLabelClip(float $score): string
    {
        if ($score >= 0.88) {
            return 'مطابق تقريباً';
        }
        if ($score >= 0.78) {
            return 'قريب جداً';
        }
        if ($score >= 0.65) {
            return 'مشابه';
        }

        return 'قريب';
    }

    /**
     * Push absolute product image paths to the CLIP microservice /index.
     *
     * @return array{indexed: int, errors: int, sent: int}
     */
    public function rebuildClipIndex(?callable $onProgress = null): array
    {
        $base = rtrim((string) config('image_search.clip.base_url'), '/');
        $timeout = max(120, (int) config('image_search.clip.timeout', 60));

        $items = [];
        Product::query()
            ->where('is_active', true)
            ->with('images')
            ->orderBy('id')
            ->chunkById(50, function (Collection $products) use (&$items, $onProgress) {
                foreach ($products as $product) {
                    foreach ($this->paths->collectProductImagePaths($product) as $relPath) {
                        $abs = $this->paths->resolveAbsolute($relPath);
                        if (! $abs) {
                            continue;
                        }
                        $items[] = [
                            'product_id' => (int) $product->id,
                            'path' => $abs,
                        ];
                        if ($onProgress) {
                            $onProgress($product->id, $abs);
                        }
                    }
                }
            });

        if (! $items) {
            throw new RuntimeException('لا توجد صور منتجات لفهرستها.');
        }

        try {
            $response = Http::timeout($timeout)->post("{$base}/index", [
                'items' => $items,
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'خدمة CLIP غير متاحة على '.$base.' — شغّلي: cd image-search-service && .\\venv\\Scripts\\uvicorn.exe main:app --host 127.0.0.1 --port 3010',
                0,
                $e
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException('فشل فهرسة CLIP: '.$response->body());
        }

        $json = $response->json() ?? [];

        return [
            'indexed' => (int) ($json['indexed'] ?? 0),
            'errors' => (int) ($json['errors'] ?? 0),
            'sent' => count($items),
        ];
    }

    /**
     * Rebuild local feature index for active products.
     *
     * @return array{indexed: int, skipped: int, errors: int}
     */
    public function rebuildLocalIndex(?callable $onProgress = null): array
    {
        $stats = ['indexed' => 0, 'skipped' => 0, 'errors' => 0];

        Product::query()
            ->where('is_active', true)
            ->with('images')
            ->orderBy('id')
            ->chunkById(50, function (Collection $products) use (&$stats, $onProgress) {
                foreach ($products as $product) {
                    $paths = $this->paths->collectProductImagePaths($product);
                    if (! $paths) {
                        $stats['skipped']++;
                        continue;
                    }

                    // Keep only current paths for this product.
                    ProductImageFeature::query()
                        ->where('product_id', $product->id)
                        ->whereNotIn('image_path', $paths)
                        ->delete();

                    foreach ($paths as $relPath) {
                        $abs = $this->paths->resolveAbsolute($relPath);
                        if (! $abs) {
                            $stats['skipped']++;
                            continue;
                        }
                        try {
                            $feat = $this->extractor->extractFromPath($abs);
                            ProductImageFeature::query()->updateOrCreate(
                                [
                                    'product_id' => $product->id,
                                    'image_path' => $relPath,
                                ],
                                [
                                    'dhash' => $feat['dhash'],
                                    'color_hist' => $feat['color_hist'],
                                ]
                            );
                            $stats['indexed']++;
                            if ($onProgress) {
                                $onProgress($product->id, $relPath);
                            }
                        } catch (\Throwable $e) {
                            $stats['errors']++;
                            report($e);
                        }
                    }
                }
            });

        return $stats;
    }
}
