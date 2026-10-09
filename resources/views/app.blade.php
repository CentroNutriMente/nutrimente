<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            // SEO: i controller delle pagine pubbliche passano $meta via withViewData().
            // Tutto renderizzato lato server, così Google lo legge senza eseguire JS.
            $meta = ($meta ?? []) + [
                'title'       => config('app.name', 'NutriMente'),
                'description' => null,
                'canonical'   => null,
                'image'       => null,
                'robots'      => null,
                'jsonld'      => null,
            ];
        @endphp

        <title inertia>{{ $meta['title'] }}</title>
        @auth
            {{-- Le aree riservate non vanno mai indicizzate --}}
            <meta name="robots" content="noindex,nofollow">
        @else
            @if ($meta['robots'])
                <meta name="robots" content="{{ $meta['robots'] }}">
            @endif
        @endauth
        @if ($meta['description'])
            <meta name="description" content="{{ $meta['description'] }}">
        @endif
        @if ($meta['canonical'])
            <link rel="canonical" href="{{ $meta['canonical'] }}">
        @endif

        {{-- Open Graph / Twitter: anteprima nelle condivisioni (WhatsApp, social) --}}
        <meta property="og:site_name" content="{{ config('app.name', 'NutriMente') }}">
        <meta property="og:type" content="website">
        <meta property="og:locale" content="it_IT">
        <meta property="og:title" content="{{ $meta['title'] }}">
        @if ($meta['description'])
            <meta property="og:description" content="{{ $meta['description'] }}">
        @endif
        @if ($meta['canonical'])
            <meta property="og:url" content="{{ $meta['canonical'] }}">
        @endif
        @if ($meta['image'])
            <meta property="og:image" content="{{ $meta['image'] }}">
            <meta name="twitter:card" content="summary_large_image">
        @endif

        @if ($meta['jsonld'])
            <script type="application/ld+json">{!! json_encode($meta['jsonld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endif

        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('logo.jpeg') }}">

        <!-- Fonts (self-hosted — nessuna chiamata a Google, GDPR) -->
        <link href="{{ asset('fonts/fonts.css') }}" rel="stylesheet">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        {{-- Iubenda: banner cookie/consenso + link policy. Solo per i visitatori
             pubblici (l'area professionista usa solo cookie tecnici di sessione). --}}
        @guest
            <script type="text/javascript" src="https://embeds.iubenda.com/widgets/d96ced3b-4518-408d-b27f-526242f4c7bd.js"></script>
            {{-- Loader che trasforma i link .iubenda-embed (Privacy/Cookie Policy) in modali --}}
            <script type="text/javascript" src="https://cdn.iubenda.com/iubenda.js" async></script>
        @endguest
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
