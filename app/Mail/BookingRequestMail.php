<?php

namespace App\Mail;

use App\Models\BookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BookingRequest $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nuova richiesta di appuntamento – {$this->booking->fullName()}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.booking-request');
    }

    public function attachments(): array { return []; }
}
