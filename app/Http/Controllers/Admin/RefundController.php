<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Refund;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $refunds = Refund::with('merchant', 'invoice', 'currency', 'approvedBy')
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('search'), fn ($q, $s) =>
                $q->whereHas('merchant', fn ($mq) => $mq->where('name', 'like', "%{$s}%"))
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.refunds.index', compact('refunds'));
    }

    public function approve(Request $request, Refund $refund)
    {
        abort_if($refund->isFinal(), 422, 'Refund is already finalized.');

        $refund->update([
            'status'      => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        AuditLog::record('refund.approved', $refund);

        return back()->with('success', 'Refund approved for processing.');
    }

    public function reject(Request $request, Refund $refund)
    {
        abort_if($refund->isFinal(), 422, 'Refund is already finalized.');

        $request->validate(['admin_notes' => ['nullable', 'string', 'max:500']]);

        $refund->update([
            'status'      => 'rejected',
            'admin_notes' => $request->input('admin_notes'),
        ]);

        AuditLog::record('refund.rejected', $refund);

        return back()->with('success', 'Refund rejected.');
    }
}
