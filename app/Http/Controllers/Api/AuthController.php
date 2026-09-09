<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\CustomerAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private CustomerAccountService $accounts)
    {
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
            'locale' => 'nullable|in:ar,en',
            'preferred_currency' => 'nullable|in:SYP,AED,USD',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $role = Role::where('name', 'customer')->first();
        $email = Str::lower($request->email);

        $user = User::create([
            'name' => $request->name,
            'email' => $email,
            'phone' => $request->phone,
            'password' => $request->password,
            'role_id' => $role?->id,
            'is_active' => true,
            'email_verified_at' => null,
            'locale' => $request->input('locale', 'ar'),
            'preferred_currency' => $request->input('preferred_currency', 'SYP'),
            'privacy_settings' => $this->accounts->defaultPrivacy(),
        ]);

        $otpPayload = $this->accounts->sendOtp('email', $email, 'register', $user);

        return response()->json([
            'ok' => true,
            'requires_email_verification' => true,
            'email' => $email,
            'message' => 'أنشأنا حسابك. أدخل رمز التأكيد المرسل إلى بريدك لإكمال التسجيل.',
            'otp' => $otpPayload,
        ], 201);
    }

    public function verifyRegistration(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|max:10',
        ]);

        $email = Str::lower($data['email']);
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages(['email' => 'لا يوجد حساب بهذا البريد.']);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'user' => $this->accounts->serialize($user),
                'token' => $this->accounts->issueToken($user),
                'message' => 'البريد مؤكّد مسبقاً.',
            ]);
        }

        $this->accounts->verifyOtp('email', $email, 'register', $data['code'], $user);
        $user->forceFill(['email_verified_at' => now()])->save();

        return response()->json([
            'ok' => true,
            'message' => 'تم تأكيد البريد بنجاح. أهلاً بكِ في وصلة.',
            'user' => $this->accounts->serialize($user->fresh()),
            'token' => $this->accounts->issueToken($user),
        ]);
    }

    public function resendRegistrationOtp(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $email = Str::lower($data['email']);
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            // لا نكشف وجود الحساب
            return response()->json([
                'ok' => true,
                'message' => 'إذا كان البريد مسجّلاً وغير مؤكّد، سيصلك رمز جديد.',
            ]);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'ok' => true,
                'message' => 'هذا البريد مؤكّد مسبقاً. سجّلي الدخول.',
            ]);
        }

        $otpPayload = $this->accounts->sendOtp('email', $email, 'register', $user);

        return response()->json([
            'ok' => true,
            'message' => 'أرسلنا رمزاً جديداً إلى بريدك.',
            'otp' => $otpPayload,
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', Str::lower($request->email))->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة.'], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'الحساب معطّل مؤقتاً. تواصلي مع وصلة لإعادة التفعيل.',
                'code' => 'account_disabled',
            ], 403);
        }

        if (config('auth_social.require_email_verification') && ! $user->email_verified_at) {
            $otpPayload = $this->accounts->sendOtp('email', $user->email, 'register', $user);

            return response()->json([
                'message' => 'يجب تأكيد البريد أولاً. أرسلنا رمزاً جديداً.',
                'code' => 'email_not_verified',
                'email' => $user->email,
                'otp' => $otpPayload,
            ], 403);
        }

        return response()->json([
            'user' => $this->accounts->serialize($user),
            'token' => $this->accounts->issueToken($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function profile(Request $request)
    {
        return response()->json($this->accounts->serialize($request->user()));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'locale' => 'nullable|in:ar,en',
            'preferred_currency' => 'nullable|in:SYP,AED,USD',
            'privacy_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $emailChanged = Str::lower($data['email']) !== Str::lower($user->email);
        $phoneChanged = ($data['phone'] ?? null) !== $user->phone;

        $user->name = $data['name'];
        $user->email = Str::lower($data['email']);
        $user->phone = $data['phone'] ?? null;
        if (array_key_exists('locale', $data) && $data['locale']) {
            $user->locale = $data['locale'];
        }
        if (array_key_exists('preferred_currency', $data) && $data['preferred_currency']) {
            $user->preferred_currency = $data['preferred_currency'];
        }
        if (isset($data['privacy_settings'])) {
            $user->privacy_settings = array_merge(
                $this->accounts->defaultPrivacy(),
                $user->privacy_settings ?? [],
                $data['privacy_settings']
            );
        }
        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        if ($phoneChanged) {
            $user->phone_verified_at = null;
        }
        $user->save();

        return response()->json($this->accounts->serialize($user->fresh()));
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        $user->forceFill(['password' => $data['password']])->save();
        // أبقِ الجلسة الحالية، وألغِ الباقي
        $current = $user->currentAccessToken();
        $user->tokens()->where('id', '!=', $current?->id)->delete();

        return response()->json(['ok' => true, 'message' => 'تم تغيير كلمة المرور.']);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:4096',
        ]);

        $user = $request->user();
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->forceFill(['avatar' => '/storage/'.$path])->save();

        return response()->json([
            'ok' => true,
            'user' => $this->accounts->serialize($user->fresh()),
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $data = $request->validate([
            'locale' => 'nullable|in:ar,en',
            'preferred_currency' => 'nullable|in:SYP,AED,USD',
            'privacy_settings' => 'nullable|array',
        ]);

        $user = $request->user();
        if (! empty($data['locale'])) {
            $user->locale = $data['locale'];
        }
        if (! empty($data['preferred_currency'])) {
            $user->preferred_currency = $data['preferred_currency'];
        }
        if (isset($data['privacy_settings'])) {
            $user->privacy_settings = array_merge(
                $this->accounts->defaultPrivacy(),
                $user->privacy_settings ?? [],
                $data['privacy_settings']
            );
        }
        $user->save();

        return response()->json($this->accounts->serialize($user->fresh()));
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        return response()->json($this->accounts->requestPasswordReset($data['email']));
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'token' => 'nullable|string',
            'code' => 'nullable|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! empty($data['code'])) {
            $result = $this->accounts->resetPasswordWithOtp($data['email'], $data['code'], $data['password']);

            return response()->json($result);
        }

        if (empty($data['token'])) {
            throw ValidationException::withMessages(['token' => 'يلزم رمز أو رابط إعادة التعيين.']);
        }

        $this->accounts->resetPasswordWithToken($data['email'], $data['token'], $data['password']);

        return response()->json(['ok' => true, 'message' => 'تم تعيين كلمة المرور الجديدة. سجّلي الدخول.']);
    }

    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'channel' => 'required|in:phone,email',
            'destination' => 'required|string|max:120',
            'purpose' => 'required|in:login,verify_phone,verify_email,reset,register',
        ]);

        $user = $request->user();
        if (in_array($data['purpose'], ['verify_phone', 'verify_email'], true) && ! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json($this->accounts->sendOtp(
            $data['channel'],
            $data['destination'],
            $data['purpose'],
            $user
        ));
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'channel' => 'required|in:phone,email',
            'destination' => 'required|string|max:120',
            'purpose' => 'required|in:login,verify_phone,verify_email,reset,register',
            'code' => 'required|string|max:10',
        ]);

        if ($data['purpose'] === 'login') {
            return response()->json($this->accounts->loginWithOtp(
                $data['channel'],
                $data['destination'],
                $data['code']
            ));
        }

        $user = $request->user();
        $this->accounts->verifyOtp(
            $data['channel'],
            $data['destination'],
            $data['purpose'],
            $data['code'],
            $user
        );

        return response()->json([
            'ok' => true,
            'user' => $user ? $this->accounts->serialize($user->fresh()) : null,
            'message' => 'تم التحقق بنجاح.',
        ]);
    }

    public function social(Request $request)
    {
        if (! config('auth_social.social_enabled')) {
            return response()->json([
                'message' => 'تسجيل Google / Apple معلّق حالياً. سجّلي بالإيميل وكلمة المرور.',
                'code' => 'social_disabled',
            ], 503);
        }

        $data = $request->validate([
            'provider' => 'required|in:google,apple',
            'id_token' => 'required|string',
            'email' => 'nullable|email',
            'name' => 'nullable|string|max:255',
        ]);

        $result = $data['provider'] === 'google'
            ? $this->accounts->loginWithGoogle($data['id_token'])
            : $this->accounts->loginWithApple($data['id_token'], $data['email'] ?? null, $data['name'] ?? null);

        return response()->json($result);
    }

    public function providers()
    {
        return response()->json([
            'social_enabled' => (bool) config('auth_social.social_enabled'),
            'google' => [
                'enabled' => (bool) config('auth_social.google.enabled'),
                'client_id' => config('auth_social.google.client_id'),
            ],
            'apple' => [
                'enabled' => (bool) config('auth_social.apple.enabled'),
                'client_id' => config('auth_social.apple.client_id'),
            ],
            'require_email_verification' => (bool) config('auth_social.require_email_verification'),
            'otp_expose' => (bool) config('auth_social.otp.expose_in_response'),
        ]);
    }
}
