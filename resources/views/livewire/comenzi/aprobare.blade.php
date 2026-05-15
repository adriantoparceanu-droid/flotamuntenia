<div>
    @php use App\Models\Comanda; @endphp

    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-check-badge class="w-6 h-6 text-emerald-600" />
            Aprobare comenzi portal
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                @if (session('mesaj'))
                    <div class="mb-4 px-4 py-2 rounded bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                        <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 flex-shrink-0" />
                        {{ session('mesaj') }}
                    </div>
                @endif
                @if (session('eroare'))
                    <div class="mb-4 px-4 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm flex items-center gap-2">
                        <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-red-500 flex-shrink-0" />
                        {{ session('eroare') }}
                    </div>
                @endif

                {{-- Tabs status --}}
                <div class="flex flex-wrap items-center gap-2 mb-4 border-b border-gray-200 dark:border-gray-700">
                    <button type="button" wire:click="$set('filtruStatus', 'in_asteptare')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm border-b-2 -mb-px {{ $filtruStatus === 'in_asteptare' ? 'border-amber-500 text-amber-700 font-medium' : 'border-transparent text-gray-600 hover:text-gray-800' }}">
                        <x-heroicon-m-clock class="w-4 h-4" />
                        In asteptare
                        @if($totalInAsteptare > 0)
                            <span class="ml-1 inline-flex items-center justify-center px-1.5 min-w-[1.25rem] h-5 text-[11px] rounded-full bg-amber-100 text-amber-700 font-semibold">{{ $totalInAsteptare }}</span>
                        @endif
                    </button>
                    <button type="button" wire:click="$set('filtruStatus', 'respinse')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm border-b-2 -mb-px {{ $filtruStatus === 'respinse' ? 'border-red-500 text-red-700 font-medium' : 'border-transparent text-gray-600 hover:text-gray-800' }}">
                        <x-heroicon-m-x-circle class="w-4 h-4" />
                        Respinse
                        @if($totalRespinse > 0)
                            <span class="ml-1 inline-flex items-center justify-center px-1.5 min-w-[1.25rem] h-5 text-[11px] rounded-full bg-red-100 text-red-700 font-semibold">{{ $totalRespinse }}</span>
                        @endif
                    </button>
                    <button type="button" wire:click="$set('filtruStatus', 'toate')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm border-b-2 -mb-px {{ $filtruStatus === 'toate' ? 'border-indigo-500 text-indigo-700 font-medium' : 'border-transparent text-gray-600 hover:text-gray-800' }}">
                        <x-heroicon-m-list-bullet class="w-4 h-4" />
                        Toate
                    </button>
                </div>

                {{-- Cautare --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
                    <div class="relative w-full lg:w-96">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                        <input type="text" wire:model.live.debounce.300ms="cautare"
                               placeholder="Cauta dupa client, cod, email, CIF, ID comanda..."
                               class="pl-9 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm w-full" />
                    </div>
                </div>

                {{-- Tabel --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">#</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Plasata</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Client / Adresa</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Produse</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Total (lei)</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Data dorita</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Stare</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse($comenzi as $c)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-mono text-xs whitespace-nowrap">#{{ $c->id }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 whitespace-nowrap text-xs">
                                        {{ $c->created_at?->format('d.m.Y H:i') }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-gray-100">
                                        <a href="{{ route('clienti.detalii', $c->id_client) }}" wire:navigate
                                           class="font-medium hover:text-indigo-600">
                                            {{ $c->client?->denumire ?? '—' }}
                                        </a>
                                        @if($c->client?->email)
                                            <span class="text-xs text-gray-500 block">{{ $c->client->email }}</span>
                                        @endif
                                        @if($c->adresa)
                                            <span class="text-xs text-gray-400 block">{{ $c->adresa->eticheta }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 text-xs">
                                        @forelse($c->produse as $linie)
                                            <div>
                                                {{ $linie->cantitate }}× {{ $linie->produs?->denumire ?? $linie->denumire ?? '—' }}
                                                <span class="text-gray-400">@ {{ number_format($linie->pret, 2, ',', '.') }} lei</span>
                                            </div>
                                        @empty
                                            <span class="text-gray-400 italic">fara linii</span>
                                        @endforelse
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-100 font-medium tabular-nums whitespace-nowrap">
                                        {{ number_format($c->total(), 2, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 whitespace-nowrap text-xs">
                                        {{ $c->data_livrare?->format('d.m.Y') ?? '—' }}
                                        @if($c->interval_livrare)
                                            <span class="block text-gray-400">{{ $c->interval_livrare }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if($c->isInAsteptare())
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-700">
                                                <x-heroicon-m-clock class="w-3 h-3" />
                                                In asteptare
                                            </span>
                                        @elseif($c->isRespinsa())
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-red-100 text-red-700" @if($c->motiv_respingere) title="{{ $c->motiv_respingere }}" @endif>
                                                <x-heroicon-m-x-circle class="w-3 h-3" />
                                                Respinsa
                                            </span>
                                            @if($c->aprobatDe)
                                                <span class="block text-[11px] text-gray-400 mt-0.5">de {{ $c->aprobatDe->name }}</span>
                                            @endif
                                            @if($c->data_respingere)
                                                <span class="block text-[11px] text-gray-400">{{ $c->data_respingere->format('d.m.Y H:i') }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right whitespace-nowrap">
                                        @if($c->isInAsteptare())
                                            <div class="inline-flex items-center gap-3">
                                                <button type="button" wire:click="aproba({{ $c->id }})"
                                                        wire:confirm="Aprobi comanda #{{ $c->id }}? Se vor genera miscarile de stoc OUT si se va trimite email confirmare clientului."
                                                        title="Aproba comanda (genereaza miscari OUT + email confirmare)"
                                                        aria-label="Aproba comanda"
                                                        class="text-emerald-600 hover:text-emerald-800">
                                                    <x-heroicon-o-check-badge class="w-5 h-5" />
                                                </button>
                                                <a href="{{ route('comenzi.editare', ['comanda' => $c, 'aprobare' => 1]) }}" wire:navigate
                                                   title="Editeaza comanda inainte de aprobare (date, masina, depozit)"
                                                   aria-label="Editeaza si aproba comanda"
                                                   class="text-indigo-600 hover:text-indigo-800">
                                                    <x-heroicon-o-pencil-square class="w-5 h-5" />
                                                </a>
                                                <button type="button" wire:click="deschideModalRespingere({{ $c->id }})"
                                                        title="Respinge comanda (cu motiv optional + email notificare)"
                                                        aria-label="Respinge comanda"
                                                        class="text-red-600 hover:text-red-800">
                                                    <x-heroicon-o-x-circle class="w-5 h-5" />
                                                </button>
                                            </div>
                                        @else
                                            <a href="{{ route('comenzi.editare', $c) }}" wire:navigate
                                               title="Vezi comanda"
                                               aria-label="Vezi comanda"
                                               class="text-gray-500 hover:text-indigo-600">
                                                <x-heroicon-o-eye class="w-5 h-5" />
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                @if($c->isRespinsa() && $c->motiv_respingere)
                                    <tr>
                                        <td colspan="8" class="px-3 pb-3 -mt-1">
                                            <div class="text-xs text-red-700 bg-red-50 border border-red-100 rounded px-3 py-1.5 flex items-start gap-2">
                                                <x-heroicon-m-chat-bubble-bottom-center-text class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" />
                                                <span><span class="font-medium">Motiv:</span> {{ $c->motiv_respingere }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="8" class="px-3 py-12 text-center">
                                        <x-heroicon-o-check-badge class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                        <p class="text-sm text-gray-500">
                                            @if($filtruStatus === 'in_asteptare')
                                                Nicio comanda in asteptare. Tot ce vine de la portal a fost procesat.
                                            @elseif($filtruStatus === 'respinse')
                                                Nicio comanda respinsa.
                                            @else
                                                Nicio comanda care sa corespunda filtrelor.
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $comenzi->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal respingere --}}
    <div x-data="{ deschis: @entangle('modalRespingere') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModalRespingere()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
        <div x-show="deschis" x-on:click="$wire.inchideModalRespingere()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>
        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-md sm:mx-auto p-6">
            <div class="flex items-start gap-3">
                <div class="bg-red-100 dark:bg-red-900/30 rounded-md p-2">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600" />
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Respinge comanda</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Comanda <span class="font-medium">{{ $denumireDeRespins }}</span> va fi marcata ca respinsa. Clientul va primi un email de notificare.
                    </p>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Motiv respingere (optional, dar util pentru client)</label>
                <textarea wire:model="motivRespingere" rows="3"
                          placeholder="Ex: Adresa nu e in zona de livrare, data dorita este sarbatoare etc."
                          class="block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" wire:click="inchideModalRespingere"
                        class="inline-flex items-center gap-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                    <x-heroicon-m-x-mark class="w-4 h-4" />
                    Anuleaza
                </button>
                <button type="button" wire:click="confirmaRespingere"
                        class="inline-flex items-center gap-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md">
                    <x-heroicon-m-x-circle class="w-4 h-4" />
                    Respinge
                </button>
            </div>
        </div>
    </div>
</div>
