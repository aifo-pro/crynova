<!doctype html>
<html lang="uk">
<body style="margin:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;background:#f8fafc;">
    <tr><td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #e2e8f0;border-radius:24px;overflow:hidden;">
            <tr><td style="padding:28px 32px;border-bottom:1px solid #e2e8f0;"><strong style="font-size:20px;color:#2563eb;">Crynova</strong></td></tr>
            <tr><td style="padding:32px;">
                <h1 style="margin:0 0 14px;font-size:28px;line-height:1.2;">{{ __('mail.reset.title') }}</h1>
                <p style="margin:0 0 20px;color:#475569;line-height:1.7;">{{ __('mail.reset.text', ['email' => $user->email]) }}</p>
                <a href="{{ $resetUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:999px;padding:13px 22px;font-weight:700;">{{ __('mail.reset.cta') }}</a>
                <p style="margin:22px 0 0;color:#64748b;font-size:13px;line-height:1.6;">{{ __('mail.reset.ignore') }}</p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
