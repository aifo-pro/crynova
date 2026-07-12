@php
    /** @var \App\Models\SupportTicket $ticket */
    $viewerIsAdmin = $isAdmin ?? false;
@endphp

<div class="flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/70 px-5 py-4">
        <div class="min-w-0">
            <p class="truncate font-bold text-slate-950">{{ $ticket->subject }}</p>
            <p class="text-xs text-slate-500">
                #{{ $ticket->id }}
                @if($viewerIsAdmin) · {{ optional($ticket->user)->name }} ({{ optional($ticket->user)->email }})@endif
            </p>
        </div>
        @php
            $badge = match($ticket->status) {
                'closed'   => ['bg-slate-100 text-slate-500', __('support.status.closed')],
                'answered' => ['bg-emerald-50 text-emerald-600', __('support.status.answered')],
                default    => ['bg-amber-50 text-amber-600', __('support.status.open')],
            };
        @endphp
        <div class="flex items-center gap-2">
            <span id="ticket-status" class="rounded-full px-3 py-1 text-xs font-bold {{ $badge[0] }}">{{ $badge[1] }}</span>
            @if($viewerIsAdmin)
                @if($ticket->isClosed())
                    <form method="POST" action="{{ route('admin.support.reopen', $ticket) }}">@csrf
                        <button class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-white">{{ __('support.reopen') }}</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.support.close', $ticket) }}">@csrf
                        <button class="rounded-full border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-500 hover:bg-rose-50">{{ __('support.close') }}</button>
                    </form>
                @endif
            @elseif(! $ticket->isClosed())
                <form method="POST" action="{{ route('account.support.close', $ticket) }}">@csrf
                    <button class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-50">{{ __('support.close') }}</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Messages --}}
    <div id="chat-stream" class="flex max-h-[60vh] min-h-[320px] flex-col gap-3 overflow-y-auto bg-slate-50/40 px-4 py-5 sm:px-6">
        @foreach($ticket->messages as $m)
            @if($m->is_system)
                <div class="flex justify-center" data-mid="{{ $m->id }}">
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-center text-[11px] font-semibold text-slate-500">{{ $m->displayBody() }}</span>
                </div>
                @continue
            @endif
            @php $mine = $m->is_admin === $viewerIsAdmin; @endphp
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}" data-mid="{{ $m->id }}">
                <div class="max-w-[78%] rounded-2xl px-4 py-2.5 text-sm shadow-sm {{ $mine ? 'bg-blue-600 text-white' : 'bg-white text-slate-800 ring-1 ring-slate-100' }}">
                    @if(! $mine)
                        @php
                            $author = $m->is_admin
                                ? ($viewerIsAdmin
                                    ? (optional($m->user)->name ?: __('support.staff'))
                                    : (optional($m->user)->support_display_name ?: __('support.staff')))
                                : optional($m->user)->name;
                        @endphp
                        <p class="mb-0.5 text-[11px] font-bold {{ $m->is_admin ? 'text-blue-600' : 'text-slate-500' }}">{{ $author }}</p>
                    @endif
                    @if($m->body)<p class="whitespace-pre-wrap break-words leading-6">{{ $m->body }}</p>@endif
                    @foreach($m->attachments as $a)
                        @if($a->isImage())
                            <a href="{{ $viewerIsAdmin ? route('admin.support.attachment', $a) : route('account.support.attachment', $a) }}" target="_blank" class="mt-2 block">
                                <img src="{{ $a->url() }}" alt="{{ $a->original_name }}" class="max-h-48 rounded-xl border {{ $mine ? 'border-blue-400' : 'border-slate-200' }}">
                            </a>
                        @else
                            <a href="{{ $viewerIsAdmin ? route('admin.support.attachment', $a) : route('account.support.attachment', $a) }}" class="mt-2 flex items-center gap-2 rounded-xl px-2 py-1.5 text-xs {{ $mine ? 'bg-blue-500/40' : 'bg-slate-100 text-slate-700' }}">
                                <x-icon name="file-text" class="h-4 w-4 shrink-0" /> <span class="truncate">{{ $a->original_name }}</span> <span class="opacity-70">{{ $a->humanSize() }}</span>
                            </a>
                        @endif
                    @endforeach
                    <p class="mt-1 text-right text-[10px] {{ $mine ? 'text-blue-100' : 'text-slate-400' }}">{{ $m->created_at->format('d.m.Y H:i') }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Composer --}}
    @if($ticket->isClosed())
        <div class="border-t border-slate-100 px-5 py-4 text-center text-sm text-slate-400">{{ __('support.closed_note') }}</div>
    @else
        <form id="chat-form" class="border-t border-slate-100 px-4 py-4 sm:px-5"
              action="{{ $viewerIsAdmin ? route('admin.support.reply', $ticket) : route('account.support.reply', $ticket) }}">
            @if(($viewerIsAdmin ?? false) && !empty($templates) && $templates->isNotEmpty())
                <div id="tpl-wrap" class="relative mb-2 inline-block">
                    <button type="button" id="tpl-toggle"
                        class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
                        <x-icon name="message-circle" class="h-3.5 w-3.5" /> Швидкі відповіді
                        <x-icon name="chevron-down" class="h-3.5 w-3.5 text-slate-400" />
                    </button>
                    <div id="tpl-menu" class="absolute bottom-full left-0 z-20 mb-2 hidden w-72 max-w-[calc(100vw-3rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl ring-1 ring-black/5">
                        <div class="max-h-64 overflow-y-auto p-1.5">
                            @foreach($templates as $tpl)
                                <button type="button" data-tpl="{{ $tpl['id'] }}"
                                    class="block w-full truncate rounded-xl px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-blue-50 hover:text-blue-700">{{ $tpl['title'] }}</button>
                            @endforeach
                        </div>
                        <a href="{{ route('admin.templates.index') }}" target="_blank" class="block border-t border-slate-100 px-3 py-2 text-center text-xs font-semibold text-slate-400 transition hover:bg-slate-50 hover:text-blue-600">Керувати шаблонами</a>
                    </div>
                </div>
            @endif
            <div id="file-chips" class="mb-2 flex flex-wrap gap-2"></div>
            <div class="flex items-end gap-2">
                <label class="grid h-11 w-11 shrink-0 cursor-pointer place-items-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-50">
                    <x-icon name="plus" class="h-5 w-5" />
                    <input id="chat-files" type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.zip,.txt,.doc,.docx,.xls,.xlsx" class="hidden">
                </label>
                <textarea id="chat-input" name="body" rows="1" placeholder="{{ __('support.placeholder') }}"
                    class="fin-input max-h-32 flex-1 resize-none py-2.5"></textarea>
                <button id="chat-send" type="submit" class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-blue-600 text-white transition hover:bg-blue-700 disabled:opacity-50">
                    <x-icon name="arrow-right" class="h-5 w-5" />
                </button>
            </div>
        </form>
    @endif
