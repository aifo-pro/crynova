<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
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
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->orderByDesc('last_message_at')
            ->get();

        return view('account.support.index', compact('tickets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'   => ['required', 'string', 'max:160'],
            'body'      => ['required', 'string', 'max:5000'],
            'files'     => ['nullable', 'array', 'max:' . SupportService::MAX_FILES],
            'files.*'   => ['file', 'max:' . SupportService::MAX_SIZE_KB, 'mimes:jpg,jpeg,png,webp,gif,pdf,zip,txt,doc,docx,xls,xlsx'],
        ]);

        $ticket = $this->support->createTicket(
            $request->user(),
            $data['subject'],
            $data['body'],
            $request->file('files', []),
        );

        return redirect()->route('account.support.show', $ticket)
            ->with('success', __('support.created'));
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTicket($request, $ticket);

        // Mark admin replies as read for the user.
        if ($ticket->user_unread) {
            $ticket->update(['user_unread' => false]);
        }

        $ticket->load(['messages.attachments', 'messages.user']);

        return view('account.support.show', compact('ticket'));
    }

    public function messages(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTicket($request, $ticket);

        $after = (int) $request->query('after', 0);
        $messages = $ticket->messages()->with('attachments')->where('id', '>', $after)->orderBy('id')->get();

        if ($messages->isNotEmpty() && $ticket->user_unread) {
            $ticket->update(['user_unread' => false]);
        }

        return response()->json([
            'status'   => $ticket->status,
            'messages' => $messages->map(fn ($m) => $this->serialize($m))->all(),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTicket($request, $ticket);
        abort_if($ticket->isClosed(), 422, 'Ticket is closed.');

        $data = $request->validate([
            'body'    => ['required_without:files', 'nullable', 'string', 'max:5000'],
            'files'   => ['nullable', 'array', 'max:' . SupportService::MAX_FILES],
            'files.*' => ['file', 'max:' . SupportService::MAX_SIZE_KB, 'mimes:jpg,jpeg,png,webp,gif,pdf,zip,txt,doc,docx,xls,xlsx'],
        ]);

        $this->support->postMessage($ticket, $request->user(), false, (string) ($data['body'] ?? ''), $request->file('files', []));

        return $request->ajax() || $request->expectsJson() ? response()->noContent() : back();
    }

    public function close(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTicket($request, $ticket);
        $ticket->update(['status' => 'closed']);

        return back()->with('success', __('support.closed'));
    }

    public function download(Request $request, SupportAttachment $attachment)
    {
        $ticket = $attachment->message->ticket;
        $this->authorizeTicket($request, $ticket);

        return Storage::disk('public')->download($attachment->path, $attachment->original_name);
    }

    private function authorizeTicket(Request $request, SupportTicket $ticket): void
    {
        abort_unless($ticket->user_id === $request->user()->id, 403);
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
                'url'      => route('account.support.attachment', $a),
                'is_image' => $a->isImage(),
                'preview'  => $a->isImage() ? $a->url() : null,
                'size'     => $a->humanSize(),
            ])->all(),
        ];
    }
}
