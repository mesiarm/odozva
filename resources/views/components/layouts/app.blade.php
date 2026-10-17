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
        <header class="flex flex-wrap items-center gap-x-6 gap-y-3 px-4 py-4 sm:px-8 lg:min-h-21 lg:gap-x-9 lg:px-18">
            <a href="{{ route('overview') }}" class="font-display text-xl font-medium">Odozva</a>
            <div class="grow"></div>
            <a href="{{ route('companies.create') }}" class="sm:order-last inline-flex h-10 items-center rounded-full bg-ink px-5 text-sm font-medium whitespace-nowrap text-paper hover:bg-ink/90">+ Pridať firmu</a>
            <nav class="order-last flex w-full gap-6 overflow-x-auto sm:order-none sm:w-auto sm:gap-7" aria-label="Hlavná navigácia">
                @foreach (['overview' => 'Prehľad', 'statistics' => 'Štatistiky', 'later' => 'Do budúcna'] as $route => $label)
                    <a
                        href="{{ route($route) }}"
                        @if (request()->routeIs($route)) aria-current="page" @endif
                        @class([
                            'font-medium text-ink' => request()->routeIs($route),
                            'text-muted hover:text-ink' => ! request()->routeIs($route),
                            'whitespace-nowrap',
                        ])
                    >{{ $label }}</a>
                @endforeach
            </nav>
            {{ $actions ?? '' }}
        </header>

        {{ $slot }}
    </body>
</html>
