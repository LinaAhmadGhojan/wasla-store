<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ProductImageCatalog;
use Illuminate\Console\Command;

class RefreshProductImages extends Command
{
    protected $signature = 'products:refresh-images
                            {--only-picsum : Update only picsum/placeholder images}
                            {--only-remote : Update only external (unsplash) URLs}';

    protected $description = 'Assign realistic category-matched product images (no random people photos)';

    public function handle(): int
    {
        $query = Product::with('category');
        $onlyPicsum = (bool) $this->option('only-picsum');
        $onlyRemote = (bool) $this->option('only-remote');

        if ($onlyPicsum) {
            $query->where(function ($q) {
                $q->whereNull('image')
                    ->orWhere('image', '')
                    ->orWhere('image', 'like', '%picsum.photos%')
                    ->orWhere('image', 'like', '%placeholder%');
            });
        } elseif ($onlyRemote) {
            $query->where(function ($q) {
                $q->where('image', 'like', 'http%')
                    ->orWhere('image', 'like', '%unsplash.com%');
            });
        }

        $updated = 0;

        $query->orderBy('id')->each(function (Product $product) use (&$updated) {
            $slug = $product->category?->slug;
            $image = ProductImageCatalog::urlFor($slug, (int) $product->id);

            if ($product->image !== $image) {
                $product->update(['image' => $image]);
                $updated++;
            }
        });

        $this->info("Updated {$updated} product image(s).");

        return self::SUCCESS;
    }
}
