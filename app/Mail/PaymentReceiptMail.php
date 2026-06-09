<?php

namespace App\Mail;

use App\Models\PaymentInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PaymentInvoice $invoice,
        public string $recipientType = 'merchant',
    ) {}

    public function build(): self
    {
        return $this->subject('Crynova payment receipt #' . substr($this->invoice->uuid, 0, 8))
            ->view('emails.payments.receipt');
    }
}
