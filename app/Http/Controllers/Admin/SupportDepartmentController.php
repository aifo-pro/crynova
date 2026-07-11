<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SupportDepartment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupportDepartmentController extends Controller
{
    public function index()
    {
        $departments = SupportDepartment::withCount('agents', 'tickets')->orderBy('sort')->orderBy('name')->get();

        return view('admin.support_departments.index', compact('departments'));
    }

    public function create()
    {
        $agents = $this->agents();
        $department = null;

        return view('admin.support_departments.form', compact('department', 'agents'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $department = SupportDepartment::create($data);
        $department->agents()->sync($request->input('agents', []));

        AuditLog::record('support_department.created', $department, [], ['name' => $department->name], 'admin');

        return redirect()->route('admin.support-departments.index')->with('success', 'Відділ створено.');
    }

    public function edit(SupportDepartment $department)
    {
        $department->load('agents');
        $agents = $this->agents();

        return view('admin.support_departments.form', compact('department', 'agents'));
    }

    public function update(Request $request, SupportDepartment $department)
    {
        $department->update($this->validated($request, $department->id));
        $department->agents()->sync($request->input('agents', []));

        AuditLog::record('support_department.updated', $department, [], ['name' => $department->name], 'admin');

        return redirect()->route('admin.support-departments.index')->with('success', 'Відділ оновлено.');
    }

    public function destroy(SupportDepartment $department)
    {
        $department->delete();
        AuditLog::record('support_department.deleted', null, ['name' => $department->name], [], 'admin');

        return redirect()->route('admin.support-departments.index')->with('success', 'Відділ видалено.');
    }

    private function agents()
    {
        return User::whereIn('role', ['admin', 'support'])->orderBy('name')->get(['id', 'name', 'email', 'role']);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['nullable', 'boolean'],
            'sort'        => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['slug'] = Str::slug($data['name']) . ($ignoreId ? '' : '-' . Str::random(4));
        if ($ignoreId) {
            unset($data['slug']); // keep existing slug on update
        }

        return $data;
    }
}
