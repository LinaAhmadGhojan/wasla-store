<?php

namespace App\Services\Push;

use App\Models\DeviceToken;
use App\Models\DeviceSighting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging (HTTP v1).
 * يحتاج ملف Service Account من Firebase Console.
 */
class FcmPushService
{
    public function enabled(): bool
    {
        return (bool) config('firebase.enabled')
            && filled(config('firebase.project_id'))
            && is_readable((string) config('firebase.credentials'));
    }

    public function register(User $user, string $token, ?string $platform = null, ?string $deviceName = null, string $app = 'customer'): DeviceToken
    {
        $token = trim($token);

        // سجل apparition للجهاز (لكشف أكثر من حساب على نفس الجهاز)
        DeviceSighting::record($user, $token, $platform, $deviceName, $app ?: 'customer');

        // توكن الدفع الحالي يبقى لمستخدم واحد (FCM)
        DeviceToken::query()->where('token', $token)->where('user_id', '!=', $user->id)->delete();

        return DeviceToken::query()->updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'device_name' => $deviceName,
                'app' => $app ?: 'customer',
                'last_used_at' => now(),
            ]
        );
    }

    public function unregister(User $user, string $token): void
    {
        DeviceToken::query()
            ->where('user_id', $user->id)
            ->where('token', $token)
            ->delete();
    }

    public function sendToUser(User $user, string $title, string $body, array $data = [], ?string $app = 'customer'): array
    {
        if (! $this->enabled()) {
            Log::info('FCM skipped: not configured', ['user_id' => $user->id, 'title' => $title]);

            return ['sent' => 0, 'skipped' => true];
        }

        $query = DeviceToken::query()->where('user_id', $user->id);
        if ($app) {
            $query->where('app', $app);
        }

        $tokens = $query->pluck('token')->all();
        if ($tokens === []) {
            return ['sent' => 0, 'skipped' => false, 'reason' => 'no_tokens'];
        }

        $sent = 0;
        $failed = [];

        foreach ($tokens as $token) {
            $ok = $this->sendToToken($token, $title, $body, $data);
            if ($ok) {
                $sent++;
                DeviceToken::query()->where('token', $token)->update(['last_used_at' => now()]);
            } else {
                $failed[] = $token;
            }
        }

        return ['sent' => $sent, 'failed' => count($failed)];
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            $accessToken = $this->accessToken();
            $projectId = config('firebase.project_id');

            $stringData = [];
            foreach ($data as $k => $v) {
                $stringData[(string) $k] = is_scalar($v) || $v === null ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
            }

            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $stringData,
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => config('firebase.android_channel', 'wasla_orders'),
                            'sound' => 'default',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->timeout(15)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            if ($response->successful()) {
                return true;
            }

            $error = $response->json('error.status') ?? $response->body();
            Log::warning('FCM send failed', [
                'status' => $response->status(),
                'error' => $error,
                'token_tail' => substr($token, -12),
            ]);

            // توكن باطل → احذفه
            if (in_array($response->status(), [404, 400], true)
                || str_contains((string) $error, 'UNREGISTERED')
                || str_contains((string) $error, 'INVALID_ARGUMENT')) {
                DeviceToken::query()->where('token', $token)->delete();
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('FCM exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function accessToken(): string
    {
        return Cache::remember('firebase_fcm_access_token', 3000, function () {
            $path = (string) config('firebase.credentials');
            $json = json_decode((string) file_get_contents($path), true);
            if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
                throw new \RuntimeException('ملف Firebase credentials غير صالح.');
            }

            $now = time();
            $claim = [
                'iss' => $json['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $jwt = $this->encodeJwt($claim, $json['private_key']);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful() || ! $response->json('access_token')) {
                throw new \RuntimeException('تعذّر الحصول على Firebase access token: '.$response->body());
            }

            return (string) $response->json('access_token');
        });
    }

    private function encodeJwt(array $payload, string $privateKey): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $segments = [
            $this->b64(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->b64(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];
        $signingInput = implode('.', $segments);

        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new \RuntimeException('مفتاح Firebase الخاص غير صالح.');
        }

        $signature = '';
        $ok = openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new \RuntimeException('فشل توقيع JWT لـ Firebase.');
        }

        $segments[] = $this->b64($signature);

        return implode('.', $segments);
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
