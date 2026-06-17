<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewContactRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactRequest $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nuova richiesta di primo contatto – {$this->contact->fullName()}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.contact-request');
    }

    public function attachments(): array { return []; }
}
