<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Order confirmation, sent to the customer who placed it. */
class OrderPlacedMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "We've received your order #{$this->order->id}");
    }

    public function content(): Content
    {
        $this->order->loadMissing('service', 'user');

        return new Content(view: 'emails.order-placed', with: ['order' => $this->order, 'balance' => Site::money($this->order->user->balance)]);
    }
}
