<x-mail::message>
# Sei iscritto/a a {{ $group->name }}

Ciao **{{ $patient->first_name }}**,

siamo felici di darti il benvenuto! Ti confermiamo l'iscrizione al gruppo **{{ $group->name }}**{{ $group->edition ? ' – '.$group->edition : '' }} su **NutriMente**.

@if($group->description)
{{ $group->description }}
@endif

---

**Dettagli del gruppo:**

@if($group->leader?->name)
- **Conduttrice:** {{ $group->leader->name }}
@endif
@if($group->cadence)
- **Periodicità:** {{ ucfirst($group->cadence) }}
@endif
- **Modalità:** {{ $group->modality === 'online' ? 'Online' : 'In presenza' }}
@if($group->location)
- **Luogo:** {{ $group->location }}
@endif
@if($group->next_meeting_at)
- **Prossimo incontro:** {{ $group->next_meeting_at->format('d/m/Y H:i') }}
@endif

@if($group->modality !== 'online' && ! $group->location)
Ti comunicheremo a breve il luogo degli incontri.
@endif

<x-mail::button :url="config('app.url') . '/login'">
Accedi alla tua area
</x-mail::button>

Per qualsiasi domanda puoi contattare direttamente il tuo professionista.

A presto,<br>
{{ config('app.name') }}
</x-mail::message>
