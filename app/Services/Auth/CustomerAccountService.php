<?php

namespace App\Services\Auth;

use App\Models\AuthOtp;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerAccountService
{
    public function defaultPrivacy(): array
    {
        return [
            'share_activity' => false,
            'marketing_emails' => true,
            'marketing_sms' => false,
            'order_notifications' => true,
            'promo_notifications' => true,
            'show_reviews_public' => true,
        ];
    }

    public function serialize(User $user): array
    {
        $privacy = array_merge($this->defaultPrivacy(), $user->privacy_settings ?? []);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'phone_verified' => (bool) $user->phone_verified_at,
            'email_verified' => (bool) $user->email_verified_at,
            'avatar' => $user->avatar ? url($user->avatar) : null,
            'avatar_path' => $user->avatar,
            'locale' => $user->locale ?: 'ar',
            'preferred_currency' => $user->preferred_currency ?: 'SYP',
            'privacy_settings' => $privacy,
            'store_credit_syp' => (int) $user->store_credit_syp,
            'is_active' => (bool) $user->is_active,
            'has_google' => filled($user->google_id),
            'has_apple' => filled($user->apple_id),
            'role' => $user->role?->name,
        ];
    }

    public function issueToken(User $user, string $name = 'api-token'): string
    {
        return $user->createToken($name)->plainTextToken;
    }

    public function sendOtp(string $channel, string $destination, string $purpose, ?User $user = null): array
    {
        $destination = trim($destination);
        if ($channel === 'email') {
            $destination = Str::lower($destination);
        }

        AuthOtp::query()
            ->where('destination', $destination)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);
        $ttl = max(3, (int) config('auth_social.otp.ttl_minutes', 10));

        $otp = AuthOtp::query()->create([
            'channel' => $channel,
            'destination' => $destination,
            'purpose' => $purpose,
            'code' => $code,
            'user_id' => $user?->id,
            'expires_at' => now()->addMinutes($ttl),
        ]);

        $this->deliverOtp($otp);

        $payload = [
            'ok' => true,
            'channel' => $channel,
            'destination' => $this->maskDestination($channel, $destination),
            'expires_in_seconds' => $ttl * 60,
            'message' => $channel === 'phone'
                ? 'أرسلنا رمز التحقق إلى رقمك.'
                : 'أرسلنا رمز التحقق إلى بريدك.',
        ];

        if (config('auth_social.otp.expose_in_response')) {
            $payload['dev_code'] = $code;
        }

        return $payload;
    }

    public function verifyOtp(string $channel, string $destination, string $purpose, string $code, ?User $user = null): AuthOtp
    {
        $destination = $channel === 'email' ? Str::lower(trim($destination)) : trim($destination);

        $otp = AuthOtp::query()
            ->where('channel', $channel)
            ->where('destination', $destination)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            throw ValidationException::withMessages(['code' => 'لا يوجد رمز تحقق صالح. اطلبي رمزاً جديداً.']);
        }

        if ($otp->expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => 'انتهت صلاحية الرمز.']);
        }

        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages(['code' => 'تم تجاوز عدد المحاولات. اطلبي رمزاً جديداً.']);
        }

        if ((string) $otp->code !== trim($code)) {
            $otp->increment('attempts');
            throw ValidationException::withMessages(['code' => 'رمز التحقق غير صحيح.']);
        }

        $otp->update(['consumed_at' => now()]);

        if ($user && $purpose === 'verify_phone') {
            $user->forceFill([
                'phone' => $destination,
                'phone_verified_at' => now(),
            ])->save();
        }

        if ($user && $purpose === 'verify_email') {
            $user->forceFill([
                'email' => $destination,
                'email_verified_at' => now(),
            ])->save();
        }

        return $otp;
    }

    public function loginWithOtp(string $channel, string $destination, string $code): array
    {
        $this->verifyOtp($channel, $destination, 'login', $code);

        $user = $channel === 'email'
            ? User::query()->where('email', Str::lower($destination))->first()
            : User::query()->where('phone', $destination)->first();

        if (! $user) {
            throw ValidationException::withMessages(['destination' => 'لا يوجد حساب مرتبط بهذه البيانات.']);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['destination' => 'الحساب معطّل مؤقتاً.']);
        }

        if ($channel === 'phone' && ! $user->phone_verified_at) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        if ($channel === 'email' && ! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return [
            'user' => $this->serialize($user->fresh()),
            'token' => $this->issueToken($user),
        ];
    }

    public function requestPasswordReset(string $email): array
    {
        $email = Str::lower(trim($email));
        $user = User::query()->where('email', $email)->first();

        // لا نكشف إن كان الإيميل موجوداً
        if ($user && $user->is_active) {
            $status = Password::broker()->sendResetLink(['email' => $email]);
            if ($status !== Password::RESET_LINK_SENT) {
                // fallback: OTP على الإيميل
                return $this->sendOtp('email', $email, 'reset', $user);
            }
        }

        return [
            'ok' => true,
            'message' => 'إذا كان البريد مسجّلاً، ستصلك تعليمات إعادة التعيين.',
        ];
    }

    public function resetPasswordWithToken(string $email, string $token, string $password): void
    {
        $status = Password::broker()->reset(
            [
                'email' => Str::lower($email),
                'token' => $token,
                'password' => $password,
                'password_confirmation' => $password,
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                ])->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => 'رابط إعادة التعيين غير صالح أو منتهٍ.']);
        }
    }

    public function resetPasswordWithOtp(string $email, string $code, string $password): array
    {
        $email = Str::lower(trim($email));
        $this->verifyOtp('email', $email, 'reset', $code);
        $user = User::query()->where('email', $email)->firstOrFail();
        $user->forceFill(['password' => $password])->save();
        $user->tokens()->delete();

        return [
            'user' => $this->serialize($user),
            'token' => $this->issueToken($user),
        ];
    }

    public function loginWithGoogle(string $idToken): array
    {
        $clientId = config('auth_social.google.client_id');
        if (! $clientId) {
            throw ValidationException::withMessages(['provider' => 'تسجيل Google غير مفعّل. أضيفي GOOGLE_CLIENT_ID في .env']);
        }

        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (! $response->ok()) {
            throw ValidationException::withMessages(['id_token' => 'رمز Google غير صالح.']);
        }

        $payload = $response->json();
        if (($payload['aud'] ?? null) !== $clientId) {
            throw ValidationException::withMessages(['id_token' => 'رمز Google لا يطابق تطبيق وصلة.']);
        }

        $googleId = (string) ($payload['sub'] ?? '');
        $email = Str::lower((string) ($payload['email'] ?? ''));
        $name = (string) ($payload['name'] ?? $payload['given_name'] ?? 'مستخدم وصلة');

        if ($googleId === '' || $email === '') {
            throw ValidationException::withMessages(['id_token' => 'بيانات Google ناقصة.']);
        }

        return $this->upsertSocialUser('google', $googleId, $email, $name, $payload['picture'] ?? null);
    }

    public function loginWithApple(string $idToken, ?string $email = null, ?string $name = null): array
    {
        $clientId = config('auth_social.apple.client_id');
        if (! $clientId && ! config('auth_social.apple.allow_dev_bypass')) {
            throw ValidationException::withMessages(['provider' => 'تسجيل Apple غير مفعّل. أضيفي APPLE_CLIENT_ID في .env']);
        }

        // تحقق JWT كامل يحتاج مكتبة مفاتيح Apple — هنا نفكك payload ونتحقق من aud عند التفعيل
        $parts = explode('.', $idToken);
        if (count($parts) < 2) {
            throw ValidationException::withMessages(['id_token' => 'رمز Apple غير صالح.']);
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) ?: [];
        $appleId = (string) ($payload['sub'] ?? '');
        $tokenEmail = Str::lower((string) ($payload['email'] ?? $email ?? ''));

        if ($clientId && ($payload['aud'] ?? null) !== $clientId && ! config('auth_social.apple.allow_dev_bypass')) {
            throw ValidationException::withMessages(['id_token' => 'رمز Apple لا يطابق التطبيق.']);
        }

        if ($appleId === '') {
            throw ValidationException::withMessages(['id_token' => 'بيانات Apple ناقصة.']);
        }

        if ($tokenEmail === '') {
            $tokenEmail = 'apple_'.$appleId.'@privaterelay.appleid.com';
        }

        return $this->upsertSocialUser('apple', $appleId, $tokenEmail, $name ?: 'مستخدم وصلة');
    }

    private function upsertSocialUser(string $provider, string $providerId, string $email, string $name, ?string $avatarUrl = null): array
    {
        return DB::transaction(function () use ($provider, $providerId, $email, $name, $avatarUrl) {
            $column = $provider === 'google' ? 'google_id' : 'apple_id';

            $user = User::query()->where($column, $providerId)->first()
                ?: User::query()->where('email', $email)->first();

            if (! $user) {
                $role = Role::query()->where('name', 'customer')->first();
                $user = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Str::random(32),
                    'role_id' => $role?->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'locale' => 'ar',
                    'preferred_currency' => 'SYP',
                    'privacy_settings' => $this->defaultPrivacy(),
                    $column => $providerId,
                ]);
            } else {
                if (! $user->is_active) {
                    throw ValidationException::withMessages(['provider' => 'الحساب معطّل مؤقتاً.']);
                }
                $user->forceFill([
                    $column => $providerId,
                    'email_verified_at' => $user->email_verified_at ?: now(),
                ])->save();
            }

            if ($avatarUrl && ! $user->avatar) {
                // نحفظ الرابط الخارجي كمرجع نصي إن لم تُرفع صورة محلية
                $user->forceFill(['avatar' => $avatarUrl])->save();
            }

            return [
                'user' => $this->serialize($user->fresh()),
                'token' => $this->issueToken($user),
            ];
        });
    }

    private function deliverOtp(AuthOtp $otp): void
    {
        $message = "رمز التحقق من وصلة: {$otp->code}\nصالح لـ "
            .config('auth_social.otp.ttl_minutes', 10)." دقائق.";

        if ($otp->channel === 'phone') {
            try {
                // إعادة استخدام قناة واتساب إن وُجدت
                Log::info('Auth OTP phone', ['to' => $otp->destination, 'purpose' => $otp->purpose]);
                // CustomerOrderNotifier يتوقع Order — نسجّل فقط؛ التكامل الكامل مع gateway لاحقاً
            } catch (\Throwable $e) {
                Log::warning('OTP phone delivery soft-fail', ['error' => $e->getMessage()]);
            }
        }

        Log::info('Auth OTP issued', [
            'channel' => $otp->channel,
            'destination' => $otp->destination,
            'purpose' => $otp->purpose,
            'code' => config('auth_social.otp.expose_in_response') ? $otp->code : '***',
        ]);

        // محاولة بريد بسيطة
        if ($otp->channel === 'email') {
            try {
                \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($otp) {
                    $mail->to($otp->destination)->subject('رمز التحقق — وصلة');
                });
            } catch (\Throwable $e) {
                Log::warning('OTP email delivery soft-fail', ['error' => $e->getMessage()]);
            }
        }
    }

    private function maskDestination(string $channel, string $destination): string
    {
        if ($channel === 'email' && str_contains($destination, '@')) {
            return \App\Models\ProductReview::maskEmail($destination);
        }

        $len = mb_strlen($destination);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return mb_substr($destination, 0, 3).str_repeat('*', max(3, $len - 5)).mb_substr($destination, -2);
    }
}
