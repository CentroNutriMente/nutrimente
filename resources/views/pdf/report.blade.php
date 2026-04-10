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

        /* ── Header ───────────────────────────────── */
        .header-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }
        .header-left { flex: 1; }
        .header-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .header-subtitle {
            font-size: 11px;
            color: #555;
            margin-bottom: 0;
        }
        .header-logo img {
            max-width: 90px;
            max-height: 60px;
            object-fit: contain;
        }

        /* ── Divider ──────────────────────────────── */
        .divider { border: none; border-top: 1px solid #bbb; margin: 10px 0; }
        .divider-thick { border-top: 2px solid #333; margin: 10px 0; }

        /* ── Patient info row ─────────────────────── */
        .patient-row {
            display: flex;
            gap: 30px;
            font-size: 10.5px;
            margin-bottom: 4px;
            flex-wrap: wrap;
        }
        .patient-field { display: flex; gap: 4px; align-items: baseline; }
        .patient-field .field-label { font-weight: bold; white-space: nowrap; }
        .patient-field .field-val {
            border-bottom: 1px solid #555;
            min-width: 120px;
            padding-bottom: 1px;
        }

        /* ── Sections ─────────────────────────────── */
        .section { margin-top: 18px; }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #333;
            margin-bottom: 4px;
        }
        .section-content {
            font-size: 10.5px;
            line-height: 1.7;
            min-height: 40px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .section-content.empty { color: #aaa; }

        /* ── Next appointment ─────────────────────── */
        .next-appt {
            margin-top: 20px;
            font-size: 10.5px;
        }
        .next-appt .label { font-weight: bold; margin-right: 8px; }
        .next-appt .val {
            border-bottom: 1px solid #555;
            display: inline-block;
            min-width: 200px;
            padding-bottom: 1px;
        }

        /* ── Footer ───────────────────────────────── */
        .footer {
            margin-top: 40px;
            border-top: 1px solid #bbb;
            padding-top: 12px;
        }
        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .footer-signature { font-size: 10px; line-height: 1.7; }
        .footer-signature .sign-label { color: #888; font-size: 9px; margin-bottom: 2px; }
        .footer-signature .sign-name { font-weight: bold; font-size: 11px; }
        .footer-details { font-size: 9px; color: #555; line-height: 1.7; text-align: right; }
        .footer-note { margin-top: 8px; font-size: 9px; color: #777; font-style: italic; }
    </style>
</head>
<body>

@php
    $template = $report->template;
    $profile  = $report->user->professionalProfile ?? null;
    $patient  = $report->patient;
@endphp

{{-- ── HEADER ──────────────────────────────────── --}}
<div class="header-wrap">
    <div class="header-left">
        <div class="header-title">
            {{ $template?->header_title ?? $report->title }}
        </div>
        @if($template?->header_subtitle)
            <div class="header-subtitle">{{ $template->header_subtitle }}</div>
        @endif
    </div>
    @if($template?->header_logo)
    <div class="header-logo">
        <img src="{{ storage_path('app/public/' . $template->header_logo) }}" alt="">
    </div>
    @endif
</div>

<hr class="divider-thick">

{{-- ── PATIENT FIELDS ───────────────────────────── --}}
@if(!$template || $template->show_patient_fields)
<div class="patient-row">
    <div class="patient-field">
        <span class="field-label">Cognome e Nome:</span>
        <span class="field-val">{{ $patient->last_name }} {{ $patient->first_name }}</span>
    </div>
    <div class="patient-field">
        <span class="field-label">Data:</span>
        <span class="field-val">{{ $report->report_date->format('d/m/Y') }}</span>
    </div>
    @if($patient->codice_fiscale)
    <div class="patient-field">
        <span class="field-label">C.F.:</span>
        <span class="field-val">{{ $patient->codice_fiscale }}</span>
    </div>
    @endif
</div>
<hr class="divider">
@endif

{{-- ── SECTIONS ─────────────────────────────────── --}}
@foreach($report->sections_data as $section)
<div class="section">
    <div class="section-title">{{ $section['label'] }}</div>
    @if(!empty($section['content']))
        <div class="section-content">{{ $section['content'] }}</div>
    @else
        <div class="section-content empty">—</div>
    @endif
    <hr class="divider" style="margin-top:12px">
</div>
@endforeach

{{-- ── PROSSIMO APPUNTAMENTO ───────────────────── --}}
@if($report->next_appointment)
<div class="next-appt">
    <span class="label">Prossimo Appuntamento:</span>
    <span class="val">{{ $report->next_appointment }}</span>
</div>
@endif

{{-- ── FOOTER ───────────────────────────────────── --}}
@if(!$template || $template->show_professional_footer)
<div class="footer">
    <div class="footer-inner">
        <div class="footer-signature">
            <div class="sign-label">La/Il Professionista Refertante</div>
            <div class="sign-name">
                {{ $profile?->title ? $profile->title . ' ' : '' }}{{ $report->user->name }}
            </div>
        </div>
        <div class="footer-details">
            @if($profile?->albo_professionale)
                <div>{{ $profile->albo_professionale }}{{ $profile->numero_albo ? ' n° ' . $profile->numero_albo : '' }}</div>
            @endif
            @if($profile?->partita_iva)
                <div>P.IVA {{ $profile->partita_iva }}</div>
            @endif
            @if($report->user->email)
                <div>Mail: {{ $report->user->email }}</div>
            @endif
            @if($profile?->phone)
                <div>Cell.: {{ $profile->phone }}</div>
            @endif
        </div>
    </div>
    @if($template?->footer_note)
        <div class="footer-note">{{ $template->footer_note }}</div>
    @endif
</div>
@endif

</body>
</html>
