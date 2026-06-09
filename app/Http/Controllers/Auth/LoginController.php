<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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

    public function login(Request $request, RecaptchaService $recaptcha, TelegramNotificationService $telegram)
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

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        AuditLog::record('auth.login', $user);
        $telegram->notifyLogin($user, $request->ip());

        // Redirect to 2FA if enabled
        if ($user->google2fa_enabled) {
            return redirect()->route('2fa.verify');
        }

        return redirect()->intended($this->redirectTo($user));
    }

    public function logout(Request $request)
    {
        AuditLog::record('auth.logout', Auth::user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectTo($user): string
    {
        // Everyone (including admins) lands in the user cabinet.
        // Admins switch to the admin panel via a header button.
        return route('account.dashboard');
    }
}
