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

        return redirect()->route('admin.users.index')->with('success', "Пользователь {$user->email} создан.");
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

        return view('admin.users.edit', compact('user', 'balances', 'merchants'));
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

        return back()->with('success', 'Користувача заблоковано.');
    }

    public function unblock(User $user)
    {
        $user->update(['is_active' => true, 'block_reason' => null, 'blocked_at' => null]);
        AuditLog::record('user.unblocked', $user);

        return back()->with('success', 'Користувача розблоковано.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role' => ['required', 'in:admin,merchant,support'],
        ]);

        // Prevent removing the last admin
        if ($user->isAdmin() && $validated['role'] !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Не можна понизити останнього адміністратора.');
        }

        $old = $user->only(['name', 'role']);
        $user->update($validated);
        AuditLog::record('user.updated', $user, $old, $validated);

        return back()->with('success', 'Користувача оновлено.');
    }

    public function toggleActive(User $user)
    {
        abort_if($user->isAdmin(), 403, 'Cannot deactivate admin.');

        $user->update(['is_active' => ! $user->is_active]);
        AuditLog::record('user.toggled_active', $user);

        return back()->with('success', 'Статус користувача оновлено.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 403, 'Нельзя удалить себя.');
        abort_if($user->isAdmin() && User::where('role', 'admin')->count() <= 1, 403, 'Нельзя удалить последнего администратора.');

        AuditLog::record('user.deleted', $user, [], ['email' => $user->email]);
        $user->delete();

        return back()->with('success', 'Користувача видалено.');
    }

    public function restore(int $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        AuditLog::record('user.restored', $user);

        return back()->with('success', 'Користувача відновлено.');
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

        return back()->with('success', "Password updated for {$user->email}.");
    }

    public function impersonate(Request $request, User $user)
    {
        abort_unless(app()->environment('local'), 403);

        $request->session()->put('impersonating', auth()->id());
        auth()->login($user);

        return redirect()->route('account.dashboard');
    }
}
