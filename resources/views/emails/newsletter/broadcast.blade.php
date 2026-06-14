<!doctype html>
<html lang="uk">
<body style="margin:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;background:#f8fafc;">
    <tr><td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border:1px solid #e2e8f0;border-radius:24px;overflow:hidden;">
            <tr><td style="padding:28px 32px;border-bottom:1px solid #e2e8f0;"><strong style="font-size:20px;color:#2563eb;">Crynova</strong></td></tr>
            <tr><td style="padding:32px;">
                <h1 style="margin:0 0 18px;font-size:26px;line-height:1.25;">{{ $subjectLine }}</h1>
                <div style="color:#334155;font-size:15px;line-height:1.8;">{!! nl2br(e($body)) !!}</div>
            </td></tr>
            <tr><td style="padding:22px 32px;background:#f8fafc;color:#64748b;font-size:12px;line-height:1.6;">
                {{ __('mail.broadcast.footer') }}
                <br><a href="{{ $unsubscribeUrl }}" style="color:#2563eb;">{{ __('mail.broadcast.unsubscribe') }}</a>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
