<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->input('action'), fn ($q, $a) => $q->where('action', 'like', "%{$a}%"))
            ->when($request->input('user_id'), fn ($q, $u) => $q->where('user_id', $u))
            ->orderByDesc('created_at')
            ->paginate(40);

        return view('admin.audit-logs.index', compact('logs'));
    }
}
