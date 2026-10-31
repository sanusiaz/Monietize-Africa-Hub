<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** New-order alert for the admin. */
class NewOrderAdminMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New order #{$this->order->id} · ".Site::money($this->order->charge));
    }

    public function content(): Content
    {
        $this->order->loadMissing('service.provider', 'user');

        return new Content(view: 'emails.new-order-admin', with: ['order' => $this->order]);
    }
}
