<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\IntegrationModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IntegrationModuleController extends Controller
{
    public function index()
    {
        $modules = IntegrationModule::orderBy('sort')->orderBy('name')->get();

        return view('admin.modules.index', compact('modules'));
    }

    public function create()
    {
        return view('admin.modules.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('modules', 'public');
        }

        $module = IntegrationModule::create($data);
        AuditLog::record('module.created', $module);

        return redirect()->route('admin.modules.index')->with('success', __('flash.module_added'));
    }

    public function edit(IntegrationModule $module)
    {
        return view('admin.modules.edit', compact('module'));
    }

    public function update(Request $request, IntegrationModule $module)
    {
        $data = $this->validateData($request, $module);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        if ($request->hasFile('file')) {
            if ($module->file_path) {
                Storage::disk('public')->delete($module->file_path);
            }
            $data['file_path'] = $request->file('file')->store('modules', 'public');
        }

        $module->update($data);
        AuditLog::record('module.updated', $module);

        return redirect()->route('admin.modules.index')->with('success', __('flash.module_updated'));
    }

    public function destroy(IntegrationModule $module)
    {
        if ($module->file_path) {
            Storage::disk('public')->delete($module->file_path);
        }
        AuditLog::record('module.deleted', $module);
        $module->delete();

        return redirect()->route('admin.modules.index')->with('success', __('flash.module_deleted'));
    }

    private function validateData(Request $request, ?IntegrationModule $module = null): array
    {
        $uniqueSlug = 'unique:integration_modules,slug' . ($module ? ",{$module->id}" : '');

        return $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'slug'         => ['nullable', 'string', 'max:255', $uniqueSlug],
            'description'  => ['nullable', 'string', 'max:500'],
            'icon'         => ['nullable', 'string', 'max:50'],
            'version'      => ['nullable', 'string', 'max:50'],
            'external_url' => ['nullable', 'url', 'max:2048'],
            'file'         => ['nullable', 'file', 'max:51200', 'mimes:zip,rar,gz,tar,tgz,php'],
            'is_active'    => ['boolean'],
            'sort'         => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
