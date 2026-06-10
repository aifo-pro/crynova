<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SupportAttachment;
use App\Models\SupportTicket;
use App\Services\SupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportController extends Controller
{
    public function __construct(private readonly SupportService $support) {}

    public function index(Request $request)
    {
        $status = $request->query('status');

        $tickets = SupportTicket::with('user')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->query('search'), fn ($q, $s) =>
                $q->where('subject', 'like', "%{$s}%")
                  ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            )
            ->orderByDesc('admin_unread')
            ->orderByDesc('last_message_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.support.index', compact('tickets', 'status'));
    }

    public function show(SupportTicket $ticket)
    {
        if ($ticket->admin_unread) {
            $ticket->update(['admin_unread' => false]);
        }

        $ticket->load(['messages.attachments', 'messages.user', 'user']);

        return view('admin.support.show', compact('ticket'));
    }

    public function messages(SupportTicket $ticket)
    {
        $after = (int) request()->query('after', 0);
        $messages = $ticket->messages()->with('attachments')->where('id', '>', $after)->orderBy('id')->get();

        if ($messages->isNotEmpty() && $ticket->admin_unread) {
            $ticket->update(['admin_unread' => false]);
        }

        return response()->json([
            'status'   => $ticket->status,
            'messages' => $messages->map(fn ($m) => $this->serialize($m))->all(),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'body'    => ['required_without:files', 'nullable', 'string', 'max:5000'],
            'files'   => ['nullable', 'array', 'max:' . SupportService::MAX_FILES],
            'files.*' => ['file', 'max:' . SupportService::MAX_SIZE_KB, 'mimes:jpg,jpeg,png,webp,gif,pdf,zip,txt,doc,docx,xls,xlsx'],
        ]);

        $this->support->postMessage($ticket, $request->user(), true, (string) ($data['body'] ?? ''), $request->file('files', []));
        AuditLog::record('support.replied', $ticket);

        return $request->ajax() || $request->expectsJson() ? response()->noContent() : back();
    }

    public function close(SupportTicket $ticket)
    {
        $ticket->update(['status' => 'closed']);
        AuditLog::record('support.closed', $ticket);

        return back()->with('success', 'Тікет закрито.');
    }

    public function reopen(SupportTicket $ticket)
    {
        $ticket->update(['status' => 'open', 'admin_unread' => true]);
        AuditLog::record('support.reopened', $ticket);

        return back()->with('success', 'Тікет відкрито знову.');
    }

    public function download(SupportAttachment $attachment)
    {
        return Storage::disk('public')->download($attachment->path, $attachment->original_name);
    }

    private function serialize($message): array
    {
        return [
            'id'          => $message->id,
            'is_admin'    => $message->is_admin,
            'body'        => $message->body,
            'time'        => $message->created_at->format('d.m.Y H:i'),
            'attachments' => $message->attachments->map(fn (SupportAttachment $a) => [
                'name'     => $a->original_name,
                'url'      => route('admin.support.attachment', $a),
                'is_image' => $a->isImage(),
                'preview'  => $a->isImage() ? $a->url() : null,
                'size'     => $a->humanSize(),
            ])->all(),
        ];
    }
}
