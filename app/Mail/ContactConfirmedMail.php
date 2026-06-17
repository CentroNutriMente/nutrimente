<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactRequest $contact, public Carbon $startAt) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Il tuo primo colloquio è fissato – ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.contact-confirmed');
    }

    public function attachments(): array { return []; }
}
