<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\EmailService;
use App\Services\TelegramNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly TelegramNotificationService $telegram,
    ) {}

    public function redirectGoogle(Request $request): RedirectResponse
    {
        if (! $this->googleEnabled()) {
            return redirect()->route('login')->with('error', 'Вхід через Google не налаштовано.');
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => Setting::get('google_client_id', ''),
            'redirect_uri' => $this->googleRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callbackGoogle(Request $request): RedirectResponse
    {
        if (! $this->googleEnabled()) {
            return redirect()->route('login')->with('error', 'Вхід через Google не налаштовано.');
        }

        if ($request->filled('error')) {
            return redirect()->route('login')->with('error', 'Google авторизацію скасовано.');
        }

        $state = (string) $request->session()->pull('google_oauth_state', '');
        if ($state === '' || ! hash_equals($state, (string) $request->query('state', ''))) {
            return redirect()->route('login')->with('error', 'Некоректний OAuth state.');
        }

        $request->validate(['code' => ['required', 'string']]);

        try {
            $token = Http::asForm()->timeout(12)->post('https://oauth2.googleapis.com/token', [
                'client_id' => Setting::get('google_client_id', ''),
                'client_secret' => Setting::get('google_client_secret', ''),
                'redirect_uri' => $this->googleRedirectUri(),
                'grant_type' => 'authorization_code',
                'code' => $request->query('code'),
            ])->throw()->json();

            $profile = Http::withToken($token['access_token'] ?? '')
                ->timeout(12)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo')
                ->throw()
                ->json();
        } catch (\Throwable) {
            return redirect()->route('login')->with('error', 'Не вдалося отримати профіль Google.');
        }

        if (empty($profile['email'])) {
            return redirect()->route('login')->with('error', 'Google не повернув email акаунта.');
        }

        try {
            [$user, $created] = $this->findOrCreateGoogleUser($profile);
        } catch (ValidationException $e) {
            return redirect()->route('login')->withErrors($e->errors());
        }

        return $this->loginSocialUser($request, $user, 'auth.google_login', $created);
    }

    public function callbackTelegram(Request $request): RedirectResponse
    {
        if (! $this->telegramEnabled()) {
            return redirect()->route('login')->with('error', 'Вхід через Telegram не налаштовано.');
        }

        try {
            $payload = $this->verifiedTelegramPayload($request);
            [$user, $created] = $this->findOrCreateTelegramUser($payload);
        } catch (ValidationException $e) {
            return redirect()->route('login')->withErrors($e->errors());
        } catch (\Throwable) {
            return redirect()->route('login')->with('error', 'Не вдалося підтвердити Telegram авторизацію.');
        }

        return $this->loginSocialUser($request, $user, 'auth.telegram_login', $created);
    }

    private function findOrCreateGoogleUser(array $profile): array
    {
        $email = mb_strtolower((string) $profile['email']);
        $user = User::where('email', $email)->first();

        if ($user) {
            if (! $user->email_verified_at && ($profile['email_verified'] ?? false)) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            return [$user, false];
        }

        $this->ensureRegistrationOpen();

        $user = DB::transaction(function () use ($profile, $email): User {
            $user = User::create([
                'name' => $profile['name'] ?? Str::before($email, '@'),
                'email' => $email,
                'email_verified_at' => ($profile['email_verified'] ?? false) ? now() : null,
                'password' => Str::random(48),
                'role' => 'merchant',
            ]);

            AuditLog::record('user.registered_google', $user, [], [], 'system');

            return $user;
        });

        $this->emailService->sendWelcome($user);
        $this->telegram->notifyAdminRegistration($user);

        return [$user, true];
    }

    private function findOrCreateTelegramUser(array $payload): array
    {
        $telegramId = (string) $payload['id'];
        $email = "telegram_{$telegramId}@telegram.local";
        $username = isset($payload['username']) ? ltrim((string) $payload['username'], '@') : $telegramId;

        $user = User::where('email', $email)->first()
            ?: User::where('telegram', $telegramId)->first()
            ?: ($username !== '' ? User::where('telegram', $username)->first() : null);

        if ($user) {
            $user->forceFill([
                'telegram' => $telegramId,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();

            return [$user, false];
        }

        $this->ensureRegistrationOpen();

        $name = trim(($payload['first_name'] ?? '').' '.($payload['last_name'] ?? ''));
        if ($name === '') {
            $name = $username !== '' ? $username : 'Telegram User';
        }

        $user = DB::transaction(function () use ($name, $email, $telegramId): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => now(),
                'telegram' => $telegramId,
                'password' => Str::random(48),
                'role' => 'merchant',
            ]);

            AuditLog::record('user.registered_telegram', $user, [], [], 'system');

            return $user;
        });

        $this->telegram->notifyAdminRegistration($user);

        return [$user, true];
    }

    private function loginSocialUser(Request $request, User $user, string $auditAction, bool $created): RedirectResponse
    {
        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'Акаунт вимкнено.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        AuditLog::record($auditAction, $user, [], ['created' => $created]);
        $this->telegram->notifyLogin($user, $request->ip());

        if ($user->google2fa_enabled) {
            return redirect()->route('2fa.verify');
        }

        return redirect()->intended(route('account.dashboard'));
    }

    private function verifiedTelegramPayload(Request $request): array
    {
        $data = $request->only([
            'id', 'first_name', 'last_name', 'username', 'photo_url', 'auth_date', 'hash',
        ]);

        if (empty($data['id']) || empty($data['auth_date']) || empty($data['hash'])) {
            throw ValidationException::withMessages(['telegram' => 'Некоректні дані Telegram.']);
        }

        if (((int) $data['auth_date']) < now()->subDay()->timestamp) {
            throw ValidationException::withMessages(['telegram' => 'Термін Telegram авторизації минув.']);
        }

        $hash = (string) $data['hash'];
        unset($data['hash']);

        ksort($data);
        $checkString = collect($data)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value, $key) => $key.'='.$value)
            ->implode("\n");

        $secretKey = hash('sha256', (string) Setting::get('telegram_login_bot_token', ''), true);
        $calculatedHash = hash_hmac('sha256', $checkString, $secretKey);

        if (! hash_equals($calculatedHash, $hash)) {
            throw ValidationException::withMessages(['telegram' => 'Підпис Telegram не пройшов перевірку.']);
        }

        return $data;
    }

    private function ensureRegistrationOpen(): void
    {
        if (! (bool) Setting::get('registration_enabled', true)) {
            throw ValidationException::withMessages([
                'email' => 'Реєстрацію тимчасово вимкнено.',
            ]);
        }
    }

    private function googleEnabled(): bool
    {
        return (bool) Setting::get('google_auth_enabled', false)
            && trim((string) Setting::get('google_client_id', '')) !== ''
            && trim((string) Setting::get('google_client_secret', '')) !== '';
    }

    private function telegramEnabled(): bool
    {
        return (bool) Setting::get('telegram_auth_enabled', false)
            && trim((string) Setting::get('telegram_login_bot_username', '')) !== ''
            && trim((string) Setting::get('telegram_login_bot_token', '')) !== '';
    }

    private function googleRedirectUri(): string
    {
        $uri = trim((string) Setting::get('google_redirect_uri', ''));

        return $uri !== '' ? $uri : route('auth.google.callback');
    }
}
