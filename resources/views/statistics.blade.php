<x-layouts.app title="Štatistiky · Odozva">
    <main class="flex max-w-240 flex-col gap-12 px-4 pt-3 pb-14 sm:px-8 lg:px-18">
        <div>
            <h1 class="mt-4 mb-2 font-display text-4xl leading-[1.15] sm:text-[44px] font-normal tracking-tight">Štatistiky</h1>
            <p class="max-w-140 text-[17px] text-subtle">Ako ti ide oslovovanie firiem. Stav k {{ now()->format('j. n. Y') }}.</p>
        </div>

        <section aria-labelledby="by-status">
            <div class="mb-3 flex items-baseline gap-3">
                <h2 id="by-status" class="font-display text-[26px] font-normal">Všetky firmy podľa stavu</h2>
                <span class="text-[13px] text-muted">{{ $total }} {{ $total === 1 ? 'firma' : 'firiem' }} v zozname</span>
            </div>

            @if ($total > 0)
                <div class="mb-4 flex h-7 gap-0.5 overflow-hidden rounded-md" role="img" aria-label="Rozdelenie firiem podľa stavu">
                    @foreach ($statuses as $status)
                        @if ($byStatus[$status->value] > 0)
                            <div class="{{ $status === \App\Enums\ApplicationStatus::Unsent ? 'bg-rule' : $status->dotClass() }}" style="flex: {{ $byStatus[$status->value] }}" title="{{ $status->label() }}"></div>
                        @endif
                    @endforeach
                </div>
            @endif

            <ul class="flex flex-wrap gap-x-7 gap-y-2 text-[13px]">
                @foreach ($statuses as $status)
                    <li class="flex items-center gap-2">
                        <span class="size-3 rounded-sm {{ $status === \App\Enums\ApplicationStatus::Unsent ? 'bg-rule' : $status->dotClass() }}"></span>
                        {{ $status->label() }}
                        <b class="font-medium">{{ $byStatus[$status->value] }}</b>
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="grid gap-x-22 gap-y-12 lg:grid-cols-2">
            <section aria-labelledby="success">
                <h2 id="success" class="mb-4 font-display text-[26px] font-normal">Úspešnosť po odoslaní</h2>

                <div class="mb-6 flex flex-col gap-3">
                    @foreach ([
                        ['Odoslané', $sent, 100, 'bg-sent'],
                        ['Dostali odpoveď', $replied, $replyRate, 'bg-reply'],
                        ['Pohovor', $interviews, $interviewRate, 'bg-interview'],
                    ] as [$label, $count, $percent, $color])
                        <div class="grid grid-cols-[8rem_minmax(0,1fr)_2rem] items-center gap-3">
                            <span class="text-subtle">{{ $label }}</span>
                            <div class="h-5 rounded {{ $color }}" style="width: {{ max($percent, $count > 0 ? 2 : 0) }}%"></div>
                            <b class="text-right font-medium">{{ $count }}</b>
                        </div>
                    @endforeach
                </div>

                <div class="flex gap-10">
                    <div>
                        <div class="font-display text-[40px] leading-none">{{ $replyRate }} %</div>
                        <div class="mt-1 text-[13px] text-muted">odpoveď na odoslané</div>
                    </div>
                    <div>
                        <div class="font-display text-[40px] leading-none text-highlight">{{ $interviewRate }} %</div>
                        <div class="mt-1 text-[13px] text-muted">z odoslaných až pohovor</div>
                    </div>
                </div>
            </section>

            <section aria-labelledby="recent">
                <h2 id="recent" class="mb-3 font-display text-[26px] font-normal">Posledná aktivita</h2>
                <ul class="flex flex-col">
                    @forelse ($recent as $company)
                        <li class="grid grid-cols-[3.5rem_minmax(0,1fr)_auto] items-center gap-3 border-t border-line py-2.5">
                            <span class="text-[13px] text-muted">{{ $company->updated_at->format('j. n.') }}</span>
                            <span class="font-medium">{{ $company->name }}</span>
                            <span class="rounded-full px-3 py-0.5 text-xs font-medium {{ $company->status->softClass() }}">{{ $company->status->label() }}</span>
                        </li>
                    @empty
                        <li class="border-t border-line py-2.5 text-muted">Zatiaľ žiadna aktivita.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </main>
</x-layouts.app>
