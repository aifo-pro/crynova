<?php

namespace App\Services;

use App\Mail\LoginAlertMail;
use App\Mail\NewsletterBroadcastMail;
use App\Mail\PasswordResetLinkMail;
use App\Mail\PaymentReceiptMail;
use App\Mail\EmailVerificationMail;
use App\Mail\WelcomeRegistrationMail;
use App\Models\NewsletterUnsubscribe;
use App\Models\PaymentInvoice;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailService
{
    public function sendWelcome(User $user): bool
    {
        return $this->send($user->email, new WelcomeRegistrationMail($user), 'welcome');
    }

    public function sendPasswordReset(User $user, string $resetUrl): bool
    {
        return $this->send($user->email, new PasswordResetLinkMail($user, $resetUrl), 'password_reset');
    }

    /** Security alert for a new sign-in (first login of the day). */
    public function sendLoginAlert(User $user, string $ip, string $userAgent, string $loggedAt): bool
    {
        if (! $this->userAllowsAuthEmail($user)) {
            return false;
        }

        return $this->send($user->email, new LoginAlertMail($user, $ip, $userAgent, $loggedAt), 'login_alert');
    }

    public function sendEmailVerification(User $user): bool
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        return $this->send($user->email, new EmailVerificationMail($user, $url), 'email_verification');
    }

    public function sendPaymentReceipt(PaymentInvoice $invoice): void
    {
        $invoice->loadMissing('merchant.user', 'currency', 'transactions');

        $merchantUser = $invoice->merchant?->user;
        if ($merchantUser && $this->userAllowsPaymentEmail($merchantUser)) {
            $this->send($merchantUser->email, new PaymentReceiptMail($invoice, 'merchant'), 'payment_receipt_merchant');
        }

        $customerEmail = $this->customerEmail($invoice);
        if ($customerEmail && (! $merchantUser || mb_strtolower($customerEmail) !== mb_strtolower($merchantUser->email))) {
            $this->send($customerEmail, new PaymentReceiptMail($invoice, 'customer'), 'payment_receipt_customer');
        }
    }

    public function sendNewsletter(User $user, string $subject, string $body): bool
    {
        if (NewsletterUnsubscribe::isUnsubscribed($user->email)) {
            return false;
        }

        $token = NewsletterUnsubscribe::tokenFor($user->email);
        $unsubscribeUrl = route('newsletter.unsubscribe', $token);

        return $this->send(
            $user->email,
            new NewsletterBroadcastMail($subject, $body, $unsubscribeUrl, $user),
            'newsletter',
        );
    }

    private function send(string $email, Mailable $mailable, string $context): bool
    {
        if (! $this->configureMailer()) {
            Log::info("Email skipped: mail disabled [{$context}]", ['email' => $email]);

            return false;
        }

        try {
            Mail::to($email)->send($mailable);

            return true;
        } catch (\Throwable $e) {
            Log::error("Email send failed [{$context}]: " . $e->getMessage(), [
                'email' => $email,
            ]);

            return false;
        }
    }

    private function configureMailer(): bool
    {
        if (! (bool) Setting::get('mail_enabled', false)) {
            return false;
        }

        $mailer = (string) Setting::get('mail_mailer', config('mail.default', 'log'));
        $encryption = strtolower((string) Setting::get('mail_encryption', ''));
        $port = (int) Setting::get('mail_port', config('mail.mailers.smtp.port'));

        // Symfony Mailer (Laravel 11/12) encodes TLS via the SMTP *scheme*, not an
        // "encryption" key. Passing encryption => "tls" raises
        // 'The "tls" scheme is not supported'. Implicit TLS (port 465 / "ssl") →
        // "smtps"; STARTTLS (port 587 / "tls") → "smtp".
        $scheme = ($encryption === 'ssl' || $port === 465) ? 'smtps' : 'smtp';

        config([
            'mail.default' => $mailer,
            'mail.from.address' => Setting::get('mail_from_address', config('mail.from.address')),
            'mail.from.name' => Setting::get('mail_from_name', config('mail.from.name')),
            'mail.mailers.smtp.host' => Setting::get('mail_host', config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => Setting::get('mail_username', config('mail.mailers.smtp.username')),
            'mail.mailers.smtp.password' => Setting::get('mail_password', config('mail.mailers.smtp.password')),
            'mail.mailers.smtp.scheme' => $scheme,
            'mail.mailers.smtp.encryption' => null,
        ]);

        $manager = app('mail.manager');
        if (method_exists($manager, 'forgetMailers')) {
            $manager->forgetMailers();
        }

        return true;
    }

    private function userAllowsPaymentEmail(User $user): bool
    {
        $prefs = array_merge([
            'channel_email' => true,
            'event_paid' => true,
        ], $user->notification_prefs ?? []);

        return (bool) $prefs['channel_email'] && (bool) $prefs['event_paid'];
    }

    private function userAllowsAuthEmail(User $user): bool
    {
        $prefs = array_merge([
            'channel_email' => true,
            'event_auth' => true,
        ], $user->notification_prefs ?? []);

        return (bool) $prefs['channel_email'] && (bool) $prefs['event_auth'];
    }

    private function customerEmail(PaymentInvoice $invoice): ?string
    {
        $metadata = $invoice->metadata ?? [];
        if (! is_array($metadata)) {
            return null;
        }

        foreach (['customer_email', 'email', 'payer_email', 'billing_email'] as $key) {
            $value = $metadata[$key] ?? null;
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }

        $customer = $metadata['customer'] ?? null;
        if (is_array($customer) && isset($customer['email']) && filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
            return $customer['email'];
        }

        return null;
    }
}
