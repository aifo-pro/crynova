<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/*
 * Account settings: Профиль / Безопасность / Уведомления / Пользователи.
 */
class SettingsController extends Controller
{
    private const DEFAULT_PREFS = [
        'channel_email'    => true,
        'channel_telegram' => false,
        'event_auth'       => true,
        'event_withdraw'   => true,
        'event_partial'    => true,
        'event_paid'       => true,
    ];

    // ── Профиль ────────────────────────────────────────────────────
    public function profile(Request $request)
    {
        return view('account.settings.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'telegram' => ['nullable', 'string', 'max:50'],
            'language' => ['required', 'in:ru,en,ua'],
        ]);
        $validated['telegram'] = $validated['telegram'] ? ltrim($validated['telegram'], '@') : null;

        $request->user()->update($validated);
        AuditLog::record('account.profile_updated', $request->user());

        return back()->with('success', 'Профиль сохранён.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);
        $request->user()->update(['password' => $request->input('password')]);
        AuditLog::record('account.password_changed', $request->user());

        return back()->with('success', 'Пароль изменён.');
    }

    // ── Безопасность ───────────────────────────────────────────────
    public function security(Request $request)
    {
        $user = $request->user();

        // Login history from the audit log
        $logins = AuditLog::where('user_id', $user->id)
            ->whereIn('action', ['auth.login', 'auth.2fa_verified'])
            ->latest()
            ->limit(10)
            ->get();

        return view('account.settings.security', compact('user', 'logins'));
    }

    public function regenerateApiKey(Request $request)
    {
        $raw = 'cryn_acc_' . Str::random(40);
        $user = $request->user();
        $user->account_api_key = $raw;   // mutator encrypts
        $user->save();
        AuditLog::record('account.api_key_created', $user);

        return back()->with('success', 'Новый API-ключ создан.')->with('new_account_key', $raw);
    }

    // ── Уведомления ────────────────────────────────────────────────
    public function notifications(Request $request)
    {
        $prefs = array_merge(self::DEFAULT_PREFS, $request->user()->notification_prefs ?? []);

        return view('account.settings.notifications', ['user' => $request->user(), 'prefs' => $prefs]);
    }

    public function updateNotifications(Request $request)
    {
        $keys = array_keys(self::DEFAULT_PREFS);
        $prefs = [];
        foreach ($keys as $k) {
            $prefs[$k] = $request->boolean($k);
        }

        $request->user()->update(['notification_prefs' => $prefs]);
        AuditLog::record('account.notifications_updated', $request->user());

        return back()->with('success', 'Настройки уведомлений сохранены.');
    }

    // ── Пользователи (team access) ─────────────────────────────────
    public function team(Request $request)
    {
        $members = $request->user()->teamMembers()->with('member')->latest()->get();

        return view('account.settings.team', ['user' => $request->user(), 'members' => $members]);
    }

    public function inviteTeam(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role'  => ['required', 'in:viewer,manager,admin'],
        ]);

        $owner = $request->user();

        if (strtolower($validated['email']) === strtolower($owner->email)) {
            return back()->with('error', 'Нельзя пригласить самого себя.');
        }
        if ($owner->teamMembers()->where('email', $validated['email'])->exists()) {
            return back()->with('error', 'Этот пользователь уже добавлен.');
        }

        // Link to an existing user or create a pending account with a temp password
        $member = User::where('email', $validated['email'])->first();
        $tempPassword = null;
        if (! $member) {
            $tempPassword = Str::random(12);
            $member = User::create([
                'name'     => Str::before($validated['email'], '@'),
                'email'    => $validated['email'],
                'password' => $tempPassword,
                'role'     => 'merchant',
            ]);
        }

        TeamMember::create([
            'owner_id'  => $owner->id,
            'member_id' => $member->id,
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'status'    => 'invited',
        ]);

        AuditLog::record('account.team_invited', $owner, [], ['email' => $validated['email'], 'role' => $validated['role']]);

        // In production an email with the temp password would be sent here.
        $msg = 'Пользователь приглашён.';
        if ($tempPassword) {
            $msg .= " Временный пароль: {$tempPassword}";
        }

        return back()->with('success', $msg);
    }

    public function removeTeam(Request $request, TeamMember $member)
    {
        abort_unless($member->owner_id === $request->user()->id, 403);
        $member->delete();
        AuditLog::record('account.team_removed', $request->user());

        return back()->with('success', 'Доступ отозван.');
    }
}
