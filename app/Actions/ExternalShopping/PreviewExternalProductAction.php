<?php

namespace App\Actions\ExternalShopping;

use App\Services\ExternalShopping\ExternalProductImportEngine;

class PreviewExternalProductAction
{
    public function __construct(private ExternalProductImportEngine $engine)
    {
    }

    public function execute(string $url, array $manual = []): array
    {
        return $this->engine->preview($url, $manual);
    }
}
