<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\EmailService;
use App\Services\RecaptchaService;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showForm()
    {
        return view('auth.login');
    }

    public function login(Request $request, RecaptchaService $recaptcha, TelegramNotificationService $telegram, EmailService $emailService)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $recaptcha->verify($request, 'login');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Невірний email або пароль.',
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Акаунт вимкнено.']);
        }

        // First sign-in of the day → security alert email (before we overwrite it).
        $firstLoginToday = $user->last_login_at === null
            || ! $user->last_login_at->isSameDay(now());

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        AuditLog::record('auth.login', $user);
        $telegram->notifyLogin($user, $request->ip());

        if ($firstLoginToday) {
            $ip        = (string) $request->ip();
            $userAgent = (string) $request->userAgent();
            $loggedAt  = now()->format('d.m.Y H:i');
            app()->terminating(function () use ($emailService, $user, $ip, $userAgent, $loggedAt) {
                $emailService->sendLoginAlert($user, $ip, $userAgent, $loggedAt);
            });
        }

        // Redirect to 2FA if enabled
        if ($user->google2fa_enabled) {
            return redirect()->route('2fa.verify');
        }

        return redirect()->intended($this->redirectTo($user))
            ->with('success', __('auth.welcome_back', ['name' => $user->name]))
            ->with('app_preloader', true);
    }

    public function logout(Request $request)
    {
        AuditLog::record('auth.logout', Auth::user());

        // Keep the chosen interface language across logout (session is wiped below).
        $locale = $request->session()->get('locale');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($locale) {
            $request->session()->put('locale', $locale);
        }

        return redirect()->route('login')->with('success', __('auth.logged_out'));
    }

    private function redirectTo($user): string
    {
        // Everyone (including admins) lands in the user cabinet.
        // Admins switch to the admin panel via a header button.
        return route('account.dashboard');
    }
}
