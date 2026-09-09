<?php

namespace App\Actions\Catalog;

use App\Models\Attribute;
use App\Models\AttributeValue;

class AddAttributeValueAction
{
    public function execute(Attribute $attribute, array $data): AttributeValue
    {
        return $attribute->values()->create($data);
    }
}
