<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-rose-600" />
            Probleme / Interventii
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

                {{-- Bara actiuni --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
                    <div class="relative w-full lg:w-96">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                        <input type="text" wire:model.live.debounce.300ms="cautare"
                               placeholder="Cauta dupa client, descriere, contact, telefon, ID..."
                               class="pl-9 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm w-full" />
                    </div>

                    <a href="{{ route('probleme.noua') }}" wire:navigate
                       class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        Problema noua
                    </a>
                </div>

                {{-- Filtre detaliate --}}
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-4 p-3 bg-gray-50 dark:bg-gray-900 rounded-md">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Data de la</label>
                        <input type="date" wire:model.live="dataDeLa"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Data pana la</label>
                        <input type="date" wire:model.live="dataPanaLa"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Masina</label>
                        <select wire:model.live="filtruMasina"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs">
                            <option value="">Toate</option>
                            @foreach($masini as $m)
                                <option value="{{ $m->id }}">{{ $m->denumire }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Depozit</label>
                        <select wire:model.live="filtruDepozit"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs">
                            <option value="">Toate</option>
                            @foreach($depozite as $d)
                                <option value="{{ $d->id }}">{{ $d->denumire }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-400">Status</label>
                        <select wire:model.live="filtruStatus"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 text-xs">
                            <option value="toate">Toate</option>
                            <option value="nelivrate">Nerezolvate</option>
                            <option value="livrate">Rezolvate</option>
                            <option value="neachitate">Neachitate</option>
                            <option value="achitate">Achitate</option>
                        </select>
                    </div>

                    <div class="col-span-full flex justify-end">
                        <button type="button" wire:click="reseteazaFiltre"
                                class="inline-flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 hover:text-rose-600">
                            <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                            Reseteaza filtre
                        </button>
                    </div>
                </div>

                {{-- Tabel --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">#</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Data</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Client / Adresa</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Descriere</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Suma (lei)</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Masina</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Stare</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse($probleme as $p)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-mono text-xs">#{{ $p->id }}</td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                        {{ $p->data_livrare?->format('d.m.Y') }}
                                        @if($p->interval_livrare)
                                            <span class="text-xs text-gray-500 block">{{ $p->interval_livrare }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-900 dark:text-gray-100">
                                        <a href="{{ route('clienti.detalii', $p->id_client) }}" wire:navigate
                                           class="font-medium hover:text-rose-600">
                                            {{ $p->client?->denumire ?? '—' }}
                                        </a>
                                        @if($p->adresa)
                                            <span class="text-xs text-gray-500 block">{{ $p->adresa->denumire }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300 max-w-xs">
                                        <div class="line-clamp-2" title="{{ $p->descriere }}">{{ $p->descriere }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-100 font-medium tabular-nums whitespace-nowrap">
                                        {{ number_format($p->total(), 2, ',', '.') }}
                                        <span class="text-[10px] font-normal text-gray-500 block">{{ $p->etichetaModPlata() }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                        @if($p->masina)
                                            <span class="inline-flex items-center gap-1 text-xs">
                                                <span class="w-2 h-2 rounded-full" style="background:{{ $p->masina->culoare }}"></span>
                                                {{ $p->masina->denumire }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">neasignata</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <div class="flex flex-col items-center gap-1">
                                            <button type="button" wire:click="comutaLivrat({{ $p->id }})"
                                                    title="Click pentru a comuta starea de rezolvare"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs {{ $p->livrat ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                                @if($p->livrat)
                                                    <x-heroicon-s-check-circle class="w-3 h-3" /> Rezolvata
                                                @else
                                                    <x-heroicon-m-wrench-screwdriver class="w-3 h-3" /> Nerezolvata
                                                @endif
                                            </button>
                                            <button type="button" wire:click="comutaAchitat({{ $p->id }})"
                                                    title="Click pentru a comuta starea de plata"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs {{ $p->achitat ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                                @if($p->achitat)
                                                    <x-heroicon-m-banknotes class="w-3 h-3" /> Achitata
                                                @else
                                                    <x-heroicon-m-banknotes class="w-3 h-3" /> Neachitata
                                                @endif
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-right whitespace-nowrap">
                                        <a href="{{ route('probleme.editare', $p) }}" wire:navigate
                                           class="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-800">
                                            <x-heroicon-m-pencil-square class="w-3.5 h-3.5" />
                                            Editeaza
                                        </a>
                                        @unless($p->livrat)
                                            <button type="button" wire:click="deschideModalStergere({{ $p->id }})"
                                                    class="inline-flex items-center gap-1 text-xs text-red-600 hover:text-red-800 ml-3">
                                                <x-heroicon-m-trash class="w-3.5 h-3.5" />
                                                Sterge
                                            </button>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-3 py-12 text-center">
                                        <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                        <p class="text-sm text-gray-500">Nu exista probleme care sa corespunda filtrelor.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $probleme->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal stergere --}}
    <div x-data="{ deschis: @entangle('modalStergere') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModalStergere()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
        <div x-show="deschis" x-on:click="$wire.inchideModalStergere()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>
        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-md sm:mx-auto p-6">
            <div class="flex items-start gap-3">
                <div class="bg-red-100 dark:bg-red-900/30 rounded-md p-2">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600" />
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Confirma stergerea</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Problema <span class="font-medium">{{ $denumireDeSters }}</span> va fi stearsa permanent.
                    </p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" wire:click="inchideModalStergere"
                        class="inline-flex items-center gap-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                    <x-heroicon-m-x-mark class="w-4 h-4" />
                    Anuleaza
                </button>
                <button type="button" wire:click="confirmaStergere"
                        class="inline-flex items-center gap-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md">
                    <x-heroicon-m-trash class="w-4 h-4" />
                    Sterge
                </button>
            </div>
        </div>
    </div>
</div>
