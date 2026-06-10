<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $ip,
        public string $userAgent,
        public string $loggedAt,
    ) {}

    public function build(): self
    {
        return $this->subject(__('mail.login_alert.subject'))
            ->view('emails.auth.login-alert');
    }
}
