@php
    $accent = $tone === 'lavender' ? '#9079BB' : ($tone === 'blush' ? '#BE7B4A' : '#7C8A5E');
    $soft   = $tone === 'lavender' ? '#EBE5F4' : ($tone === 'blush' ? '#F6E4D2' : '#E4E8D8');
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #3f3b33; }
        .page { padding: 60px 56px; text-align: center; }
        .wash { background: {{ $soft }}; border-radius: 24px; padding: 28px 24px; }
        .eyebrow { letter-spacing: 4px; text-transform: uppercase; font-size: 11px; color: {{ $accent }}; }
        .title { font-size: 40px; font-weight: bold; margin: 14px 0 6px; color: #2f2b25; }
        .cat { display: inline-block; background: #fff; color: {{ $accent }}; font-size: 13px; padding: 6px 16px; border-radius: 999px; margin-top: 6px; }
        .desc { font-size: 15px; line-height: 1.6; color: #6b6459; margin: 26px auto 0; max-width: 440px; }
        .meta { margin: 28px auto 0; max-width: 440px; }
        .meta td { font-size: 13px; color: #6b6459; padding: 6px 10px; text-align: left; }
        .meta .lbl { color: #a39a8c; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        .qrbox { margin: 30px auto 0; width: 240px; }
        .qr { width: 220px; height: 220px; border: 8px solid #fff; border-radius: 18px; }
        .cta { font-size: 16px; font-weight: bold; color: {{ $accent }}; margin-top: 18px; }
        .url { font-size: 12px; color: #a39a8c; margin-top: 6px; word-break: break-all; }
        .brand { margin-top: 40px; font-size: 14px; color: #a39a8c; }
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

        @if($group->description)
            <div class="desc">{{ $group->description }}</div>
        @endif

        <table class="meta" cellspacing="0" cellpadding="0">
            <tr>
                <td><div class="lbl">Conduttore</div>{{ optional($group->leader)->name ?? '—' }}</td>
                <td><div class="lbl">Periodicità</div>{{ $group->cadence ? ucfirst($group->cadence) : '—' }}</td>
            </tr>
            <tr>
                <td><div class="lbl">Modalità</div>{{ $group->modality === 'online' ? 'Online' : 'In presenza' }}</td>
                <td><div class="lbl">Posti</div>{{ $group->capacity }} totali</td>
            </tr>
        </table>

        <div class="qrbox">
            <img class="qr" src="{{ $qrDataUri }}" alt="QR iscrizione">
            <div class="cta">Inquadra per iscriverti</div>
            <div class="url">{{ $url }}</div>
        </div>

        <div class="brand"><b>nutrimente</b> · Studio di psicologia</div>
    </div>
</body>
</html>
