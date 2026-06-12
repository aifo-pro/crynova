<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\EmailService;
use App\Services\RecaptchaService;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function showForm()
    {
        if (! (bool) Setting::get('registration_enabled', true)) {
            return view('auth.register-disabled');
        }

        return view('auth.register');
    }

    public function register(
        Request $request,
        EmailService $emailService,
        RecaptchaService $recaptcha,
        TelegramNotificationService $telegram,
    )
    {
        if (! (bool) Setting::get('registration_enabled', true)) {
            throw ValidationException::withMessages([
                'email' => 'Реєстрацію тимчасово вимкнено.',
            ]);
        }

        $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:255', 'unique:users'],
            'password'    => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'agree_terms' => ['accepted'],
        ]);

        $recaptcha->verify($request, 'register');

        // Create the user account only — merchants are created separately
        // from the account panel and go through verification + moderation.
        // Attribute the signup to a referrer if a valid ?ref= code was used
        $referrerId = null;
        if ($ref = $request->input('ref', $request->cookie('ref'))) {
            $referrerId = User::where('referral_code', $ref)->value('id');
        }

        $user = DB::transaction(function () use ($request, $referrerId): User {
            $user = User::create([
                'name'        => $request->input('name'),
                'email'       => $request->input('email'),
                'password'    => $request->input('password'),
                'role'        => 'merchant',
                'referred_by' => $referrerId,
            ]);

            AuditLog::record('user.registered', $user, [], [], 'system');

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $emailService->sendWelcome($user);
        $telegram->notifyAdminRegistration($user);

        if ((bool) Setting::get('email_verification_enabled', false)) {
            $emailService->sendEmailVerification($user);

            return redirect()->route('verification.notice')
                ->with('success', __('flash.verify_sent'));
        }

        return redirect()->route('account.dashboard')
            ->with('success', __('flash.welcome_create_merchant'));
    }
}
