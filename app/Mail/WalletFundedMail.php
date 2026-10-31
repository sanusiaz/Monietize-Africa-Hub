<?php

namespace App\Mail;

use App\Models\Payment;
use App\Support\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Payment receipt after a successful Paystack top-up. */
class WalletFundedMail extends Mailable
{
    public function __construct(public Payment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Payment received · '.Site::money($this->payment->amount));
    }

    public function content(): Content
    {
        $this->payment->loadMissing('user');

        return new Content(view: 'emails.wallet-funded', with: ['payment' => $this->payment, 'balance' => Site::money($this->payment->user->balance)]);
    }
}
