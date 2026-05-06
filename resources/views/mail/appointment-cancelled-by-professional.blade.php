<x-mail::message>
# Il tuo appuntamento è stato cancellato

@if($appointment->patient)
Ciao **{{ $appointment->patient->first_name }}**,
@else
Ciao,
@endif

Il tuo appuntamento con **{{ $appointment->user->name }}** è stato cancellato.

**Titolo:** {{ $appointment->title }}
**Data e ora:** {{ \Carbon\Carbon::parse($appointment->start_at)->locale('it')->isoFormat('dddd D MMMM YYYY [alle] HH:mm') }}

Se hai domande o vuoi fissare un nuovo appuntamento, puoi prenotare direttamente online.

<x-mail::button :url="config('app.url') . '/prenota'">
Prenota un nuovo appuntamento
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
