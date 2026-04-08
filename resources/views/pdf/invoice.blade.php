<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .issuer h2 { font-size: 18px; margin: 0 0 4px; color: #2d6a4f; }
        .issuer p { margin: 2px 0; color: #555; }
        .invoice-meta { text-align: right; }
        .invoice-meta h1 { font-size: 22px; margin: 0; color: #2d6a4f; }
        .invoice-meta p { margin: 2px 0; }
        .divider { border-top: 2px solid #2d6a4f; margin: 20px 0; }
        .parties { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .party-block { width: 48%; }
        .party-block h4 { margin: 0 0 6px; text-transform: uppercase; color: #888; font-size: 9px; letter-spacing: 1px; }
        .party-block p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #2d6a4f; color: white; padding: 8px; text-align: left; font-size: 10px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) td { background: #f9f9f9; }
        .totals { margin-top: 16px; text-align: right; }
        .totals table { width: 280px; margin-left: auto; }
        .totals td { border: none; padding: 4px 8px; }
        .totals .total-row td { font-weight: bold; font-size: 14px; border-top: 2px solid #2d6a4f; }
        .footer { margin-top: 40px; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .exempt-note { margin-top: 20px; font-size: 9px; color: #555; font-style: italic; }
        .stamp-note { color: #c0392b; font-size: 9px; margin-top: 8px; }
    </style>
</head>
<body>

<div class="header">
    <div class="issuer">
        <h2>{{ $invoice->issuer_name }}</h2>
        @if($invoice->issuer_partita_iva)
            <p>P.IVA: {{ $invoice->issuer_partita_iva }}</p>
        @endif
        @if($invoice->issuer_codice_fiscale)
            <p>C.F.: {{ $invoice->issuer_codice_fiscale }}</p>
        @endif
        @if($invoice->issuer_address)
            <p>{{ $invoice->issuer_address }}</p>
        @endif
    </div>
    <div class="invoice-meta">
        <h1>FATTURA</h1>
        <p><strong>N. {{ $invoice->invoice_code }}</strong></p>
        <p>Data: {{ $invoice->issued_at->format('d/m/Y') }}</p>
        <p>Regime: {{ ucfirst($invoice->issuer_regime_fiscale) }}</p>
    </div>
</div>

<div class="divider"></div>

<div class="parties">
    <div class="party-block">
        <h4>Emessa da</h4>
        <p><strong>{{ $invoice->issuer_name }}</strong></p>
        @if($invoice->issuer_partita_iva)<p>P.IVA: {{ $invoice->issuer_partita_iva }}</p>@endif
    </div>
    <div class="party-block">
        <h4>Intestata a</h4>
        <p><strong>{{ $invoice->client_name }}</strong></p>
        @if($invoice->client_codice_fiscale)<p>C.F.: {{ $invoice->client_codice_fiscale }}</p>@endif
        @if($invoice->client_address)<p>{{ $invoice->client_address }}</p>@endif
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:60%">Descrizione</th>
            <th style="width:10%; text-align:center">Qtà</th>
            <th style="width:15%; text-align:right">Prezzo unitario</th>
            <th style="width:15%; text-align:right">Totale</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->lines as $line)
        <tr>
            <td>{{ $line->description }}</td>
            <td style="text-align:center">{{ $line->quantity }}</td>
            <td style="text-align:right">€ {{ number_format($line->unit_price, 2, ',', '.') }}</td>
            <td style="text-align:right">€ {{ number_format($line->total, 2, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    <table>
        <tr>
            <td>Imponibile</td>
            <td style="text-align:right">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
        </tr>
        @if($invoice->iva_exempt)
        <tr>
            <td>IVA</td>
            <td style="text-align:right">Esente</td>
        </tr>
        @endif
        @if($invoice->marca_da_bollo > 0)
        <tr>
            <td>Marca da bollo</td>
            <td style="text-align:right">€ {{ number_format($invoice->marca_da_bollo, 2, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td>TOTALE</td>
            <td style="text-align:right">€ {{ number_format($invoice->total, 2, ',', '.') }}</td>
        </tr>
    </table>
</div>

@if($invoice->iva_exempt)
<p class="exempt-note">
    Operazione esente IVA ai sensi dell'art. 10, n. 18 del D.P.R. 26/10/1972 n. 633.
</p>
@endif

@if($invoice->marca_da_bollo > 0)
<p class="stamp-note">
    Marca da bollo assolta in modo virtuale ai sensi del D.M. 17/06/2014 (importo superiore a € 77,47).
</p>
@endif

@if($invoice->payment_method)
<p style="margin-top: 16px;">
    <strong>Metodo di pagamento:</strong> {{ ucfirst($invoice->payment_method) }}
</p>
@endif

<div class="footer">
    <p>Documento generato il {{ now()->format('d/m/Y') }} &mdash; Centro Nutrimento</p>
    <p>Prestazione sanitaria soggetta a segreto professionale. Non costituisce sostituto di servizi di emergenza.</p>
</div>

</body>
</html>
