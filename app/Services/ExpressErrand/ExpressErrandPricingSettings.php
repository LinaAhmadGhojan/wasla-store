<?php

namespace App\Services\ExpressErrand;

use Illuminate\Support\Facades\Storage;

class ExpressErrandPricingSettings
{
    private string $path = 'settings/express_errand_pricing.json';

    public function all(): array
    {
        if (Storage::disk('local')->exists($this->path)) {
            $saved = json_decode(Storage::disk('local')->get($this->path), true);

            return is_array($saved) ? $this->mergeDefaults($saved) : $this->defaults();
        }

        return $this->defaults();
    }

    public function put(array $data): void
    {
        Storage::disk('local')->put(
            $this->path,
            json_encode($this->mergeDefaults($data), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    public function defaults(): array
    {
        return config('express_errand_pricing', []);
    }

    private function mergeDefaults(array $saved): array
    {
        $def = $this->defaults();

        return array_replace_recursive($def, $saved);
    }
}
