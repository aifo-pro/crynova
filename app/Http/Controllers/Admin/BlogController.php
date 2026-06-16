<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $posts = BlogPost::with('author')
            ->when($request->input('search'), fn ($q, $s) =>
                $q->where('title', 'like', "%{$s}%")
            )
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.blog.index', compact('posts'));
    }

    public function create()
    {
        return view('admin.blog.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'excerpt'     => ['nullable', 'string', 'max:500'],
            'body'        => ['required', 'string'],
            'status'      => ['required', 'in:draft,published,archived'],
            'tags'        => ['nullable', 'string'],
            'cover_image' => ['nullable', 'url', 'max:500'],
            'title_en' => ['nullable','string','max:255'], 'title_pl' => ['nullable','string','max:255'],
            'excerpt_en' => ['nullable','string','max:1000'], 'excerpt_pl' => ['nullable','string','max:1000'],
            'body_en' => ['nullable','string'], 'body_pl' => ['nullable','string'],
        ]);

        $validated['slug']        = Str::slug($validated['title']);
        $validated['author_id']   = $request->user()->id;
        $validated['tags']        = array_filter(array_map('trim', explode(',', $validated['tags'] ?? '')));
        $validated['published_at'] = $validated['status'] === 'published' ? now() : null;

        $post = BlogPost::create($validated);
        AuditLog::record('blog.post_created', $post);

        return redirect()->route('admin.blog.index')->with('success', __('flash.post_published'));
    }

    /** Inline image upload for the rich-text editor. Returns { url }. */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $path = $request->file('image')->store('blog/inline', 'public');

        return response()->json(['url' => \Illuminate\Support\Facades\Storage::disk('public')->url($path)]);
    }

    public function edit(BlogPost $post)
    {
        return view('admin.blog.edit', compact('post'));
    }

    public function update(Request $request, BlogPost $post)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'excerpt'     => ['nullable', 'string', 'max:500'],
            'body'        => ['required', 'string'],
            'status'      => ['required', 'in:draft,published,archived'],
            'tags'        => ['nullable', 'string'],
            'cover_image' => ['nullable', 'url', 'max:500'],
            'title_en' => ['nullable','string','max:255'], 'title_pl' => ['nullable','string','max:255'],
            'excerpt_en' => ['nullable','string','max:1000'], 'excerpt_pl' => ['nullable','string','max:1000'],
            'body_en' => ['nullable','string'], 'body_pl' => ['nullable','string'],
        ]);

        $old = $post->toArray();

        $validated['tags'] = array_filter(array_map('trim', explode(',', $validated['tags'] ?? '')));

        if ($validated['status'] === 'published' && ! $post->published_at) {
            $validated['published_at'] = now();
        }

        $post->update($validated);
        AuditLog::record('blog.post_updated', $post, $old, $post->fresh()->toArray());

        return back()->with('success', __('flash.post_updated'));
    }

    public function destroy(BlogPost $post)
    {
        AuditLog::record('blog.post_deleted', $post);
        $post->delete();

        return redirect()->route('admin.blog.index')->with('success', __('flash.post_deleted'));
    }
}
