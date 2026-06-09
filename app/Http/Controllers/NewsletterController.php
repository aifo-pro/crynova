<?php

namespace App\Http\Controllers;

use App\Models\NewsletterUnsubscribe;

class NewsletterController extends Controller
{
    public function unsubscribe(string $token)
    {
        $unsubscribe = NewsletterUnsubscribe::where('token', $token)->firstOrFail();

        $unsubscribe->update([
            'unsubscribed_at' => $unsubscribe->unsubscribed_at ?? now(),
            'source' => 'email_link',
        ]);

        return view('newsletter.unsubscribed', ['email' => $unsubscribe->email]);
    }
}
