@php
    $howFoundLabels = [
        'passaparola' => 'Passaparola / Biglietti da visita',
        'social'      => 'Social',
        'miodottore'  => 'MioDottore',
    ];
    $contactLabels = [
        'whatsapp' => 'WhatsApp',
        'telefono' => 'Telefono',
    ];
    $dayLabels = ['lun' => 'Lun', 'mar' => 'Mar', 'mer' => 'Mer', 'gio' => 'Gio', 'ven' => 'Ven', 'sab' => 'Sab'];
    $slotsByDay = [];
    foreach ($contact->availability ?? [] as $slot) {
        [$d, $h] = explode('|', $slot);
        $slotsByDay[$d][] = $h;
    }
@endphp
<x-mail::message>
# Nuova richiesta di primo contatto

Ciao **{{ $contact->professional->name ?? '' }}**,

hai ricevuto una nuova **Scheda Primo Contatto**.

**Nome:** {{ $contact->fullName() }}
**Email:** {{ $contact->email }}
**Telefono:** {{ $contact->phone ?? '—' }}

**Come ci ha trovato:** {{ collect($contact->how_found ?? [])->map(fn ($v) => $howFoundLabels[$v] ?? $v)->implode(', ') ?: '—' }}
**Come preferisce essere ricontattato:** {{ collect($contact->contact_method ?? [])->map(fn ($v) => $contactLabels[$v] ?? $v)->implode(', ') ?: '—' }}

**Disponibilità indicata:**
@forelse($slotsByDay as $day => $hours)
- **{{ $dayLabels[$day] ?? $day }}:** {{ implode(', ', $hours) }}
@empty
- Nessuna fascia indicata
@endforelse

@if($contact->notes)
**Note:**
{{ $contact->notes }}
@endif

---

Gestisci la richiesta dal tuo inbox:

<x-mail::button :url="route('contact-requests.inbox')" color="success">
Vai all'inbox richieste
</x-mail::button>

Grazie,<br>
{{ config('app.name') }}
</x-mail::message>
