<x-mail::message>
# Appuntamento disdetto dal paziente

Ciao **{{ $appointment->user->name }}**,

Il paziente **{{ $patientName }}** ha disdetto il seguente appuntamento:

**Titolo:** {{ $appointment->title }}
**Data e ora:** {{ \Carbon\Carbon::parse($appointment->start_at)->locale('it')->isoFormat('dddd D MMMM YYYY [alle] HH:mm') }}

L'appuntamento è stato rimosso dal tuo calendario.

<x-mail::button :url="config('app.url') . '/calendar'">
Vai al calendario
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
