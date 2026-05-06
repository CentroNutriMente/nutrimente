<?php

namespace App\Mail;

use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PatientWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Patient $patient,
        public string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Benvenuto/a in NutriMente – Accedi alla tua area personale',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.patient-welcome');
    }

    public function attachments(): array { return []; }
}
