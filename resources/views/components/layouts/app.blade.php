@props(['title' => 'Odozva'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-paper font-sans text-[15px] leading-normal text-ink antialiased">
        <header class="flex h-21 items-center gap-6 px-8 lg:gap-9 lg:px-18">
            <a href="{{ route('overview') }}" class="font-display text-xl font-medium">Odozva</a>
            <div class="grow"></div>
            <nav class="hidden gap-7 sm:flex" aria-label="Hlavná navigácia">
                @foreach (['overview' => 'Prehľad', 'statistics' => 'Štatistiky', 'later' => 'Do budúcna'] as $route => $label)
                    <a
                        href="{{ route($route) }}"
                        @if (request()->routeIs($route)) aria-current="page" @endif
                        @class([
                            'font-medium text-ink' => request()->routeIs($route),
                            'text-muted hover:text-ink' => ! request()->routeIs($route),
                        ])
                    >{{ $label }}</a>
                @endforeach
            </nav>
            {{ $actions ?? '' }}
            <a href="{{ route('companies.create') }}" class="inline-flex h-10 items-center rounded-full bg-ink px-5 text-sm font-medium whitespace-nowrap text-paper hover:bg-ink/90">+ Pridať firmu</a>
        </header>

        {{ $slot }}
    </body>
</html>
