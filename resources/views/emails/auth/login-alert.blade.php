<!doctype html>
<html lang="{{ app()->getLocale() }}">
<body style="margin:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;background:#f8fafc;">
    <tr><td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #e2e8f0;border-radius:24px;overflow:hidden;">
            <tr><td style="padding:28px 32px;border-bottom:1px solid #e2e8f0;">
                <strong style="font-size:20px;color:#2563eb;">Crynova</strong>
            </td></tr>
            <tr><td style="padding:32px;">
                <h1 style="margin:0 0 18px;font-size:26px;line-height:1.2;">{{ __('mail.login_alert.title') }}</h1>
                <p style="margin:0 0 20px;color:#475569;line-height:1.7;">{{ __('mail.login_alert.intro', ['name' => $user->name]) }}</p>

                <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                    <tr><td style="padding:12px 16px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:13px;width:120px;">📍 {{ __('mail.login_alert.ip') }}</td>
                        <td style="padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;font-family:monospace;">{{ $ip }}</td></tr>
                    <tr><td style="padding:12px 16px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:13px;">🖥️ {{ __('mail.login_alert.device') }}</td>
                        <td style="padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;word-break:break-all;">{{ $userAgent }}</td></tr>
                    <tr><td style="padding:12px 16px;color:#64748b;font-size:13px;">🗓️ {{ __('mail.login_alert.date') }}</td>
                        <td style="padding:12px 16px;font-size:13px;">{{ $loggedAt }}</td></tr>
                </table>

                <p style="margin:0 0 24px;color:#0f172a;line-height:1.7;"><strong>{{ __('mail.login_alert.important') }}</strong> {{ __('mail.login_alert.warning') }}</p>

                <a href="{{ route('account.dashboard') }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:999px;padding:13px 26px;font-weight:700;">{{ __('mail.login_alert.cta') }}</a>
            </td></tr>
            <tr><td style="padding:22px 32px;background:#f8fafc;color:#64748b;font-size:12px;line-height:1.6;">
                {{ __('mail.login_alert.footer') }}<br>Crynova payment gateway
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
