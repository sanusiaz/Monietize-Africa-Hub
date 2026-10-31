<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Contact-form notification for the admin; replying goes straight to the sender. */
class ContactMessageMail extends Mailable
{
    public function __construct(public ContactMessage $message) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->message->email, $this->message->name)],
            subject: 'New message: '.$this->message->subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact-message', with: ['contact' => $this->message]);
    }
}
