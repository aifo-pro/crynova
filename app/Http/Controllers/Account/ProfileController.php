<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return view('account.profile', compact('user'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $user->update(['name' => $request->input('name')]);

        AuditLog::record('account.profile_updated', $user);

        return back()->with('success', __('flash.profile_saved'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = $request->user();
        $user->update(['password' => $request->input('password')]);

        AuditLog::record('account.password_changed', $user);

        return back()->with('success', __('flash.password_updated'));
    }

    public function security(Request $request)
    {
        // The standalone security page was merged into account settings.
        return redirect()->route('account.settings.security');
    }
}
