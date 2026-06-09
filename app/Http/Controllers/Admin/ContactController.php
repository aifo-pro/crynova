<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $messages = ContactMessage::with('assignedTo')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('search'), fn ($q, $s) =>
                $q->where('email', 'like', "%{$s}%")
                  ->orWhere('subject', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%")
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.contact.index', compact('messages'));
    }

    public function show(ContactMessage $message)
    {
        // Mark as read if still new
        if ($message->status === 'new') {
            $message->update(['status' => 'read']);
        }

        return view('admin.contact.show', compact('message'));
    }

    public function update(Request $request, ContactMessage $message)
    {
        $validated = $request->validate([
            'status'      => ['required', 'in:new,read,replied,archived'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $old = $message->toArray();
        $message->update($validated);
        AuditLog::record('contact.updated', $message, $old, $message->fresh()->toArray());

        return back()->with('success', 'Message updated.');
    }
}
