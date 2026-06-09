<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly Google2FA $google2fa,
    ) {}

    public function showVerify()
    {
        return view('auth.2fa-verify');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $user   = $request->user();
        $secret = $user->google2fa_secret;

        if (! $secret || ! $this->google2fa->verifyKey($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        $request->session()->put('2fa_verified', true);

        AuditLog::record('auth.2fa_verified', $user);

        return redirect()->intended(route('account.dashboard'));
    }

    public function showSetup(Request $request)
    {
        $user = $request->user();

        if ($user->google2fa_enabled) {
            return redirect()->route('account.security')->with('info', '2FA already enabled.');
        }

        $secret = $this->google2fa->generateSecretKey();
        $request->session()->put('2fa_setup_secret', $secret);

        $qrUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        return view('auth.2fa-setup', compact('secret', 'qrUrl'));
    }

    public function confirmSetup(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret || ! $this->google2fa->verifyKey($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Code does not match. Try again.']);
        }

        $user = $request->user();
        $user->google2fa_secret  = $secret; // triggers encrypted setter
        $user->google2fa_enabled = true;
        $user->save();

        $request->session()->forget('2fa_setup_secret');
        $request->session()->put('2fa_verified', true);

        AuditLog::record('auth.2fa_enabled', $user);

        return redirect()->route('account.security')->with('success', '2FA enabled successfully.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        $user->google2fa_enabled = false;
        $user->google2fa_secret  = null;
        $user->save();

        $request->session()->forget('2fa_verified');

        AuditLog::record('auth.2fa_disabled', $user);

        return back()->with('success', '2FA disabled.');
    }
}
