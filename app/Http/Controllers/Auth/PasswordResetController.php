<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\EmailService;
use App\Services\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request, EmailService $emailService, RecaptchaService $recaptcha)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $recaptcha->verify($request, 'password_reset');

        $user = User::where('email', $validated['email'])->where('is_active', true)->first();

        if ($user) {
            $token = Password::broker()->createToken($user);
            $url = route('password.reset', ['token' => $token, 'email' => $user->email]);
            $emailService->sendPasswordReset($user, $url);
            AuditLog::record('auth.password_reset_requested', $user, [], [], 'system');
        }

        return back()->with('success', __('flash.reset_link_sent'));
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                AuditLog::record('auth.password_reset_completed', $user, [], [], 'system');
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Посилання недійсне або термін його дії минув.'])->withInput($request->only('email'));
        }

        return redirect()->route('login')->with('success', __('flash.password_reset_done'));
    }
}
