<x-mail::message>
# Il tuo primo colloquio è fissato

Ciao **{{ $contact->name }}**,

abbiamo fissato il tuo primo colloquio presso **{{ config('app.name') }}**.

**Data:** {{ $startAt->locale('it')->isoFormat('dddd D MMMM YYYY') }}
**Orario:** {{ $startAt->format('H:i') }}
@if($contact->acceptedBy)
**Professionista:** {{ $contact->acceptedBy->name }}
@endif

Se hai bisogno di modificare l'appuntamento, rispondi pure a questa email.

A presto,<br>
{{ config('app.name') }}
</x-mail::message>
