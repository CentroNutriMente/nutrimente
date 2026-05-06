<x-mail::message>
# Benvenuto/a in NutriMente!

Ciao **{{ $patient->first_name }}**,

Il tuo professionista ha creato un account personale per te su **NutriMente**. Dalla tua area personale puoi visualizzare i tuoi appuntamenti, referti e fatture, e prenotare nuove sedute direttamente online.

---

**Le tue credenziali di accesso:**

- **Email:** {{ $patient->email }}
- **Password temporanea:** `{{ $temporaryPassword }}`

<x-mail::button :url="config('app.url') . '/login'">
Accedi ora
</x-mail::button>

Dopo il primo accesso ti consigliamo di **cambiare subito la password** dal tuo profilo.

---

**Come accedere:**

1. Vai su {{ config('app.url') }}/login
2. Inserisci la tua email e la password temporanea
3. Dal menu in alto a destra → **Profilo** → cambia la password
4. Accedi alla tua area personale su {{ config('app.url') }}/mia-area

Se hai bisogno di aiuto, rispondi a questa email o contatta direttamente il tuo professionista.

A presto,<br>
{{ config('app.name') }}
</x-mail::message>
