<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderCompletedMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your order #{$this->order->id} is complete");
    }

    public function content(): Content
    {
        $this->order->loadMissing('service', 'user');

        return new Content(view: 'emails.order-completed', with: ['order' => $this->order]);
    }
}
