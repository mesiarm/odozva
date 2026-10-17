@use('App\Enums\ApplicationStatus')
<x-layouts.app title="Odozva">
        <x-slot:actions>
            <form method="GET" action="{{ route('overview') }}" role="search" class="w-full sm:w-auto">
                @if ($activeStatus)
                    <input type="hidden" name="stav" value="{{ $activeStatus->value }}">
                @endif
                <label class="flex h-10 w-full items-center gap-2 sm:w-56 rounded-full bg-soft px-4 focus-within:ring-2 focus-within:ring-ink">
                    <svg class="size-4 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <span class="sr-only">Nájsť firmu</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Nájsť firmu" class="min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-muted">
                </label>
            </form>
        </x-slot:actions>

        <main class="grid grid-cols-1 items-start gap-x-22 gap-y-12 px-4 pt-3 pb-14 sm:px-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:px-18">
            <div class="min-w-0">
                <h1 class="mt-4 mb-2 max-w-160 font-display text-4xl leading-[1.15] sm:text-[44px] font-normal tracking-tight">
                    @if ($interviewCompany)
                        V {{ $interviewCompany->name }} ťa čaká <span class="text-highlight">pohovor</span>.
                    @else
                        Tvoje reakcie na jednom mieste.
                    @endif
                </h1>
                <p class="mb-9 max-w-140 text-[17px] text-subtle">
                    Ostatné správy sú vonku a na jednu už niekto odpovedal. Zvyšok si môžeš vybaviť v pokoji.
                </p>

                <dl class="mb-3 flex max-w-155 items-start">
                    <div class="flex min-w-20 flex-col gap-0.5 sm:min-w-27">
                        <dd class="order-1 font-display text-4xl leading-none sm:text-[40px]">{{ $summary['sent'] }}</dd>
                        <dt class="order-2 text-[13px] text-muted">poslaných</dt>
                    </div>
                    <div class="mx-2 mt-5 h-px min-w-3 flex-1 bg-rule sm:mx-3"></div>
                    <div class="flex min-w-20 flex-col gap-0.5 sm:min-w-27">
                        <dd class="order-1 font-display text-4xl leading-none sm:text-[40px]">{{ $summary['replies'] }}</dd>
                        <dt class="order-2 text-[13px] text-muted">odpoveď</dt>
                    </div>
                    <div class="mx-2 mt-5 h-px min-w-3 flex-1 bg-rule sm:mx-3"></div>
                    <div class="flex min-w-20 flex-col gap-0.5 sm:min-w-27">
                        <dd class="order-1 font-display text-4xl leading-none sm:text-[40px] text-highlight">{{ $summary['interviews'] }}</dd>
                        <dt class="order-2 text-[13px] text-muted">pohovor</dt>
                    </div>
                </dl>
                <p class="mb-11 text-[13px] text-muted">
                    Odpoveď prišla od {{ $summary['replyRate'] }} % oslovených firiem. Tentoraz to nevyšlo u {{ $summary['rejected'] }} firiem, {{ $summary['unsent'] }} o tebe ešte nevie.
                </p>

                <div class="mb-2 flex flex-wrap items-baseline gap-x-5 gap-y-2">
                    <h2 class="mr-2 font-display text-[26px] font-normal">Tvoje firmy</h2>
                    @foreach ($filters as $filter)
                        @php($isActive = $filter === $activeStatus)
                        <a
                            href="{{ route('overview', array_filter(['stav' => $filter?->value, 'q' => $search])) }}"
                            @if ($isActive) aria-current="true" @endif
                            @class([
                                'rounded-full px-3.5 py-1 text-[13px]',
                                'bg-ink text-paper' => $isActive,
                                'text-subtle hover:bg-soft' => ! $isActive,
                            ])
                        >{{ $filter?->label() ?? 'Všetky' }}</a>
                    @endforeach
                </div>

                <div class="border-b border-line">
                    @forelse ($companies as $company)
                        @php($isOpen = session()->has('opened') ? session('opened') === $company->id : $loop->first)
                        <details name="company" class="group border-t border-line" @if ($isOpen) open @endif>
                            <summary class="grid cursor-pointer list-none grid-cols-[minmax(0,1fr)_auto_1.25rem] items-center gap-4 py-4 marker:hidden [&::-webkit-details-marker]:hidden">
                                <span class="flex min-w-0 flex-col">
                                    <span class="text-[17px] font-medium">{{ $company->name }}</span>
                                    <span class="text-sm text-muted">{{ $company->summary_line }}</span>
                                </span>
                                <span class="flex items-center gap-2 text-sm font-medium {{ $company->status->textClass() }}">
                                    <span class="size-2 rounded-full {{ $company->status->dotClass() }}"></span>
                                    {{ $company->status->label() }}
                                </span>
                                <span class="text-right text-lg text-muted" aria-hidden="true">
                                    <span class="group-open:hidden">+</span>
                                    <span class="hidden group-open:inline">−</span>
                                </span>
                            </summary>

                            <form method="POST" action="{{ route('companies.update', $company) }}" class="flex max-w-150 flex-col gap-4.5 pb-6">
                                @csrf
                                @method('PATCH')

                                @if ($company->focus)
                                    <p class="text-sm text-subtle">{{ $company->focus }}</p>
                                @endif

                                @if ($isOpen && $errors->any())
                                    <p class="text-sm text-rejected" role="alert">{{ $errors->first() }}</p>
                                @endif

                                <fieldset class="flex flex-wrap gap-2">
                                    <legend class="sr-only">Stav</legend>
                                    @foreach ($statuses as $option)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="status" value="{{ $option->value }}" class="peer sr-only" @checked($option === $company->status)>
                                            <span class="block rounded-full px-3.5 py-1.5 text-[13px] font-medium peer-focus-visible:ring-2 peer-focus-visible:ring-ink peer-focus-visible:ring-offset-2 {{ $option->softClass() }} {{ $option->checkedClass() }}">{{ $option->label() }}</span>
                                        </label>
                                    @endforeach
                                </fieldset>
                                <div class="flex flex-wrap items-center gap-x-8 gap-y-2 text-sm">
                                    <label class="flex items-center gap-2">
                                        <span class="text-muted">Odoslané</span>
                                        <input type="date" name="sent_at" value="{{ $company->sent_at?->toDateString() }}" max="{{ today()->toDateString() }}" class="h-8 rounded-lg bg-soft px-3 outline-none focus:ring-2 focus:ring-ink">
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <span class="text-muted">Pozícia</span>
                                        <input type="text" name="position" value="{{ $company->position }}" maxlength="255" class="h-8 w-56 rounded-lg bg-soft px-3 outline-none focus:ring-2 focus:ring-ink">
                                    </label>
                                    <span><span class="text-muted">Web </span><span class="text-accent">{{ $company->web ?: '—' }}</span></span>
                                    <label class="flex items-center gap-2">
                                        <span class="text-muted">Do budúcna</span>
                                        <input type="text" name="future" value="{{ $company->future }}" maxlength="255" placeholder="napr. Node.js" class="h-8 w-40 rounded-lg bg-soft px-3 outline-none placeholder:text-muted focus:ring-2 focus:ring-ink">
                                    </label>
                                </div>
                                <label class="flex flex-col gap-1.5 text-[13px] text-muted">
                                    Poznámka
                                    <textarea name="note" rows="2" maxlength="5000" placeholder="Čo si treba pamätať…" class="w-full resize-none rounded-xl bg-soft px-3.5 py-3 text-[15px] text-ink outline-none focus:ring-2 focus:ring-ink">{{ $company->note }}</textarea>
                                </label>
                                <div class="flex items-center gap-5">
                                    <button type="submit" class="h-9.5 rounded-full bg-ink px-5.5 text-sm font-medium text-paper hover:bg-ink/90">Uložiť</button>
                                </div>
                            </form>
                        </details>
                    @empty
                        <p class="border-t border-line py-8 text-muted">
                            {{ $activeStatus || $search !== '' ? 'Žiadna firma nezodpovedá filtru.' : 'Zatiaľ tu nie je žiadna firma. Pridaj prvú tlačidlom hore.' }}
                        </p>
                    @endforelse
                </div>
            </div>

            <aside class="flex min-w-0 flex-col gap-10 lg:pt-7">
                <section>
                    <h2 class="mb-1 font-display text-[22px] font-normal">Pripravené na odoslanie</h2>
                    <p class="mb-3.5 text-sm text-muted">Koncepty e-mailov, ktoré už len čakajú na tvoje „odoslať“.</p>
                    <ul class="flex flex-col">
                        @forelse ($drafts as $draft)
                            <li class="flex items-center justify-between border-t border-line py-2.5">
                                <span class="font-medium">{{ $draft->name }}</span>
                                <form method="POST" action="{{ route('companies.sent', $draft) }}">
                                    @csrf
                                    <button type="submit" class="text-sm text-accent hover:underline">Označiť ako poslané</button>
                                </form>
                            </li>
                        @empty
                            <li class="border-t border-line py-2.5 text-muted">Všetko je odoslané.</li>
                        @endforelse
                        @if ($moreDrafts > 0)
                            <li class="flex justify-between border-t border-line py-2.5 text-muted">
                                <span>a ďalšie {{ $moreDrafts }}</span>
                            </li>
                        @endif
                    </ul>
                </section>

                <section>
                    <h2 class="mb-1 font-display text-[22px] font-normal">Na neskôr</h2>
                    <p class="mb-3.5 text-sm text-muted">Firmy, ku ktorým sa vrátiš, keď sa naučíš niečo nové.</p>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($later as $topic)
                            <span class="rounded-full bg-later-soft px-3.5 py-1.5 text-[13px] font-medium text-later">{{ $topic }}</span>
                        @empty
                            <span class="text-sm text-muted">Zatiaľ nič.</span>
                        @endforelse
                    </div>
                </section>
            </aside>
        </main>
</x-layouts.app>
