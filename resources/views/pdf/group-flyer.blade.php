@php
    $accent = $tone === 'lavender' ? '#9079BB' : ($tone === 'blush' ? '#BE7B4A' : '#7C8A5E');
    $soft   = $tone === 'lavender' ? '#EBE5F4' : ($tone === 'blush' ? '#F6E4D2' : '#E4E8D8');

    $modalita = $group->modality === 'online' ? 'Online' : 'In presenza';
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #3f3b33; }
        .page { padding: 44px 56px; text-align: center; }

        .wash { background: {{ $soft }}; border-radius: 24px; padding: 24px 24px; }
        .eyebrow { letter-spacing: 3px; text-transform: uppercase; font-size: 11px; color: {{ $accent }}; }
        .title { font-size: 34px; font-weight: bold; margin: 12px 0 8px; color: #2f2b25; line-height: 1.15; }
        .cat { display: inline-block; background: #fff; color: {{ $accent }}; font-size: 13px;
               padding: 6px 16px; border-radius: 999px; }

        .desc { font-size: 14px; line-height: 1.55; color: #6b6459; margin: 18px auto 0; max-width: 460px; }

        .meta { margin: 18px auto 0; max-width: 440px; }
        .meta td { font-size: 13px; color: #4a463e; padding: 6px 12px; text-align: left; vertical-align: top; }
        .meta .lbl { color: #a39a8c; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }

        .qrbox { margin: 22px auto 0; }
        .qr { width: 270px; height: 270px; border: 10px solid #fff; border-radius: 20px; }
        .cta { font-size: 18px; font-weight: bold; color: {{ $accent }}; margin-top: 14px; }
        .cta-sub { font-size: 13px; color: #6b6459; margin-top: 4px; }
        .url { font-size: 11px; color: #a39a8c; margin-top: 6px; word-break: break-all; }

        .brand { margin-top: 26px; font-size: 14px; color: #a39a8c; }
        .brand b { color: {{ $accent }}; font-weight: bold; }
    </style>
</head>
<body>
    <div class="page">
        <div class="wash">
            <div class="eyebrow">Gruppi di aiuto e sostegno</div>
            <div class="title">{{ $group->name }}</div>
            @if($group->edition)<div class="cat">{{ $group->edition }}</div>@endif
        </div>

        <table class="meta" cellspacing="0" cellpadding="0">
            <tr>
                <td><div class="lbl">Conduttrice</div>{{ optional($group->leader)->name ?? '—' }}</td>
                <td><div class="lbl">Periodicità</div>{{ $group->cadence ? ucfirst($group->cadence) : '—' }}</td>
            </tr>
            <tr>
                <td><div class="lbl">Modalità</div>{{ $modalita }}</td>
                <td>
                    @if($group->modality !== 'online' && $group->location)
                        <div class="lbl">Dove</div>{{ $group->location }}
                    @elseif($group->next_meeting_at)
                        <div class="lbl">Primo incontro</div>{{ $group->next_meeting_at->translatedFormat('d F Y') }}
                    @else
                        <div class="lbl">Posti</div>{{ $group->capacity }} totali
                    @endif
                </td>
            </tr>
            @if($group->price_label)
            <tr>
                <td colspan="2"><div class="lbl">Quota di partecipazione</div>{{ $group->price_label }}</td>
            </tr>
            @endif
        </table>

        <div class="qrbox">
            <img class="qr" src="{{ $qrDataUri }}" alt="QR iscrizione">
            <div class="cta">Inquadra il QR per iscriverti</div>
            <div class="cta-sub">Bastano pochi minuti: compili il modulo e ti ricontatteremo.</div>
            <div class="url">{{ $url }}</div>
        </div>

        <div class="brand"><b>nutrimente</b> · Studio di psicologia</div>
    </div>
</body>
</html>
