<x-mail::message>
# Il tuo appuntamento è confermato!

Ciao **{{ $booking->patient_name }}**,

Il tuo appuntamento con **{{ $booking->professional->name }}** è stato confermato.

**Data:** {{ \Carbon\Carbon::parse($booking->requested_date)->locale('it')->isoFormat('dddd D MMMM YYYY') }}
**Orario:** {{ substr($booking->requested_time, 0, 5) }}

---

Vuoi accedere alla tua area personale per gestire i tuoi appuntamenti, visualizzare i referti e le fatture?

**Registrati gratuitamente** su {{ config('app.name') }} usando la tua email **{{ $booking->patient_email }}**:

<x-mail::button :url="route('register', ['email' => $booking->patient_email, 'invite' => $booking->invite_token])">
Accedi alla tua area personale
</x-mail::button>

Se hai già un account, puoi accedere direttamente:

<x-mail::button :url="route('login')">
Accedi
</x-mail::button>

A presto,<br>
{{ config('app.name') }}
</x-mail::message>
