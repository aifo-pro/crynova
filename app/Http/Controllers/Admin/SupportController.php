<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SupportAttachment;
use App\Models\SupportInternalNote;
use App\Models\SupportTemplate;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportController extends Controller
{
    public function __construct(private readonly SupportService $support) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $assignee = $request->query('assignee'); // 'mine' | 'unassigned' | null
        $priority = $request->query('priority');
        $uid = $request->user()->id;

        $tickets = SupportTicket::with('user', 'assignedAgent')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($priority, fn ($q) => $q->where('priority', $priority))
            ->when($assignee === 'mine', fn ($q) => $q->where('assigned_to', $uid))
            ->when($assignee === 'unassigned', fn ($q) => $q->whereNull('assigned_to'))
            ->when($request->query('search'), fn ($q, $s) =>
                $q->where('subject', 'like', "%{$s}%")
                  ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            )
            ->orderByDesc('admin_unread')
            ->orderByDesc('last_message_at')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'open'       => SupportTicket::where('status', 'open')->count(),
            'mine'       => SupportTicket::where('assigned_to', $uid)->whereIn('status', ['open', 'answered'])->count(),
            'unassigned' => SupportTicket::whereNull('assigned_to')->whereIn('status', ['open', 'answered'])->count(),
        ];

        return view('admin.support.index', compact('tickets', 'status', 'assignee', 'priority', 'counts'));
    }

    public function show(SupportTicket $ticket)
    {
        if ($ticket->admin_unread) {
            $ticket->update(['admin_unread' => false]);
        }

        $ticket->load(['messages.attachments', 'messages.user', 'user', 'assignedAgent', 'internalNotes.author']);

        // Templates offered as quick replies, pre-localized to the ticket owner's language.
        $locale = $ticket->user?->language ?: 'uk';
        $templates = SupportTemplate::active()->orderBy('sort')->orderBy('title')->get()
            ->map(fn (SupportTemplate $t) => [
                'id'    => $t->id,
                'title' => $t->title,
                'body'  => $t->bodyFor($locale),
            ])->values();

        // Agents who can own a ticket.
        $agents = User::whereIn('role', ['admin', 'support'])->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.support.show', compact('ticket', 'templates', 'agents'));
    }

    /** Assign the ticket to an agent (or unassign). */
    public function assign(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $ticket->update(['assigned_to' => $data['assigned_to'] ?? null]);
        AuditLog::record('support.assigned', $ticket, [], ['assigned_to' => $ticket->assigned_to], 'admin');

        return back()->with('success', $ticket->assigned_to ? 'Тікет призначено.' : 'Призначення знято.');
    }

    /** Change ticket priority. */
    public function priority(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'priority' => ['required', 'in:low,normal,high'],
        ]);

        $ticket->update(['priority' => $data['priority']]);
        AuditLog::record('support.priority', $ticket, [], ['priority' => $data['priority']], 'admin');

        return back()->with('success', 'Пріоритет оновлено.');
    }

    /** Add an internal (staff-only) note to the ticket. */
    public function addNote(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $ticket->internalNotes()->create([
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);
        AuditLog::record('support.note_added', $ticket, [], [], 'admin');

        return back()->with('success', 'Нотатку додано.');
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

        return back()->with('success', __('flash.ticket_closed'));
    }

    public function reopen(SupportTicket $ticket)
    {
        $ticket->update(['status' => 'open', 'admin_unread' => true]);
        AuditLog::record('support.reopened', $ticket);

        return back()->with('success', __('flash.ticket_reopened'));
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
