<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::latest()->paginate(30);

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255', 'unique:pages,slug'],
            'body'             => ['required', 'string'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_published'     => ['boolean'],
            'title_en' => ['nullable','string','max:255'], 'title_pl' => ['nullable','string','max:255'], 'title_ru' => ['nullable','string','max:255'],
            'body_en' => ['nullable','string'], 'body_pl' => ['nullable','string'], 'body_ru' => ['nullable','string'],
            'meta_title_en' => ['nullable','string','max:255'], 'meta_title_pl' => ['nullable','string','max:255'], 'meta_title_ru' => ['nullable','string','max:255'],
            'meta_description_en' => ['nullable','string','max:500'], 'meta_description_pl' => ['nullable','string','max:500'], 'meta_description_ru' => ['nullable','string','max:500'],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);

        $page = Page::create($validated);
        AuditLog::record('page.created', $page);

        return redirect()->route('admin.pages.index')->with('success', __('flash.page_created'));
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255', "unique:pages,slug,{$page->id}"],
            'body'             => ['required', 'string'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_published'     => ['boolean'],
            'title_en' => ['nullable','string','max:255'], 'title_pl' => ['nullable','string','max:255'], 'title_ru' => ['nullable','string','max:255'],
            'body_en' => ['nullable','string'], 'body_pl' => ['nullable','string'], 'body_ru' => ['nullable','string'],
            'meta_title_en' => ['nullable','string','max:255'], 'meta_title_pl' => ['nullable','string','max:255'], 'meta_title_ru' => ['nullable','string','max:255'],
            'meta_description_en' => ['nullable','string','max:500'], 'meta_description_pl' => ['nullable','string','max:500'], 'meta_description_ru' => ['nullable','string','max:500'],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $old = $page->toArray();
        $page->update($validated);
        AuditLog::record('page.updated', $page, $old, $page->fresh()->toArray());

        return back()->with('success', __('flash.page_saved'));
    }

    public function destroy(Page $page)
    {
        AuditLog::record('page.deleted', $page);
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', __('flash.page_deleted'));
    }
}
