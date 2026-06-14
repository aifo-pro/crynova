<!doctype html>
<html lang="uk">
<body style="margin:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;background:#f8fafc;">
    <tr><td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border:1px solid #e2e8f0;border-radius:24px;overflow:hidden;">
            <tr><td style="padding:28px 32px;border-bottom:1px solid #e2e8f0;">
                <strong style="font-size:20px;color:#2563eb;">Crynova</strong>
                <span style="float:right;background:#ecfdf5;color:#047857;border-radius:999px;padding:6px 12px;font-size:12px;font-weight:700;">{{ strtoupper($invoice->status) }}</span>
            </td></tr>
            <tr><td style="padding:32px;">
                <h1 style="margin:0 0 8px;font-size:28px;line-height:1.2;">{{ __('mail.receipt.title') }}</h1>
                <p style="margin:0 0 24px;color:#475569;line-height:1.7;">{{ __('mail.receipt.text', ['uuid' => $invoice->uuid]) }}</p>
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <tr><td style="padding:12px 0;color:#64748b;border-bottom:1px solid #e2e8f0;">{{ __('mail.receipt.merchant') }}</td><td align="right" style="padding:12px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ $invoice->merchant?->name }}</td></tr>
                    <tr><td style="padding:12px 0;color:#64748b;border-bottom:1px solid #e2e8f0;">Order ID</td><td align="right" style="padding:12px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ $invoice->order_id ?? '—' }}</td></tr>
                    <tr><td style="padding:12px 0;color:#64748b;border-bottom:1px solid #e2e8f0;">{{ __('mail.receipt.amount') }}</td><td align="right" style="padding:12px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ $invoice->amount }} {{ $invoice->currency?->code }}</td></tr>
                    <tr><td style="padding:12px 0;color:#64748b;border-bottom:1px solid #e2e8f0;">{{ __('mail.receipt.received') }}</td><td align="right" style="padding:12px 0;border-bottom:1px solid #e2e8f0;font-weight:700;">{{ $invoice->amount_received }} {{ $invoice->currency?->code }}</td></tr>
                    <tr><td style="padding:12px 0;color:#64748b;">{{ __('mail.receipt.paid_at') }}</td><td align="right" style="padding:12px 0;font-weight:700;">{{ $invoice->paid_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }}</td></tr>
                </table>
                <a href="{{ route('checkout.show', $invoice->uuid) }}" style="display:inline-block;margin-top:24px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:999px;padding:13px 22px;font-weight:700;">{{ __('mail.receipt.open') }}</a>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
