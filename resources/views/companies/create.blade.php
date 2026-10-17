<x-layouts.app title="Pridať firmu · Odozva">
    <main class="px-4 pt-3 pb-14 sm:px-8 lg:px-18">
        <a href="{{ route('overview') }}" class="text-sm text-muted hover:text-ink">← Späť na prehľad</a>

        <h1 class="mt-4 mb-2 font-display text-4xl leading-[1.15] sm:text-[44px] font-normal tracking-tight">Nová firma</h1>
        <p class="mb-9 max-w-140 text-[17px] text-subtle">Stačí názov. Ostatné môžeš doplniť neskôr.</p>

        <form method="POST" action="{{ route('companies.store') }}" class="group/form flex max-w-150 flex-col gap-6">
            @csrf

            @if ($errors->any())
                <ul class="rounded-xl bg-rejected-soft px-4 py-3 text-sm text-rejected" role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <label class="flex flex-col gap-1.5 text-[13px] text-muted">
                Názov firmy
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" autofocus class="h-11 rounded-xl bg-soft px-4 text-[15px] text-ink outline-none focus:ring-2 focus:ring-ink">
            </label>

            <fieldset class="flex flex-col gap-2">
                <legend class="mb-1.5 text-[13px] text-muted">Stav</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach ($statuses as $option)
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="{{ $option->value }}" class="peer sr-only" @checked(old('status', 'neposlane') === $option->value)>
                            <span class="block rounded-full px-3.5 py-1.5 text-[13px] font-medium peer-focus-visible:ring-2 peer-focus-visible:ring-ink peer-focus-visible:ring-offset-2 {{ $option->softClass() }} {{ $option->checkedClass() }}">{{ $option->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="grid gap-6 sm:grid-cols-2">
                <label class="flex flex-col gap-1.5 text-[13px] text-muted">
                    Dátum odoslania
                    <input type="date" name="sent_at" value="{{ old('sent_at') }}" max="{{ today()->toDateString() }}" class="h-11 rounded-xl bg-soft px-4 text-[15px] text-ink outline-none focus:ring-2 focus:ring-ink">
                </label>
                <label class="flex flex-col gap-1.5 text-[13px] text-muted">
                    Pozícia
                    <input type="text" name="position" value="{{ old('position') }}" maxlength="255" class="h-11 rounded-xl bg-soft px-4 text-[15px] text-ink outline-none focus:ring-2 focus:ring-ink">
                </label>
                <label class="flex flex-col gap-1.5 text-[13px] text-muted">
                    Kanál
                    <input type="text" name="channel" value="{{ old('channel') }}" maxlength="255" placeholder="Email, Profesia, Web…" class="h-11 rounded-xl bg-soft px-4 text-[15px] text-ink outline-none placeholder:text-muted focus:ring-2 focus:ring-ink">
                </label>
                <label class="flex flex-col gap-1.5 text-[13px] text-muted">
                    Web
                    <input type="text" name="web" value="{{ old('web') }}" maxlength="255" placeholder="firma.sk" class="h-11 rounded-xl bg-soft px-4 text-[15px] text-ink outline-none placeholder:text-muted focus:ring-2 focus:ring-ink">
                </label>
                <label class="flex flex-col gap-1.5 text-[13px] text-muted">
                    Do budúcna (čo sa naučiť)
                    <input type="text" name="future" value="{{ old('future') }}" maxlength="255" placeholder="napr. Node.js" class="h-11 rounded-xl bg-soft px-4 text-[15px] text-ink outline-none placeholder:text-muted focus:ring-2 focus:ring-ink">
                </label>
            </div>

            <label class="hidden flex-col gap-1.5 text-[13px] text-muted group-has-[input[value=zamietnute]:checked]/form:flex group-has-[input[value=nevhodne]:checked]/form:flex">
                Dôvod (prečo zamietnuté alebo nevhodné)
                <input type="text" name="reason" value="{{ old('reason') }}" maxlength="1000" placeholder="napr. chcú aj frontend, nerobia programovanie…" class="h-11 rounded-xl bg-soft px-4 text-[15px] text-ink outline-none placeholder:text-muted focus:ring-2 focus:ring-ink">
            </label>

            <label class="flex flex-col gap-1.5 text-[13px] text-muted">
                Poznámka
                <textarea name="note" rows="3" maxlength="5000" placeholder="Čo si treba pamätať…" class="w-full resize-none rounded-xl bg-soft px-4 py-3 text-[15px] text-ink outline-none placeholder:text-muted focus:ring-2 focus:ring-ink">{{ old('note') }}</textarea>
            </label>

            <div class="flex items-center gap-5">
                <button type="submit" class="h-10 rounded-full bg-ink px-6 text-sm font-medium text-paper hover:bg-ink/90">Pridať firmu</button>
                <a href="{{ route('overview') }}" class="text-sm text-muted underline hover:text-ink">Zrušiť</a>
            </div>
        </form>
    </main>
</x-layouts.app>
