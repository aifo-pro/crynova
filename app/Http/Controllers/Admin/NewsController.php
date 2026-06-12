<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $items = News::with('author')
            ->when($request->input('search'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.news.index', compact('items'));
    }

    public function create()
    {
        return view('admin.news.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug']         = Str::slug($data['title']);
        $data['author_id']    = $request->user()->id;
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        $item = News::create($data);
        AuditLog::record('news.created', $item);

        return redirect()->route('admin.news.index')->with('success', __('flash.news_published'));
    }

    public function edit(News $news)
    {
        return view('admin.news.edit', ['item' => $news]);
    }

    public function update(Request $request, News $news)
    {
        $data = $this->validateData($request);

        if ($data['status'] === 'published' && ! $news->published_at) {
            $data['published_at'] = now();
        }

        $news->update($data);
        AuditLog::record('news.updated', $news);

        return back()->with('success', __('flash.news_updated'));
    }

    public function destroy(News $news)
    {
        AuditLog::record('news.deleted', $news);
        $news->delete();

        return redirect()->route('admin.news.index')->with('success', __('flash.news_deleted'));
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'excerpt'     => ['nullable', 'string', 'max:500'],
            'body'        => ['required', 'string'],
            'status'      => ['required', 'in:draft,published,archived'],
            'cover_image' => ['nullable', 'url', 'max:500'],
        ]);
    }
}
