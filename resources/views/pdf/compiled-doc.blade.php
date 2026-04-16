<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1a1a1a; line-height: 1.5; }

    .page-header {
        border-bottom: 2px solid #4f46e5;
        padding-bottom: 10px;
        margin-bottom: 18px;
    }
    .doc-title {
        font-size: 15pt;
        font-weight: bold;
        color: #4f46e5;
        margin-bottom: 3px;
    }
    .doc-meta {
        font-size: 8pt;
        color: #6b7280;
    }

    .section {
        margin-bottom: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
    }
    .section-header {
        background-color: #f5f3ff;
        border-bottom: 1px solid #e5e7eb;
        padding: 6px 12px;
        font-size: 10pt;
        font-weight: bold;
        color: #4f46e5;
    }
    .section-body {
        padding: 10px 12px;
    }
    .section-intro {
        font-style: italic;
        color: #6b7280;
        margin-bottom: 8px;
        font-size: 9pt;
    }

    .item {
        margin-bottom: 8px;
    }
    .item:last-child { margin-bottom: 0; }

    /* Paragraph */
    .item-paragraph {
        font-size: 9.5pt;
        color: #374151;
        padding: 4px 0;
    }

    /* Field */
    .item-field {}
    .field-label {
        font-size: 8.5pt;
        font-weight: bold;
        color: #6b7280;
        margin-bottom: 2px;
    }
    .field-value {
        font-size: 10pt;
        color: #111827;
        border-bottom: 1px solid #9ca3af;
        padding: 2px 0 3px 0;
        min-height: 18px;
    }
    .field-value-empty {
        color: #d1d5db;
        font-style: italic;
    }
    .field-value-multiline {
        border: 1px solid #d1d5db;
        border-radius: 2px;
        padding: 4px 6px;
        min-height: 36px;
        white-space: pre-wrap;
    }

    /* Checkbox group */
    .item-checkbox-group {}
    .checkbox-group-label {
        font-size: 8.5pt;
        font-weight: bold;
        color: #6b7280;
        margin-bottom: 4px;
    }
    .checkbox-row {
        display: block;
        margin-bottom: 4px;
        font-size: 10pt;
    }
    .checkbox-box {
        display: inline-block;
        width: 12px;
        height: 12px;
        border: 1.5px solid #4b5563;
        text-align: center;
        vertical-align: middle;
        margin-right: 5px;
        font-size: 10pt;
        line-height: 11px;
    }
    .checkbox-label { vertical-align: middle; }

    .page-footer {
        margin-top: 24px;
        border-top: 1px solid #e5e7eb;
        padding-top: 8px;
        font-size: 7.5pt;
        color: #9ca3af;
        text-align: center;
    }
</style>
</head>
<body>

<div class="page-header">
    <div class="doc-title">{{ $title }}</div>
    <div class="doc-meta">Generato il {{ $date }} &nbsp;·&nbsp; Template: {{ $template->name }}</div>
</div>

@foreach ($sections as $section)
<div class="section">
    <div class="section-header">{{ $section['title'] }}</div>
    <div class="section-body">
        @if (!empty($section['intro']))
            <div class="section-intro">{{ $section['intro'] }}</div>
        @endif

        @foreach ($section['items'] as $item)
        <div class="item">

            {{-- PARAGRAPH --}}
            @if ($item['type'] === 'paragraph')
                <div class="item-paragraph">{{ $item['content'] }}</div>

            {{-- FIELD --}}
            @elseif ($item['type'] === 'field')
                <div class="item-field">
                    <div class="field-label">{{ $item['label'] }}</div>
                    @if (!empty($item['multiline']))
                        <div class="field-value field-value-multiline">{{ $item['value'] ?: ' ' }}</div>
                    @else
                        <div class="field-value {{ empty($item['value']) ? 'field-value-empty' : '' }}">
                            {{ $item['value'] ?: '—' }}
                        </div>
                    @endif
                </div>

            {{-- CHECKBOX GROUP --}}
            @elseif ($item['type'] === 'checkbox_group')
                <div class="item-checkbox-group">
                    <div class="checkbox-group-label">{{ $item['label'] }}</div>
                    @foreach ($item['checkboxes'] as $cb)
                    <div class="checkbox-row">
                        <span class="checkbox-box">{!! $cb['checked'] ? '&#x2713;' : '' !!}</span>
                        <span class="checkbox-label">{{ $cb['label'] }}</span>
                    </div>
                    @endforeach
                </div>
            @endif

        </div>
        @endforeach
    </div>
</div>
@endforeach

<div class="page-footer">
    Centro NutriMente · Documento generato automaticamente
</div>
</body>
</html>
