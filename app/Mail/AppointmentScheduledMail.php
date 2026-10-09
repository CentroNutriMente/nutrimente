<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Conferma di un appuntamento fissato dalla compilazione di un referto.
 * Stesso messaggio, con testo differenziato per il paziente e per la professionista.
 */
class AppointmentScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public bool $forProfessional = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->forProfessional
                ? 'Nuovo appuntamento fissato – '.config('app.name')
                : 'Il tuo appuntamento è confermato – '.config('app.name'),
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['patient', 'user']);

        return new Content(markdown: 'mail.appointment-scheduled');
    }

    public function attachments(): array { return []; }
}
