<?php

namespace App\Mail;

use App\Support\Site;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TestMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Test email from '.Site::name());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.test');
    }
}
