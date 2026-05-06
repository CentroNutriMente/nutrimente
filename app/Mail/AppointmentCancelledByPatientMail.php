<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentCancelledByPatientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment, public string $patientName) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Appuntamento disdetto – {$this->patientName}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.appointment-cancelled-by-patient');
    }

    public function attachments(): array { return []; }
}
