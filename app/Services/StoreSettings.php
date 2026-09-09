<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class StoreSettings
{
    private string $path = 'settings/store.json';

    /** @return array<string, array{enabled: bool, eyebrow: string, title: string}> */
    public static function defaultHomeSections(): array
    {
        return [
            'trending' => [
                'enabled' => true,
                'eyebrow' => 'الأكثر طلباً',
                'title' => 'وصلات مختارة',
            ],
            'new_arrivals' => [
                'enabled' => true,
                'eyebrow' => 'جديد',
                'title' => 'وصل حديثاً',
            ],
            'recommended' => [
                'enabled' => true,
                'eyebrow' => 'لكِ',
                'title' => 'مقترح لكِ',
            ],
            'top_rated' => [
                'enabled' => true,
                'eyebrow' => 'تقييمات',
                'title' => 'الأعلى تقييماً',
            ],
            'recently_viewed' => [
                'enabled' => true,
                'eyebrow' => 'تاريخك',
                'title' => 'شاهدتِه مؤخراً',
            ],
            'favorites' => [
                'enabled' => true,
                'eyebrow' => 'المفضلة',
                'title' => 'مفضلتك',
            ],
        ];
    }

    public function all(): array
    {
        if (Storage::disk('local')->exists($this->path)) {
            return json_decode(Storage::disk('local')->get($this->path), true) ?? [];
        }

        return [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    public function put(array $data): void
    {
        Storage::disk('local')->put(
            $this->path,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    /**
     * @return array<string, array{enabled: bool, eyebrow: string, title: string}>
     */
    public function homeSections(): array
    {
        $defaults = self::defaultHomeSections();
        $saved = $this->get('home_sections', []) ?: [];
        $merged = [];

        foreach ($defaults as $key => $def) {
            $row = is_array($saved[$key] ?? null) ? $saved[$key] : [];
            $merged[$key] = [
                'enabled' => array_key_exists('enabled', $row) ? (bool) $row['enabled'] : $def['enabled'],
                'eyebrow' => trim((string) ($row['eyebrow'] ?? $def['eyebrow'])) ?: $def['eyebrow'],
                'title' => trim((string) ($row['title'] ?? $def['title'])) ?: $def['title'],
            ];
        }

        return $merged;
    }

    public function homeSectionLimit(): int
    {
        $n = (int) $this->get('home_section_limit', 8);

        return max(4, min(24, $n ?: 8));
    }

    /** @return array{text_syp: int, with_photo_syp: int} */
    public function reviewRewards(): array
    {
        $text = (int) $this->get('review_reward_text_syp', 500);
        $photo = (int) $this->get('review_reward_photo_syp', 1000);

        return [
            'text_syp' => max(0, $text ?: 500),
            'with_photo_syp' => max(0, $photo ?: 1000),
        ];
    }

    public function reviewRewardFor(?string $body, bool $hasImage): int
    {
        $rewards = $this->reviewRewards();
        if ($hasImage) {
            return $rewards['with_photo_syp'];
        }

        return $rewards['text_syp'];
    }

    public function returnDaysDefault(): int
    {
        return max(1, (int) ($this->get('return_days_default', 7) ?: 7));
    }

    public function exchangeDaysDefault(): int
    {
        return max(1, (int) ($this->get('exchange_days_default', 7) ?: 7));
    }
}
