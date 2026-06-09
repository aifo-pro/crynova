<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice(Request $request)
    {
        if (! (bool) Setting::get('email_verification_enabled', false)) {
            return redirect()->route('account.dashboard');
        }

        if ($request->user()?->email_verified_at) {
            return redirect()->route('account.dashboard');
        }

        return view('auth.verify-email');
    }

    public function send(Request $request, EmailService $emailService)
    {
        $user = $request->user();

        if ($user && ! $user->email_verified_at && (bool) Setting::get('email_verification_enabled', false)) {
            $emailService->sendEmailVerification($user);
        }

        return back()->with('success', 'Лист підтвердження відправлено повторно.');
    }

    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals($hash, sha1($user->email)), 403);

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
            AuditLog::record('auth.email_verified', $user, [], [], 'system');
        }

        if ($request->user()?->id === $user->id) {
            return redirect()->route('account.dashboard')->with('success', 'Email підтверджено.');
        }

        return redirect()->route('login')->with('success', 'Email підтверджено. Тепер можна увійти.');
    }
}
