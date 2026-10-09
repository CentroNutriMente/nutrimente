<?php

namespace App\Mail;

use App\Models\Group;
use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GroupParticipationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Patient $patient,
        public Group $group,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Iscrizione al gruppo '.$this->group->name.' – NutriMente',
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.group-participation');
    }

    public function attachments(): array { return []; }
}