</div>

<script>
(function () {
    const stream  = document.getElementById('chat-stream');
    const form    = document.getElementById('chat-form');
    const viewerIsAdmin = {{ $viewerIsAdmin ? 'true' : 'false' }};
    const messagesUrl = @json($viewerIsAdmin ? route('admin.support.messages', $ticket) : route('account.support.messages', $ticket));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let lastId = {{ $ticket->messages->max('id') ?? 0 }};
    let polling = false;

    function scrollDown() { stream.scrollTop = stream.scrollHeight; }
    function esc(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }

    function renderMessage(m) {
        if (document.querySelector('[data-mid="'+m.id+'"]')) return;
        if (m.is_system) {
            const sw = document.createElement('div');
            sw.className = 'flex justify-center';
            sw.setAttribute('data-mid', m.id);
            sw.innerHTML = '<span class="rounded-full bg-slate-100 px-3 py-1 text-center text-[11px] font-semibold text-slate-500">' + esc(m.body) + '</span>';
            stream.appendChild(sw);
            if (m.id > lastId) lastId = m.id;
            return;
        }
        const mine = m.is_admin === viewerIsAdmin;
        const wrap = document.createElement('div');
        wrap.className = 'flex ' + (mine ? 'justify-end' : 'justify-start');
        wrap.setAttribute('data-mid', m.id);
        let inner = '<div class="max-w-[78%] rounded-2xl px-4 py-2.5 text-sm shadow-sm ' +
            (mine ? 'bg-blue-600 text-white' : 'bg-white text-slate-800 ring-1 ring-slate-100') + '">';
        if (!mine) {
            inner += '<p class="mb-0.5 text-[11px] font-bold ' + (m.is_admin ? 'text-blue-600' : 'text-slate-500') + '">' +
                esc(m.author || (m.is_admin ? @json(__('support.staff')) : '')) + '</p>';
        }
        if (m.body) inner += '<p class="whitespace-pre-wrap break-words leading-6">' + esc(m.body).replace(/\n/g,'<br>') + '</p>';
        (m.attachments || []).forEach(function (a) {
            if (a.is_image) {
                inner += '<a href="'+a.url+'" target="_blank" class="mt-2 block"><img src="'+a.preview+'" class="max-h-48 rounded-xl border ' + (mine?'border-blue-400':'border-slate-200') + '"></a>';
            } else {
                inner += '<a href="'+a.url+'" class="mt-2 flex items-center gap-2 rounded-xl px-2 py-1.5 text-xs ' + (mine?'bg-blue-500/40':'bg-slate-100 text-slate-700') + '"><span class="truncate">'+esc(a.name)+'</span><span class="opacity-70">'+esc(a.size)+'</span></a>';
            }
        });
        inner += '<p class="mt-1 text-right text-[10px] ' + (mine?'text-blue-100':'text-slate-400') + '">' + esc(m.time) + '</p></div>';
        wrap.innerHTML = inner;
        stream.appendChild(wrap);
        if (m.id > lastId) lastId = m.id;
    }

    async function poll() {
        if (polling) return;
        polling = true;
        try {
            const res = await fetch(messagesUrl + '?after=' + lastId, { headers: { 'Accept': 'application/json' } });
            if (res.ok) {
                const data = await res.json();
                const before = stream.scrollHeight;
                (data.messages || []).forEach(renderMessage);
                const statusEl = document.getElementById('ticket-status');
                if (data.messages && data.messages.length) scrollDown();
            }
        } catch (e) {}
        polling = false;
    }

    scrollDown();
    setInterval(poll, 4000);

    // Composer
    if (form) {
        const input = document.getElementById('chat-input');
        const fileInput = document.getElementById('chat-files');
        const chips = document.getElementById('file-chips');
        const sendBtn = document.getElementById('chat-send');

        input.addEventListener('input', function () {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 128) + 'px';
        });

        // Reply templates: a compact dropdown; picking one inserts the localized
        // body and sends it right away (no manual typing needed).
        const tplToggle = document.getElementById('tpl-toggle');
        const tplMenu   = document.getElementById('tpl-menu');
        if (tplToggle && tplMenu) {
            const TPL = @json(($templates ?? collect())->pluck('body', 'id'));
            tplToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                tplMenu.classList.toggle('hidden');
            });
            document.addEventListener('click', function (e) {
                if (!tplMenu.contains(e.target) && !tplToggle.contains(e.target)) tplMenu.classList.add('hidden');
            });
            tplMenu.querySelectorAll('[data-tpl]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const body = TPL[this.getAttribute('data-tpl')];
                    tplMenu.classList.add('hidden');
                    if (!body) return;
                    input.value = (input.value.trim() ? input.value.trimEnd() + '\n\n' : '') + body;
                    input.dispatchEvent(new Event('input'));
                    form.requestSubmit();
                });
            });
        }
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
        });
        fileInput.addEventListener('change', function () {
            chips.innerHTML = '';
            Array.from(fileInput.files).forEach(function (f) {
                const chip = document.createElement('span');
                chip.className = 'inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600';
                chip.textContent = f.name;
                chips.appendChild(chip);
            });
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const body = input.value.trim();
            if (!body && fileInput.files.length === 0) return;
            sendBtn.disabled = true;
            const fd = new FormData();
            fd.append('body', input.value);
            Array.from(fileInput.files).forEach(function (f) { fd.append('files[]', f); });
            try {
                const res = await fetch(form.getAttribute('action'), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                });
                if (res.ok) {
                    input.value = ''; input.style.height = 'auto';
                    fileInput.value = ''; chips.innerHTML = '';
                    await poll();
                } else {
                    alert(@json(__('support.send_error')));
                }
            } catch (e) { alert(@json(__('support.send_error'))); }
            sendBtn.disabled = false;
        });
    }
})();
</script>
