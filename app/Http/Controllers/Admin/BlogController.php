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
        $validated = $request->validate($this->rules());

        $validated['slug']         = $this->uniqueSlug($validated['slug'] ?: $validated['title']);
        $validated['author_id']    = $request->user()->id;
        $validated['tags']         = array_filter(array_map('trim', explode(',', $validated['tags'] ?? '')));
        $validated['cover_image']  = $this->resolveCover($request, $validated['cover_image'] ?? null);
        $validated['published_at'] = $validated['status'] === 'published' ? now() : null;

        $post = BlogPost::create($validated);
        AuditLog::record('blog.post_created', $post);

        return redirect()->route('admin.blog.index')->with('success', __('flash.post_published'));
    }

    /** Shared validation rules for store/update. */
    private function rules(?BlogPost $post = null): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255'],
            'excerpt'     => ['nullable', 'string', 'max:500'],
            'body'        => ['required', 'string'],
            'status'      => ['required', 'in:draft,published,archived'],
            'tags'        => ['nullable', 'string'],
            'cover_image' => ['nullable', 'url', 'max:500'],
            'cover_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'meta_title'       => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:170'],
            'meta_title_en' => ['nullable','string','max:70'], 'meta_title_pl' => ['nullable','string','max:70'],
            'meta_description_en' => ['nullable','string','max:170'], 'meta_description_pl' => ['nullable','string','max:170'],
            'title_en' => ['nullable','string','max:255'], 'title_pl' => ['nullable','string','max:255'],
            'excerpt_en' => ['nullable','string','max:1000'], 'excerpt_pl' => ['nullable','string','max:1000'],
            'body_en' => ['nullable','string'], 'body_pl' => ['nullable','string'],
        ];
    }

    /** Uploaded file wins over a pasted URL; otherwise keep the existing/pasted value. */
    private function resolveCover(Request $request, ?string $url): ?string
    {
        if ($request->hasFile('cover_upload')) {
            $path = $request->file('cover_upload')->store('blog/covers', 'public');

            return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        }

        return $url;
    }

    /** Ensure a unique slug, appending -2, -3… on collision. */
    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'post';
        $slug = $base;
        $i = 2;
        while (BlogPost::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
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
        $validated = $request->validate($this->rules($post));

        $old = $post->toArray();

        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?: $validated['title'], $post->id);
        $validated['tags'] = array_filter(array_map('trim', explode(',', $validated['tags'] ?? '')));
        $validated['cover_image'] = $this->resolveCover($request, $validated['cover_image'] ?? null) ?: $post->cover_image;

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
