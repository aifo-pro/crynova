<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SupportAttachment;
use App\Models\SupportDepartment;
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
        $deptFilter = $request->query('department');
        $user = $request->user();
        $uid = $user->id;

        // Support agents only see their departments' tickets + the general pool + their own.
        // Full admins see everything.
        $isSupport = $user->isSupport();
        $myDeptIds = $isSupport ? $user->supportDepartments()->pluck('support_departments.id')->all() : [];
        $applyScope = function ($q) use ($isSupport, $uid, $myDeptIds) {
            if ($isSupport) {
                $q->where(function ($sub) use ($uid, $myDeptIds) {
                    $sub->where('assigned_to', $uid)
                        ->orWhereNull('department_id')
                        ->orWhereIn('department_id', $myDeptIds);
                });
            }

            return $q;
        };

        $query = SupportTicket::with('user', 'assignedAgent', 'department');
        $applyScope($query);

        $tickets = $query
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($priority, fn ($q) => $q->where('priority', $priority))
            ->when($deptFilter, fn ($q, $d) => $q->where('department_id', $d))
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
            'open'       => $applyScope(SupportTicket::where('status', 'open'))->count(),
            'mine'       => SupportTicket::where('assigned_to', $uid)->whereIn('status', ['open', 'answered'])->count(),
            'unassigned' => $applyScope(SupportTicket::whereNull('assigned_to')->whereIn('status', ['open', 'answered']))->count(),
        ];

        // Department chips: agent sees own departments, admin sees all.
        $departments = $user->isSupport()
            ? $user->supportDepartments()->orderBy('name')->get()
            : SupportDepartment::orderBy('sort')->orderBy('name')->get();

        return view('admin.support.index', compact('tickets', 'status', 'assignee', 'priority', 'counts', 'departments', 'deptFilter'));
    }

    public function show(SupportTicket $ticket)
    {
        if ($ticket->admin_unread) {
            $ticket->update(['admin_unread' => false]);
        }

        $ticket->load(['messages.attachments', 'messages.user', 'user', 'assignedAgent', 'department', 'internalNotes.author']);

        // Templates offered as quick replies, pre-localized to the ticket's language
        // (agent's choice, falling back to the user's language).
        $locale = $ticket->effectiveLocale();
        $templates = SupportTemplate::active()->orderBy('sort')->orderBy('title')->get()
            ->map(fn (SupportTemplate $t) => [
                'id'    => $t->id,
                'title' => $t->title,
                'body'  => $t->bodyFor($locale),
            ])->values();

        // Agents who can own a ticket.
        $agents = User::whereIn('role', ['admin', 'support'])->orderBy('name')->get(['id', 'name', 'email']);
        $departments = SupportDepartment::active()->orderBy('sort')->orderBy('name')->get();

        return view('admin.support.show', compact('ticket', 'templates', 'agents', 'departments'));
    }

    /**
     * Transfer a ticket to another department. Frees it from the current agent so
     * any specialist in the target department can pick it up. Notifies the user
     * and records who transferred it (and why) as an internal note.
     */
    public function transfer(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:support_departments,id'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ]);

        $dept = SupportDepartment::findOrFail($data['department_id']);
        $from = $ticket->department?->name;

        $ticket->update([
            'department_id' => $dept->id,
            'assigned_to'   => null, // release into the department pool
        ]);

        // Internal audit trail for staff.
        $ticket->internalNotes()->create([
            'user_id' => $request->user()->id,
            'body'    => 'Передано у відділ «' . $dept->name . '»' . ($from ? ' (з «' . $from . '»)' : '') .
                (! empty($data['reason']) ? '. Причина: ' . $data['reason'] : ''),
        ]);

        // User-facing notice (no internal reasons exposed).
        $this->support->postSystem($ticket, 'support.system.transferred', ['department' => $dept->name]);

        AuditLog::record('support.transferred', $ticket, ['department' => $from], ['department' => $dept->name], 'admin');

        return redirect()->route('admin.support.index')->with('success', "Тікет передано у відділ «{$dept->name}».");
    }

    /** Assign the ticket to an agent (or unassign), posting a system notice on change. */
    public function assign(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $old = $ticket->assigned_to;
        $new = $data['assigned_to'] ?? null;

        if ((int) $old === (int) $new) {
            return back();
        }

        $ticket->update(['assigned_to' => $new]);
        AuditLog::record('support.assigned', $ticket, ['assigned_to' => $old], ['assigned_to' => $new], 'admin');

        $agentName = fn ($id) => optional(User::find($id))->name ?: (optional(User::find($id))->email ?? 'Агент');

        if ($new) {
            $this->support->postSystem($ticket, 'support.system.agent_joined', ['name' => $agentName($new)]);

            return back()->with('success', 'Тікет призначено.');
        }

        // Unassigned / agent left the ticket.
        $this->support->postSystem($ticket, 'support.system.agent_left', ['name' => $agentName($old)]);

        return back()->with('success', 'Ви залишили тікет.');
    }

    /** Set the language used for template replies in this ticket. */
    public function setLocale(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'locale' => ['required', 'in:uk,en,pl,ru'],
        ]);

        $ticket->update(['locale' => $data['locale']]);

        return back()->with('success', 'Мову тікета оновлено.');
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
            'is_system'   => $message->is_system,
            'body'        => $message->displayBody(),
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
