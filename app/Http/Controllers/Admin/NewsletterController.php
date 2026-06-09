<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\NewsletterMailing;
use App\Models\NewsletterUnsubscribe;
use App\Models\Setting;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function index()
    {
        return view('admin.newsletter.index', [
            'mailings' => NewsletterMailing::with('sender')->latest()->limit(20)->get(),
            'activeUsers' => User::where('is_active', true)->count(),
            'eligibleUsers' => User::where('is_active', true)
                ->whereNotIn('email', NewsletterUnsubscribe::whereNotNull('unsubscribed_at')->select('email'))
                ->count(),
            'unsubscribed' => NewsletterUnsubscribe::whereNotNull('unsubscribed_at')->count(),
        ]);
    }

    public function send(Request $request, EmailService $emailService)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        if (! (bool) Setting::get('mail_enabled', false)) {
            return back()
                ->withErrors(['mail' => 'Email відправка вимкнена. Увімкніть SMTP у налаштуваннях сайту.'])
                ->withInput();
        }

        $mailing = NewsletterMailing::create([
            'sent_by' => $request->user()->id,
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'sent_at' => now(),
        ]);

        $sent = 0;
        User::where('is_active', true)
            ->whereNotIn('email', NewsletterUnsubscribe::whereNotNull('unsubscribed_at')->select('email'))
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($emailService, $validated, &$sent) {
                foreach ($users as $user) {
                    if ($emailService->sendNewsletter($user, $validated['subject'], $validated['body'])) {
                        $sent++;
                    }
                }
            });

        $mailing->update(['recipients_count' => $sent]);

        AuditLog::record('newsletter.sent', $mailing, [], ['recipients_count' => $sent]);

        return back()->with('success', "Розсилку відправлено: {$sent} отримувачів.");
    }
}
