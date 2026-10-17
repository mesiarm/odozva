<x-layouts.app title="Do budúcna · Odozva">
    <main class="max-w-200 px-4 pt-3 pb-14 sm:px-8 lg:px-18">
        <h1 class="mt-4 mb-2 font-display text-4xl leading-[1.15] sm:text-[44px] font-normal tracking-tight">Do budúcna</h1>
        <p class="mb-11 max-w-140 text-[17px] text-subtle">Firmy, ku ktorým sa vrátiš, keď sa naučíš niečo nové.</p>

        <div class="flex flex-col gap-10">
            @forelse ($topics as $topic => $companies)
                <section aria-labelledby="topic-{{ $loop->index }}">
                    <div class="mb-2 flex items-baseline gap-3">
                        <h2 id="topic-{{ $loop->index }}" class="rounded-full bg-later-soft px-3.5 py-1.5 text-[13px] font-medium text-later">{{ $topic }}</h2>
                        <span class="text-[13px] text-muted">{{ $companies->count() }} {{ match (true) { $companies->count() === 1 => 'firma', $companies->count() < 5 => 'firmy', default => 'firiem' } }}</span>
                    </div>
                    <ul>
                        @foreach ($companies as $company)
                            <li class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 border-t border-line py-3">
                                <span class="flex min-w-0 flex-col">
                                    <span class="text-[17px] font-medium">{{ $company->name }}</span>
                                    @if ($company->note)
                                        <span class="truncate text-sm text-muted">{{ $company->note }}</span>
                                    @endif
                                </span>
                                <span class="flex items-center gap-2 text-sm font-medium {{ $company->status->textClass() }}">
                                    <span class="size-2 rounded-full {{ $company->status->dotClass() }}"></span>
                                    {{ $company->status->label() }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @empty
                <p class="border-t border-line py-8 text-muted">
                    Zatiaľ tu nič nie je. Pri firme vyplň „Do budúcna“ a objaví sa tu.
                </p>
            @endforelse
        </div>
    </main>
</x-layouts.app>
