<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            padding: 40px 50px;
        }

        /* ── Header ─────────────────────────────────────── */
        .header {
            width: 100%;
            margin-bottom: 24px;
        }
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .header-left h1 {
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        .meta p {
            font-size: 10.5px;
            line-height: 1.85;
        }
        .meta p strong {
            font-weight: bold;
        }
        .header-photo img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 3px;
        }

        /* ── Divider ─────────────────────────────────────── */
        .divider {
            border: none;
            border-top: 1px solid #cccccc;
            margin: 22px 0;
        }

        /* ── Parties (DA / A) ────────────────────────────── */
        .parties {
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
        .party {
            width: 47%;
        }
        .party .label {
            font-size: 9px;
            color: #888888;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }
        .party .party-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .party .detail p {
            font-size: 10px;
            line-height: 1.75;
        }
        .party .detail strong {
            font-weight: bold;
        }

        /* ── Lines table ─────────────────────────────────── */
        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.lines thead tr {
            border-top: 1px solid #cccccc;
            border-bottom: 1px solid #cccccc;
        }
        table.lines th {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #444444;
            padding: 8px 6px;
            letter-spacing: 0.2px;
        }
        table.lines td {
            padding: 12px 6px;
            font-size: 10.5px;
            vertical-align: top;
        }
        table.lines td.amount {
            font-weight: bold;
            text-align: right;
        }

        /* ── Totals ──────────────────────────────────────── */
        .totals-wrap {
            margin-top: 16px;
            float: right;
            width: 340px;
        }
        .totals-wrap table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-wrap td {
            font-size: 10.5px;
            padding: 5px 6px;
            color: #1a1a1a;
        }
        .totals-wrap td:last-child {
            text-align: right;
        }
        .totals-wrap tr.grand-total td {
            font-size: 14px;
            font-weight: bold;
            padding-top: 12px;
        }
        .totals-wrap tr.grand-total td:first-child {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* ── Osservazioni ────────────────────────────────── */
        .clear { clear: both; }
        .osservazioni {
            margin-top: 10px;
            font-size: 10.5px;
            color: #555;
        }
    </style>
</head>
<body>

{{-- ── HEADER ──────────────────────────────────────────── --}}
@php
    $profile = $invoice->user->professionalProfile ?? null;
@endphp

<div class="header">
    <div class="header-inner">
        <div class="header-left">
            <h1>Fattura</h1>
            <div class="meta">
                <p><strong>Nº: {{ str_pad($invoice->invoice_number, 2, '0', STR_PAD_LEFT) }}</strong></p>
                <p><strong>Data fattura:</strong> {{ $invoice->issued_at->format('d/m/Y') }}</p>
                <p><strong>Metodo di pagamento:</strong>{{ $invoice->payment_method ?? '—' }}</p>
                <p><strong>Data di pagamento:</strong>{{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y') : '—' }}</p>
                <p>Rifiuta invio al sistema TS: {{ $invoice->sts_sent ? 'Sì' : 'No' }}</p>
            </div>
        </div>
        @if($profile && $profile->photo)
        <div class="header-photo">
            <img src="{{ storage_path('app/public/' . $profile->photo) }}" alt="">
        </div>
        @endif
    </div>
</div>

<hr class="divider">

{{-- ── DA / A ───────────────────────────────────────────── --}}
<div class="parties">

    {{-- Issuer --}}
    <div class="party">
        <div class="label">DA</div>
        <div class="party-name">{{ $invoice->issuer_name }}</div>
        <div class="detail">
            @if($invoice->issuer_address)
                <p><strong>Indirizzo:</strong> {{ $invoice->issuer_address }}</p>
            @endif
            @if($invoice->issuer_codice_fiscale)
                <p><strong>Codice Fiscale:</strong> {{ $invoice->issuer_codice_fiscale }}</p>
            @endif
            @if($invoice->issuer_partita_iva)
                <p><strong>Numero partita IVA:</strong> {{ $invoice->issuer_partita_iva }}</p>
            @endif
            @if($profile && $profile->albo_professionale)
                <p><strong>Albo:</strong> {{ $profile->albo_professionale }}{{ $profile->numero_albo ? ' · ' . $profile->numero_albo : '' }}</p>
            @endif
        </div>
    </div>

    {{-- Client --}}
    <div class="party">
        <div class="label">A</div>
        <div class="party-name">{{ $invoice->client_name }}</div>
        <div class="detail">
            @if($invoice->client_address)
                <p><strong>Indirizzo:</strong> {{ $invoice->client_address }}</p>
            @endif
            @php
                $clientPhone = $invoice->client_phone ?? ($invoice->patient->phone ?? null);
                $clientEmail = $invoice->client_email ?? ($invoice->patient->email ?? null);
            @endphp
            @if($clientPhone)
                <p><strong>Telefono:</strong> {{ $clientPhone }}</p>
            @endif
            @if($clientEmail)
                <p><strong>E-mail:</strong> {{ $clientEmail }}</p>
            @endif
            @if($invoice->client_codice_fiscale)
                <p><strong>Codice Fiscale:</strong> {{ $invoice->client_codice_fiscale }}</p>
            @endif
        </div>
    </div>

</div>

<hr class="divider">

{{-- ── LINES TABLE ─────────────────────────────────────── --}}
<table class="lines">
    <colgroup>
        <col style="width:8%">
        <col style="width:30%">
        <col style="width:8%">
        <col style="width:12%">
        <col style="width:14%">
        <col style="width:10%">
        <col style="width:18%">
    </colgroup>
    <thead>
        <tr>
            <th style="width:8%">CODICE</th>
            <th style="width:30%">PRESTAZIONE</th>
            <th style="width:8%">IVA</th>
            <th style="width:12%">QUANTITÀ</th>
            <th style="width:14%">PREZZO</th>
            <th style="width:10%">SCONTO</th>
            <th style="width:18%; text-align:right">IMPORTO TOTALE</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->lines as $line)
        <tr>
            <td style="width:8%"></td>
            <td style="width:30%">{{ $line->description }}</td>
            <td style="width:8%">{{ $invoice->iva_exempt ? '0%' : '22%' }}</td>
            <td style="width:12%">{{ $line->quantity }}</td>
            <td style="width:14%">{{ number_format($line->unit_price, 2, ',', '.') }} €</td>
            <td style="width:10%"></td>
            <td style="width:18%; text-align:right; font-weight:bold">{{ number_format($line->total, 2, ',', '.') }} €</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ── TOTALS ───────────────────────────────────────────── --}}
<div class="totals-wrap">
    <table>
        <tr>
            <td>Base imponibile</td>
            <td>{{ number_format($invoice->subtotal, 2, ',', '.') }} €</td>
        </tr>
        <tr>
            <td>Totale imposta</td>
            <td>{{ $invoice->iva_exempt ? number_format(0, 2, ',', '.') : number_format($invoice->subtotal * 0.22, 2, ',', '.') }} €</td>
        </tr>
        @if($invoice->marca_da_bollo > 0)
        <tr>
            <td>Marca da bollo</td>
            <td>{{ number_format($invoice->marca_da_bollo, 2, ',', '.') }} €</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td>IMPORTO TOTALE</td>
            <td>{{ number_format($invoice->total, 2, ',', '.') }} €</td>
        </tr>
    </table>
</div>

<div class="clear"></div>
<hr class="divider">

{{-- ── OSSERVAZIONI ─────────────────────────────────────── --}}
<div class="osservazioni">
    Osservazioni:
</div>

</body>
</html>
