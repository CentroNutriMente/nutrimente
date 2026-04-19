<?php

namespace App\Mail;

use App\Models\BookingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BookingRequest $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Il tuo appuntamento è confermato – ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.booking-confirmed');
    }

    public function attachments(): array { return []; }
}
