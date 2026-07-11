<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SupportTemplate;
use Illuminate\Http\Request;

class SupportTemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = SupportTemplate::query()
            ->when($request->input('search'), fn ($q, $s) =>
                $q->where('title', 'like', "%{$s}%")->orWhere('body', 'like', "%{$s}%")
            )
            ->when($request->input('category'), fn ($q, $c) => $q->where('category', $c))
            ->orderBy('sort')
            ->orderBy('title')
            ->paginate(30)
            ->withQueryString();

        $categories = SupportTemplate::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        return view('admin.templates.index', compact('templates', 'categories'));
    }

    public function create()
    {
        return view('admin.templates.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $template = SupportTemplate::create($data);
        AuditLog::record('support_template.created', $template, [], ['title' => $template->title], 'admin');

        return redirect()->route('admin.templates.index')->with('success', 'Шаблон створено.');
    }

    public function edit(SupportTemplate $template)
    {
        return view('admin.templates.edit', compact('template'));
    }

    public function update(Request $request, SupportTemplate $template)
    {
        $template->update($this->validated($request));
        AuditLog::record('support_template.updated', $template, [], ['title' => $template->title], 'admin');

        return redirect()->route('admin.templates.index')->with('success', 'Шаблон оновлено.');
    }

    public function toggle(SupportTemplate $template)
    {
        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('success', $template->is_active ? 'Шаблон увімкнено.' : 'Шаблон вимкнено.');
    }

    public function destroy(SupportTemplate $template)
    {
        $template->delete();
        AuditLog::record('support_template.deleted', null, ['title' => $template->title], [], 'admin');

        return redirect()->route('admin.templates.index')->with('success', 'Шаблон видалено.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'     => ['required', 'string', 'max:150'],
            'category'  => ['nullable', 'string', 'max:80'],
            'body'      => ['required', 'string', 'max:8000'],
            'body_en'   => ['nullable', 'string', 'max:8000'],
            'body_pl'   => ['nullable', 'string', 'max:8000'],
            'body_ru'   => ['nullable', 'string', 'max:8000'],
            'is_active' => ['nullable', 'boolean'],
            'sort'      => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
