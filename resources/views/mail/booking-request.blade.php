<x-mail::message>
# Nuova richiesta di appuntamento

Ciao **{{ $booking->professional->name }}**,

Hai ricevuto una nuova richiesta di appuntamento.

**Paziente:** {{ $booking->fullName() }}
**Email:** {{ $booking->patient_email }}
**Telefono:** {{ $booking->patient_phone ?? '—' }}
**Data richiesta:** {{ \Carbon\Carbon::parse($booking->requested_date)->locale('it')->isoFormat('dddd D MMMM YYYY') }}
**Orario:** {{ substr($booking->requested_time, 0, 5) }}

@if($booking->notes)
**Note del paziente:**
{{ $booking->notes }}
@endif

---

Conferma o rifiuta la richiesta dal pannello:

<x-mail::button :url="route('booking.confirm', [$booking->professional->professionalProfile->slug, $booking->confirm_token])" color="success">
✓ Conferma appuntamento
</x-mail::button>

<x-mail::button :url="route('booking.reject', [$booking->professional->professionalProfile->slug, $booking->reject_token])">
✗ Rifiuta richiesta
</x-mail::button>

Grazie,<br>
{{ config('app.name') }}
</x-mail::message>
