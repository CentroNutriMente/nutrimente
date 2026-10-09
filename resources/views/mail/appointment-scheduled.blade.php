@php
    $appt    = $appointment;
    $patient = $appt->patient;
    $pro     = $appt->user;
    $data    = \Carbon\Carbon::parse($appt->start_at)->locale('it')->isoFormat('dddd D MMMM YYYY');
    $ora     = \Carbon\Carbon::parse($appt->start_at)->format('H:i');
@endphp
<x-mail::message>
@if($forProfessional)
# Nuovo appuntamento fissato

Ciao **{{ $pro->name }}**,

hai fissato un nuovo appuntamento con **{{ $patient?->first_name }} {{ $patient?->last_name }}**.

**Data:** {{ $data }}
**Orario:** {{ $ora }}

<x-mail::button :url="route('calendar')">
Vai all'agenda
</x-mail::button>
@else
# Il tuo appuntamento è confermato

Ciao **{{ $patient?->first_name }}**,

il tuo prossimo appuntamento con **{{ $pro->name }}** è stato fissato.

**Data:** {{ $data }}
**Orario:** {{ $ora }}

Per qualsiasi esigenza puoi contattare direttamente il tuo professionista.
@endif

A presto,<br>
{{ config('app.name') }}
</x-mail::message>
