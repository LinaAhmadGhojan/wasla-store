<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    protected $fillable = [
        'query',
        'hits',
        'last_searched_at',
    ];

    protected $casts = [
        'last_searched_at' => 'datetime',
    ];

    public static function record(string $raw): void
    {
        $q = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $raw) ?? ''));
        if ($q === '' || mb_strlen($q) < 2) {
            return;
        }
        if (mb_strlen($q) > 180) {
            $q = mb_substr($q, 0, 180);
        }

        $row = static::query()->firstOrNew(['query' => $q]);
        $row->hits = ((int) $row->hits) + 1;
        $row->last_searched_at = now();
        $row->save();
    }
}
