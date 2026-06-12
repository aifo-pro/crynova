<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogRatingController extends Controller
{
    public function store(Request $request, BlogPost $post)
    {
        abort_unless($post->status === 'published' && $post->published_at, 404);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        // One vote per visitor per post — guarded by a long-lived cookie.
        $cookieName = 'blog_rated_' . $post->id;
        if ($request->cookie($cookieName)) {
            return back()->with('info', __('public.blog_page.already_rated'));
        }

        $post->increment('rating_sum', $validated['rating']);
        $post->increment('rating_count');

        return back()
            ->with('success', __('public.blog_page.rated_thanks'))
            ->withCookie(cookie($cookieName, (string) $validated['rating'], 60 * 24 * 365));
    }
}
