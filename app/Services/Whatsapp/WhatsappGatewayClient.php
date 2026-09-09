<?php

namespace App\Services\Whatsapp;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class WhatsappGatewayClient
{
    public function isEnabled(): bool
    {
        return (bool) config('whatsapp.gateway_url');
    }

    public function status(): array
    {
        if (! $this->isEnabled()) {
            return [
                'ready' => false,
                'qr' => null,
                'error' => 'gateway_disabled',
            ];
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(8)
                ->get($this->url('/status'));

            return [
                'ready' => (bool) $response->json('ready'),
                'qr' => $response->json('qr'),
                'error' => $response->json('error'),
                'initializing' => (bool) $response->json('initializing'),
                'authenticated' => (bool) $response->json('authenticated'),
                'loading_percent' => $response->json('loadingPercent'),
            ];
        } catch (\Throwable $e) {
            return [
                'ready' => false,
                'qr' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /** @return list<array{id: string, name: string}> */
    public function listGroups(): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->get($this->url('/groups'));

            if (! $response->successful()) {
                return [];
            }

            return $response->json('groups') ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array{id: string, name: string|null}|null */
    public function resolveGroupFromInvite(string $inviteLink): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(45)
                ->post($this->url('/groups/resolve-invite'), [
                    'inviteLink' => $inviteLink,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $chatId = $response->json('chatId');
            if (! is_string($chatId) || $chatId === '') {
                return null;
            }

            return [
                'id' => $chatId,
                'name' => $response->json('name'),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function sendToPhone(string $phone, string $message, array $imagePayload = []): void
    {
        $this->send($message, phone: $phone, imagePayload: $imagePayload);
    }

    public function sendToGroup(string $groupChatId, string $message, array $imagePayload = []): void
    {
        $this->send($message, groupChatId: $groupChatId, imagePayload: $imagePayload);
    }

    /**
     * @param  array{image?: string, localImage?: string}  $imagePayload
     */
    public function send(
        string $message,
        ?string $phone = null,
        ?string $groupChatId = null,
        array $imagePayload = [],
    ): void {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('WhatsApp gateway is not configured.');
        }

        $payload = ['message' => $message];

        if ($groupChatId) {
            $payload['target'] = 'group';
            $payload['groupChatId'] = $groupChatId;
        } elseif ($phone) {
            $payload['target'] = 'phone';
            $payload['phone'] = preg_replace('/\D+/', '', $phone);
        } else {
            throw new \RuntimeException('Recipient phone or group chat id is required.');
        }

        if (! empty($imagePayload['localImage'])) {
            $payload['localImage'] = $imagePayload['localImage'];
        } elseif (! empty($imagePayload['image']) && preg_match('/^https?:\/\//i', $imagePayload['image'])) {
            $payload['image'] = $imagePayload['image'];
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(90)
                ->post($this->url('/send'), $payload);

            if (! $response->successful()) {
                throw new \RuntimeException((string) ($response->json('error') ?: 'send_failed'));
            }
        } catch (RequestException $e) {
            $error = $e->response?->json('error') ?? $e->getMessage();
            throw new \RuntimeException((string) $error, previous: $e);
        }
    }

    public function resetSession(): void
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('WhatsApp gateway is not configured.');
        }

        Http::withHeaders($this->headers())
            ->timeout(15)
            ->post($this->url('/reset'));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('whatsapp.gateway_url'), '/').$path;
    }

    private function headers(): array
    {
        return [
            'X-Gateway-Token' => (string) config('whatsapp.gateway_token'),
            'Accept' => 'application/json',
        ];
    }
}
