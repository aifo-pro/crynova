<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withTrashed()
            ->with('merchant')
            ->when($request->input('search'), fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")
            )
            ->when($request->input('role'), fn ($q, $r) => $q->where('role', $r))
            ->latest()
            ->paginate(25);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'     => ['required', 'in:admin,merchant,support'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = User::create($validated + ['is_active' => true]);
        AuditLog::record('user.created', $user, [], ['email' => $user->email, 'role' => $user->role]);

        return redirect()->route('admin.users.index')->with('success', __('flash.admin_user_created', ['email' => $user->email]));
    }

    public function edit(User $user)
    {
        $user->loadCount('merchants');

        // Aggregate balances across all of the user's merchants, grouped by currency
        $merchantIds = $user->merchants()->pluck('id');
        $balances = \App\Models\Balance::whereIn('merchant_id', $merchantIds)
            ->with('currency')
            ->get()
            ->groupBy(fn ($b) => $b->currency->code)
            ->map(fn ($g) => [
                'available' => (string) $g->sum('available'),
                'locked'    => (string) $g->sum('locked'),
            ]);

        $merchants = $user->merchants()->withCount('invoices')->latest()->get();

        $departments = \App\Models\SupportDepartment::orderBy('sort')->orderBy('name')->get();
        $userDeptIds = $user->supportDepartments()->pluck('support_departments.id')->all();

        return view('admin.users.edit', compact('user', 'balances', 'merchants', 'departments', 'userDeptIds'));
    }

    public function block(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 403, 'Не можна блокувати адміністратора.');

        $validated = $request->validate([
            'block_reason' => ['required', 'string', 'max:500'],
        ]);

        $user->update([
            'is_active'    => false,
            'block_reason' => $validated['block_reason'],
            'blocked_at'   => now(),
        ]);
        AuditLog::record('user.blocked', $user, [], ['reason' => $validated['block_reason']]);

        return back()->with('success', __('flash.user_blocked'));
    }

    public function unblock(User $user)
    {
        $user->update(['is_active' => true, 'block_reason' => null, 'blocked_at' => null]);
        AuditLog::record('user.unblocked', $user);

        return back()->with('success', __('flash.user_unblocked'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role' => ['required', 'in:admin,merchant,support'],
        ]);

        // Prevent removing the last admin
        if ($user->isAdmin() && $validated['role'] !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', __('flash.cant_demote_last_admin'));
        }

        $old = $user->only(['name', 'role']);
        $user->update($validated);
        AuditLog::record('user.updated', $user, $old, $validated);

        return back()->with('success', __('flash.user_updated'));
    }

    /**
     * Save internal admin note and tags for a user.
     */
    public function updateNotes(Request $request, User $user)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:5000'],
            'tags'       => ['nullable', 'string', 'max:500'],
        ]);

        $tags = collect(explode(',', (string) ($data['tags'] ?? '')))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->unique()
            ->take(20)
            ->values()
            ->all();

        $user->update([
            'admin_note' => $data['admin_note'] ?? null,
            'tags'       => $tags ?: null,
        ]);

        AuditLog::record('user.notes_updated', $user, [], ['tags' => $tags], 'admin');

        return back()->with('success', 'Нотатки та теги збережено.');
    }

    /** Save the full support-agent profile (display name, telegram, note, departments). */
    public function updateSupportProfile(Request $request, User $user)
    {
        $data = $request->validate([
            'support_display_name' => ['nullable', 'string', 'max:100'],
            'support_telegram'     => ['nullable', 'string', 'max:100'],
            'admin_note'           => ['nullable', 'string', 'max:5000'],
            'departments'          => ['nullable', 'array'],
            'departments.*'        => ['integer', 'exists:support_departments,id'],
        ]);

        $user->update([
            'support_display_name' => $data['support_display_name'] ?? null,
            'support_telegram'     => $data['support_telegram'] ?? null,
            'admin_note'           => $data['admin_note'] ?? $user->admin_note,
        ]);
        $user->supportDepartments()->sync($data['departments'] ?? []);

        AuditLog::record('user.support_profile_updated', $user, [], [
            'display_name' => $data['support_display_name'] ?? null,
            'departments'  => $data['departments'] ?? [],
        ], 'admin');

        return back()->with('success', 'Профіль техпідтримки збережено.');
    }

    public function toggleActive(User $user)
    {
        abort_if($user->isAdmin(), 403, 'Cannot deactivate admin.');

        $user->update(['is_active' => ! $user->is_active]);
        AuditLog::record('user.toggled_active', $user);

        return back()->with('success', __('flash.user_status_updated'));
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 403, 'Нельзя удалить себя.');
        abort_if($user->isAdmin() && User::where('role', 'admin')->count() <= 1, 403, 'Нельзя удалить последнего администратора.');

        AuditLog::record('user.deleted', $user, [], ['email' => $user->email]);
        $user->delete();

        return back()->with('success', __('flash.user_deleted'));
    }

    public function restore(int $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        AuditLog::record('user.restored', $user);

        return back()->with('success', __('flash.user_restored'));
    }

    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        AuditLog::record('user.password_updated', $user, [], [
            'email' => $user->email,
            'changed_by_admin' => true,
        ]);

        return back()->with('success', __('flash.admin_password_updated', ['email' => $user->email]));
    }

    public function resetTwoFactor(Request $request, User $user)
    {
        $validated = $request->validate([
            'recovery_word' => ['required', 'string', 'max:64'],
        ]);

        $given = mb_strtolower(trim($validated['recovery_word']));

        // The user must have set a recovery word when enabling 2FA. Verify it
        // before removing 2FA — this is the support-desk identity check.
        if (empty($user->tfa_recovery_word) || ! \Illuminate\Support\Facades\Hash::check($given, $user->tfa_recovery_word)) {
            AuditLog::record('user.2fa_reset_failed', $user, [], [
                'email' => $user->email,
                'reason' => 'recovery_word_mismatch',
            ]);

            return back()->withErrors(['recovery_word' => __('flash.recovery_mismatch')]);
        }

        $user->update([
            'google2fa_enabled' => false,
            'google2fa_secret'  => null,
            'tfa_recovery_word' => null,
        ]);

        AuditLog::record('user.2fa_reset', $user, [], [
            'email' => $user->email,
            'reset_by_admin' => true,
        ]);

        return back()->with('success', __('flash.admin_2fa_reset', ['email' => $user->email]));
    }

    /**
     * Log in as another (non-admin) user for support/diagnostics.
     * The original admin id is kept in the session so they can switch back.
     */
    public function impersonate(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 403, 'Не можна імперсонувати іншого адміністратора.');

        if ($request->session()->has('impersonator_id')) {
            return back()->with('error', 'Ви вже імперсонуєте користувача.');
        }

        AuditLog::record('user.impersonate.start', $user, [], ['email' => $user->email], 'admin');

        $request->session()->put('impersonator_id', auth()->id());
        auth()->login($user);
        // Skip 2FA gate for the impersonated session — the admin is already verified.
        $request->session()->put('2fa_verified', true);

        return redirect()->route('account.dashboard')
            ->with('success', "Ви увійшли як {$user->email}.");
    }

    /**
     * Return to the original admin account after impersonation.
     * Registered outside the admin group so the impersonated (non-admin) user can call it.
     */
    public function stopImpersonating(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_id');

        abort_unless($adminId, 403);

        $admin = User::find($adminId);
        abort_unless($admin, 403);

        $impersonated = auth()->user();
        auth()->login($admin);
        $request->session()->put('2fa_verified', true);

        AuditLog::record('user.impersonate.stop', $impersonated, [], [], 'admin');

        return redirect()->route('admin.users.index')
            ->with('success', 'Повернулися до адмін-акаунту.');
    }
}
