<?php

namespace App\Support;

class SheinUrlParser
{
    /**
     * @return array{goods_id: ?string, sku: ?string, main_attr: ?string, attr_id: ?string}
     */
    public static function parse(string $url): array
    {
        $goodsId = null;
        if (preg_match('/-p-(\d+)(?:\.html)?/i', $url, $m)) {
            $goodsId = $m[1];
        } elseif (preg_match('/[?&]goods[_-]?id=(\d+)/i', $url, $m)) {
            $goodsId = $m[1];
        }

        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $sku = trim((string) ($query['sku'] ?? $query['sku_code'] ?? $query['goods_sn'] ?? ''));
        $sku = $sku !== '' ? $sku : null;

        $mainAttr = trim((string) ($query['main_attr'] ?? ''));
        $mainAttr = $mainAttr !== '' ? $mainAttr : null;

        $attrId = null;
        if ($mainAttr && preg_match('/(?:^|_)(\d+)$/', $mainAttr, $m)) {
            $attrId = $m[1];
        }

        return [
            'goods_id' => $goodsId,
            'sku' => $sku,
            'main_attr' => $mainAttr,
            'attr_id' => $attrId,
        ];
    }
}
