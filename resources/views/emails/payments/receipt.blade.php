<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
@php
    $trim = fn ($v) => rtrim(rtrim((string) $v, '0'), '.') ?: '0';
    $code = $invoice->currency?->code;
    $paidAt = $invoice->paid_at?->format('d.m.Y H:i') ?? now()->format('d.m.Y H:i');
@endphp
<body style="margin:0;padding:0;background:#eef2f7;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#0f172a;-webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:40px 16px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 30px rgba(15,23,42,0.08);">

            {{-- Brand bar --}}
            <tr><td style="padding:22px 32px;border-bottom:1px solid #eef2f7;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                    <td style="font-size:20px;font-weight:800;letter-spacing:-0.02em;color:#2563eb;">Crynova</td>
                    <td align="right"><span style="display:inline-block;background:#ecfdf5;color:#059669;border-radius:999px;padding:6px 14px;font-size:11px;font-weight:800;letter-spacing:0.04em;">✓ {{ strtoupper($invoice->status) }}</span></td>
                </tr></table>
            </td></tr>

            {{-- Success hero --}}
            <tr><td align="center" style="padding:40px 32px 8px;">
                <table role="presentation" cellpadding="0" cellspacing="0"><tr><td align="center"
                    style="width:72px;height:72px;background:#ecfdf5;border-radius:50%;text-align:center;vertical-align:middle;font-size:34px;line-height:72px;color:#10b981;">✓</td></tr></table>
                <h1 style="margin:22px 0 6px;font-size:24px;font-weight:800;letter-spacing:-0.02em;color:#0f172a;">{{ __('mail.receipt.title') }}</h1>
                <p style="margin:0;color:#64748b;font-size:14px;line-height:1.6;">{{ __('mail.receipt.text', ['uuid' => $invoice->uuid]) }}</p>
            </td></tr>

            {{-- Amount --}}
            <tr><td style="padding:24px 32px 8px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:16px;">
                    <tr><td align="center" style="padding:22px;">
                        <div style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">{{ __('mail.receipt.amount') }}</div>
                        <div style="margin-top:6px;font-size:30px;font-weight:800;letter-spacing:-0.02em;color:#0f172a;word-break:break-all;">{{ $trim($invoice->amount) }} <span style="color:#2563eb;">{{ $code }}</span></div>
                    </td></tr>
                </table>
            </td></tr>

            {{-- Details --}}
            <tr><td style="padding:16px 32px 8px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
                    <tr>
                        <td style="padding:13px 0;color:#64748b;border-bottom:1px solid #f1f5f9;">{{ __('mail.receipt.merchant') }}</td>
                        <td align="right" style="padding:13px 0;border-bottom:1px solid #f1f5f9;font-weight:700;">{{ $invoice->merchant?->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:13px 0;color:#64748b;border-bottom:1px solid #f1f5f9;">Order ID</td>
                        <td align="right" style="padding:13px 0;border-bottom:1px solid #f1f5f9;font-weight:700;">{{ $invoice->order_id ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:13px 0;color:#64748b;border-bottom:1px solid #f1f5f9;">{{ __('mail.receipt.received') }}</td>
                        <td align="right" style="padding:13px 0;border-bottom:1px solid #f1f5f9;font-weight:700;word-break:break-all;">{{ $trim($invoice->amount_received) }} {{ $code }}</td>
                    </tr>
                    <tr>
                        <td style="padding:13px 0;color:#64748b;">{{ __('mail.receipt.paid_at') }}</td>
                        <td align="right" style="padding:13px 0;font-weight:700;">{{ $paidAt }}</td>
                    </tr>
                </table>
            </td></tr>

            {{-- Reference --}}
            <tr><td style="padding:4px 32px 0;">
                <div style="font-size:11px;color:#94a3b8;letter-spacing:0.04em;">{{ __('checkout.invoice') }}: <span style="font-family:monospace;color:#64748b;">{{ $invoice->uuid }}</span></div>
            </td></tr>

            {{-- CTA --}}
            <tr><td align="center" style="padding:24px 32px 36px;">
                <a href="{{ route('checkout.show', $invoice->uuid) }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:12px;padding:14px 28px;font-size:14px;font-weight:700;box-shadow:0 6px 16px rgba(37,99,235,0.25);">{{ __('mail.receipt.open') }}</a>
            </td></tr>

            {{-- Footer --}}
            <tr><td style="padding:20px 32px;background:#f8fafc;border-top:1px solid #eef2f7;text-align:center;">
                <div style="font-size:12px;color:#94a3b8;line-height:1.6;">{{ __('checkout.powered_by') }} · &copy; {{ date('Y') }} Crynova</div>
            </td></tr>

        </table>
    </td></tr>
</table>
</body>
</html>
