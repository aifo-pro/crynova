<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Services\RecaptchaService;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(
        Request $request,
        RecaptchaService $recaptcha,
        TelegramNotificationService $telegram,
    ) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $recaptcha->verify($request, 'contact');

        $message = ContactMessage::create([
            ...$validated,
            'ip' => $request->ip(),
            'status' => 'new',
        ]);

        AuditLog::record('contact.created', $message, [], [], 'system');
        $telegram->notifyContactMessage($message);

        return back()->with('success', __('public.contact.success'));
    }
}
