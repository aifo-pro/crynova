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
            return back()->withErrors(['code' => __('auth.tfa.invalid_code')]);
        }

        $request->session()->put('2fa_verified', true);

        AuditLog::record('auth.2fa_verified', $user);

        return redirect()->intended(route('account.dashboard'));
    }

    public function showSetup(Request $request)
    {
        $user = $request->user();

        // Already enabled → show the management page (disable section) instead of
        // bouncing back. Don't generate a new secret so the existing one stays valid.
        if ($user->google2fa_enabled) {
            return view('auth.2fa-setup', ['enabled' => true, 'secret' => null, 'qrUrl' => null, 'hasRecovery' => true]);
        }

        // Reuse the pending secret across reloads/failed attempts so the QR the
        // user already scanned stays valid (a fresh secret each load would break
        // the authenticator they just configured).
        $secret = $request->session()->get('2fa_setup_secret');
        if (! $secret) {
            $secret = $this->google2fa->generateSecretKey();
            $request->session()->put('2fa_setup_secret', $secret);
        }

        $qrUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        return view('auth.2fa-setup', [
            'enabled'      => false,
            'secret'       => $secret,
            'qrUrl'        => $qrUrl,
            'hasRecovery'  => ! empty($user->tfa_recovery_word),
        ]);
    }

    public function confirmSetup(Request $request)
    {
        $user = $request->user();
        $needsRecovery = empty($user->tfa_recovery_word);

        $request->validate([
            'code'          => ['required', 'string', 'size:6'],
            'recovery_word' => [$needsRecovery ? 'required' : 'nullable', 'string', 'min:4', 'max:64'],
        ]);

        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret || ! $this->google2fa->verifyKey($secret, $request->input('code'))) {
            return back()->withErrors(['code' => __('auth.tfa.code_mismatch')])->withInput();
        }

        $user->google2fa_secret  = $secret; // triggers encrypted setter
        $user->google2fa_enabled = true;
        if ($needsRecovery) {
            // Normalised (trim + lowercase) so matching is forgiving for support.
            $user->tfa_recovery_word = \Illuminate\Support\Facades\Hash::make(
                mb_strtolower(trim((string) $request->input('recovery_word')))
            );
        }
        $user->save();

        $request->session()->forget('2fa_setup_secret');
        $request->session()->put('2fa_verified', true);

        AuditLog::record('auth.2fa_enabled', $user);

        return redirect()->route('account.security')->with('success', __('auth.tfa.enabled_ok'));
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

        return back()->with('success', __('auth.tfa.disabled_ok'));
    }
}
