<div>
    @php
        // Mapping pentru culorile pinurilor pe harta
        $culoriMasini = $masini->keyBy('id')->map(fn ($m) => $m->culoare ?: '#3b82f6');
        $denumireMasini = $masini->keyBy('id')->map(fn ($m) => $m->denumire);

        // Sumar 19L/11L + puncte harta
        $sumar19l = 0;
        $sumar11l = 0;
        $puncteHarta = [];
        foreach ($itemi as $i) {
            $sumar19l += $i['nr19l'];
            $sumar11l += $i['nr11l'];
            if ($i['lat'] !== null && $i['lng'] !== null) {
                $estaNeasignata = empty($i['id_masina']);
                $puncteHarta[] = [
                    'tip' => $i['tip'],
                    'id' => $i['id'],
                    'id_masina' => $i['id_masina'], // null sau int — pentru selectarea optiunii din dropdown-ul popup
                    'lat' => (float) $i['lat'],
                    'lng' => (float) $i['lng'],
                    'culoare' => $estaNeasignata ? '#ef4444' : ($culoriMasini[$i['id_masina']] ?? '#3b82f6'),
                    'masina' => $estaNeasignata ? 'Nealocata' : ($denumireMasini[$i['id_masina']] ?? '?'),
                    'ordine' => $i['ordine_traseu'],
                    'titlu' => $i['titlu'],
                    'subtitlu' => $i['subtitlu'],
                    'nr19l' => $i['nr19l'],
                    'nr11l' => $i['nr11l'],
                    'livrat' => $i['livrat'],
                ];
            }
        }

        // Iconita per tip comanda
        $iconaTip = function (string $tipCod) {
            return match ($tipCod) {
                'abonament' => 'arrow-path',
                'consum suplimentar' => 'plus-circle',
                'rapida' => 'bolt',
                'problema' => 'wrench-screwdriver',
                default => 'cube',
            };
        };
        $culoareTip = function (string $tipCod) {
            return match ($tipCod) {
                'abonament' => 'text-indigo-600 bg-indigo-50',
                'consum suplimentar' => 'text-amber-600 bg-amber-50',
                'rapida' => 'text-amber-500 bg-amber-50',
                'problema' => 'text-rose-600 bg-rose-50',
                default => 'text-gray-600 bg-gray-100',
            };
        };

        // Pill culoare per modalitate plata
        $stilPlata = function (int $cod) {
            return match ($cod) {
                2 => 'bg-blue-100 text-blue-700',
                3 => 'bg-purple-100 text-purple-700',
                4 => 'bg-gray-100 text-gray-700',
                default => 'bg-emerald-100 text-emerald-700',
            };
        };

        $totalLivrate = collect($itemi)->where('livrat', true)->count();
    @endphp

    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-truck class="w-6 h-6 text-indigo-600" />
            Lista livrari
            <span class="text-sm font-normal text-gray-500">{{ \Carbon\Carbon::parse($data)->locale('ro')->isoFormat('dddd, D MMMM YYYY') }}</span>
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Flash messages --}}
            @if (session('mesaj'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-md px-4 py-2 flex items-center gap-2">
                    <x-heroicon-m-check-circle class="w-4 h-4" />
                    {{ session('mesaj') }}
                </div>
            @endif
            @if (session('eroare'))
                <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-2 flex items-center gap-2">
                    <x-heroicon-m-x-circle class="w-4 h-4" />
                    {{ session('eroare') }}
                </div>
            @endif

            {{-- Toolbar: data + filtre + butoane principale --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-3">
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Data --}}
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="navigheazaZi(-1)" title="Ziua precedenta"
                                class="inline-flex items-center justify-center p-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-md">
                            <x-heroicon-m-chevron-left class="w-4 h-4" />
                        </button>
                        <input type="date" wire:model.live="data"
                               class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm py-1.5" />
                        <button type="button" wire:click="navigheazaZi(1)" title="Ziua urmatoare"
                                class="inline-flex items-center justify-center p-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-md">
                            <x-heroicon-m-chevron-right class="w-4 h-4" />
                        </button>
                    </div>

                    {{-- Filtru masina --}}
                    <select wire:model.live="filtruMasina"
                            class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm py-1.5">
                        <option value="">Toate masinile</option>
                        <option value="0">Doar nealocate</option>
                        @foreach($masini as $m)
                            <option value="{{ $m->id }}">{{ $m->denumire }}</option>
                        @endforeach
                    </select>

                    {{-- Filtru depozit --}}
                    <select wire:model.live="idDepozit"
                            class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm py-1.5">
                        <option value="">Toate</option>
                        @foreach($depozite as $d)
                            <option value="{{ $d->id }}">{{ $d->denumire }}</option>
                        @endforeach
                    </select>

                    <div class="flex-1"></div>

                    {{-- Butoane principale --}}
                    <button type="button" wire:click="salveazaAlocari"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm">
                        <x-heroicon-m-check class="w-4 h-4" />
                        Salveaza alocarile
                    </button>
                    <a href="{{ route('comenzi.noua') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-md shadow-sm">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        Comanda noua
                    </a>
                </div>
            </div>

            {{-- Harta --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                @if(empty($apiKey))
                    <div class="bg-amber-50 border-b border-amber-200 p-3 text-xs text-amber-800 flex items-start gap-2">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 flex-shrink-0 mt-0.5" />
                        <div>
                            Cheia Google Maps lipseste. Adauga
                            <code class="px-1 bg-white border border-amber-300 rounded">GOOGLE_MAPS_API_KEY</code>
                            in fisierul <code class="px-1 bg-white border border-amber-300 rounded">.env</code>.
                        </div>
                    </div>
                @else
                    {{-- Data carrier morfat de Livewire — JS-ul citeste de aici la fiecare update --}}
                    @php
                        $masiniPentruHarta = $masini->map(fn ($m) => ['id' => $m->id, 'denumire' => $m->denumire])->values();
                    @endphp
                    <div id="harta-data"
                         data-puncte="{{ json_encode($puncteHarta, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                         data-masini="{{ json_encode($masiniPentruHarta, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                         hidden></div>

                    <div class="relative">
                        <div wire:ignore id="harta-traseu" class="w-full h-[28rem]"></div>
                        @if(empty($puncteHarta))
                            <div class="absolute inset-0 flex flex-col items-center justify-center bg-white/95 text-xs text-gray-400 italic pointer-events-none">
                                <x-heroicon-o-map class="w-12 h-12 mb-2 text-gray-300" />
                                Niciuna dintre comenzile filtrate nu are coordonate GPS.
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Lista livrari (tabel) --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-[11px] uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-2 py-2 text-left w-10">#</th>
                                <th class="px-2 py-2 text-center w-12">Tip</th>
                                <th class="px-3 py-2 text-left">Client / Destinatar</th>
                                <th class="px-3 py-2 text-left">Adresa</th>
                                <th class="px-3 py-2 text-left">Produse / Descriere</th>
                                <th class="px-3 py-2 text-right">Suma</th>
                                <th class="px-3 py-2 text-left w-44">Masina</th>
                                <th class="px-2 py-2 text-center w-16">Achitat</th>
                                <th class="px-2 py-2 text-center w-16">Livrat</th>
                                <th class="px-2 py-2 text-center w-20">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($itemi as $idx => $i)
                                <tr wire:key="rand-{{ $i['tip'] }}-{{ $i['id'] }}"
                                    class="{{ $i['livrat'] ? 'bg-emerald-50/30' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    {{-- # --}}
                                    <td class="px-2 py-2 text-center text-xs text-gray-500 tabular-nums align-top">
                                        {{ $idx + 1 }}
                                    </td>

                                    {{-- Tip (iconita) --}}
                                    <td class="px-2 py-2 align-top">
                                        <div class="flex items-center justify-center">
                                            <span title="{{ $i['tip_comanda_label'] }}"
                                                  class="inline-flex items-center justify-center w-7 h-7 rounded-md {{ $culoareTip($i['tip_cod']) }}">
                                                <x-dynamic-component :component="'heroicon-m-' . $iconaTip($i['tip_cod'])" class="w-4 h-4" />
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Client / Destinatar --}}
                                    <td class="px-3 py-2 align-top">
                                        @if($i['ruta_client'])
                                            <a href="{{ $i['ruta_client'] }}" wire:navigate
                                               class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                                {{ $i['titlu'] }}
                                            </a>
                                        @else
                                            <span class="font-medium text-amber-600">
                                                <x-heroicon-m-bolt class="w-3.5 h-3.5 inline" />
                                                {{ $i['titlu'] }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Adresa --}}
                                    <td class="px-3 py-2 align-top text-xs text-gray-600 dark:text-gray-400 max-w-xs">
                                        @if($i['adresa_completa'])
                                            <div class="truncate" title="{{ $i['adresa_completa'] }}">
                                                {{ $i['adresa_completa'] }}
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">—</span>
                                        @endif
                                        @if($i['lat'] === null || $i['lng'] === null)
                                            <span class="inline-flex items-center gap-0.5 mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200"
                                                  title="Adresa nu are coordonate GPS — pinul nu apare pe harta">
                                                <x-heroicon-m-map-pin class="w-3 h-3" />
                                                Fara GPS
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Produse / Descriere --}}
                                    <td class="px-3 py-2 align-top text-xs text-gray-700 dark:text-gray-300">
                                        {{ $i['descriere_produse'] }}
                                    </td>

                                    {{-- Suma + plata --}}
                                    <td class="px-3 py-2 align-top text-right whitespace-nowrap">
                                        <div class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">
                                            {{ number_format($i['total'], 2, ',', '.') }} <span class="text-[11px] font-normal text-gray-500">lei</span>
                                        </div>
                                        <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $stilPlata($i['mod_plata_cod']) }}">
                                            {{ $i['mod_plata_short'] }}
                                        </span>
                                    </td>

                                    {{-- Masina (select pentru alocare) --}}
                                    <td class="px-3 py-2 align-top">
                                        @php
                                            // Aleg colectia de overlay in functie de tip — toate au structura identica.
                                            $modelOverlay = match ($i['tip']) {
                                                'comanda_rapida' => 'alocariRapide',
                                                'problema' => 'alocariProbleme',
                                                default => 'alocariClasice',
                                            };
                                        @endphp
                                        <select wire:model.live="{{ $modelOverlay }}.{{ $i['id'] }}"
                                                class="w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-xs py-1">
                                            <option value="">— Nealocata —</option>
                                            @foreach($masini as $m)
                                                <option value="{{ $m->id }}">{{ $m->denumire }}</option>
                                            @endforeach
                                        </select>
                                    </td>

                                    {{-- Achitat --}}
                                    <td class="px-2 py-2 align-top text-center">
                                        <input type="checkbox"
                                               wire:click="comutaAchitat('{{ $i['tip'] }}', {{ $i['id'] }})"
                                               {{ $i['achitat'] ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                                    </td>

                                    {{-- Livrat --}}
                                    <td class="px-2 py-2 align-top text-center">
                                        <input type="checkbox"
                                               wire:click="comutaLivrat('{{ $i['tip'] }}', {{ $i['id'] }})"
                                               {{ $i['livrat'] ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                    </td>

                                    {{-- Actiuni --}}
                                    <td class="px-2 py-2 align-top">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ $i['ruta_editare'] }}" wire:navigate
                                               title="Editeaza comanda"
                                               class="inline-flex items-center justify-center w-7 h-7 rounded-md text-amber-600 hover:bg-amber-50">
                                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                                            </a>
                                            <button type="button"
                                                    wire:click="deschideModalStergere('{{ $i['tip'] }}', {{ $i['id'] }})"
                                                    title="Sterge comanda"
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-md text-red-600 hover:bg-red-50">
                                                <x-heroicon-m-trash class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-12 text-center">
                                        <x-heroicon-o-clipboard-document-list class="w-14 h-14 mx-auto mb-2 text-gray-300" />
                                        <p class="text-sm text-gray-500">Nicio comanda pentru aceasta zi cu filtrele curente.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Footer: header sumar + total + breakdown plata --}}
            @if($itemi->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    {{-- Header bar --}}
                    <div class="bg-gray-700 text-white px-4 py-2 flex items-center justify-between text-sm">
                        <div class="flex items-center gap-3">
                            <span class="font-medium">Sumar zilnic</span>
                            <span class="text-gray-300 text-xs">{{ $totalLivrate }}/{{ $totalItemi }} livrate</span>
                        </div>
                        <div class="flex items-center gap-4 text-xs">
                            <div>
                                <span class="text-gray-300">19L:</span>
                                <span class="font-semibold tabular-nums">{{ $sumar19l }}</span>
                            </div>
                            <div>
                                <span class="text-gray-300">11L:</span>
                                <span class="font-semibold tabular-nums">{{ $sumar11l }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Total general + breakdown plata --}}
                    <div class="px-4 py-3 grid grid-cols-1 md:grid-cols-2 gap-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-baseline gap-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total suma:</span>
                            <span class="text-xl font-bold tabular-nums text-gray-900 dark:text-gray-100">
                                {{ number_format($totalGlobal, 2, ',', '.') }} lei
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-x-4 gap-y-1 text-xs">
                            <span><span class="text-gray-500">Cash:</span> <span class="font-semibold tabular-nums text-emerald-700">{{ number_format($totalPePlata[1] ?? 0, 2, ',', '.') }}</span></span>
                            <span><span class="text-gray-500">OP:</span> <span class="font-semibold tabular-nums text-blue-700">{{ number_format($totalPePlata[2] ?? 0, 2, ',', '.') }}</span></span>
                            <span><span class="text-gray-500">Card:</span> <span class="font-semibold tabular-nums text-purple-700">{{ number_format($totalPePlata[3] ?? 0, 2, ',', '.') }}</span></span>
                            @if(($totalPePlata[4] ?? 0) > 0)
                                <span><span class="text-gray-500">Alta:</span> <span class="font-semibold tabular-nums text-gray-700">{{ number_format($totalPePlata[4] ?? 0, 2, ',', '.') }}</span></span>
                            @endif
                        </div>
                    </div>

                    {{-- Sume achitate per masina --}}
                    <div>
                        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900/50 text-xs font-medium text-gray-600 dark:text-gray-400">
                            Sume achitate per sofer / masina
                        </div>
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-xs">
                            <thead class="text-[11px] uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-1.5 text-left">Sofer / Masina</th>
                                    <th class="px-3 py-1.5 text-right">Cash</th>
                                    <th class="px-3 py-1.5 text-right">OP</th>
                                    <th class="px-3 py-1.5 text-right">Card</th>
                                    <th class="px-3 py-1.5 text-right">Alta</th>
                                    <th class="px-3 py-1.5 text-right">Total achitat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($sumarPerMasina as $cheie => $s)
                                    <tr>
                                        <td class="px-4 py-1.5 flex items-center gap-2">
                                            <span class="inline-block w-2.5 h-2.5 rounded-full" style="background:{{ $s['culoare'] }};"></span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $s['nume'] }}</span>
                                            @if($s['nr_inmatriculare'])
                                                <span class="text-gray-400 text-[10px]">{{ $s['nr_inmatriculare'] }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-1.5 text-right tabular-nums {{ $s['cash'] > 0 ? 'text-emerald-700 font-semibold' : 'text-gray-400' }}">
                                            {{ number_format($s['cash'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-1.5 text-right tabular-nums {{ $s['op'] > 0 ? 'text-blue-700 font-semibold' : 'text-gray-400' }}">
                                            {{ number_format($s['op'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-1.5 text-right tabular-nums {{ $s['card'] > 0 ? 'text-purple-700 font-semibold' : 'text-gray-400' }}">
                                            {{ number_format($s['card'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-1.5 text-right tabular-nums {{ $s['alta'] > 0 ? 'text-gray-700 font-semibold' : 'text-gray-400' }}">
                                            {{ number_format($s['alta'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-1.5 text-right tabular-nums font-bold text-gray-900 dark:text-gray-100">
                                            {{ number_format($s['total'], 2, ',', '.') }} lei
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Modal stergere --}}
    @if($modalStergere)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/50" wire:click="inchideModalStergere"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-600" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Confirmare stergere</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Esti sigur ca vrei sa stergi comanda <strong>{{ $denumireDeStergere }}</strong>?
                                Miscarile de stoc generate vor fi anulate. Aceasta actiune nu poate fi anulata.
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" wire:click="inchideModalStergere"
                                class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm rounded-md">
                            Anuleaza
                        </button>
                        <button type="button" wire:click="confirmaStergere"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm rounded-md">
                            <x-heroicon-m-trash class="w-4 h-4" />
                            Sterge
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @assets
        @if($apiKey)
            <script async defer
                    src="https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&callback=initListaZilnicaMap&loading=async"></script>
        @endif
    @endassets

    @script
        <script>
            (() => {
                // OPTIMIZARE: in loc sa distrugem si recreem toti markerii la fiecare
                // update Livewire, facem diff pe cheie "tip-id":
                //   - marker existent + schimbare vizuala → setIcon() / setLabel() (fara DOM nou)
                //   - marker nou → new Marker() o singura data
                //   - marker disparut (zi schimbata, filtru) → setMap(null)
                // Un singur InfoWindow reutilizat (nu N instante, cate una per pin).
                // fitBounds() se apeleaza o singura data la initializare sau cand setul
                // de coordonate GPS se schimba (zi noua, filtru masina/depozit schimbat).

                // Map<"tip-id", { marker, culoare, livrat, idMasina, ordine }> — starea curenta a pinilor
                if (!window.__hartaMarkeriMap) window.__hartaMarkeriMap = new Map();

                // Un singur InfoWindow partajat intre toti markerii
                if (!window.__hartaInfoWindow) window.__hartaInfoWindow = null;

                // Amprenta setului de coordonate GPS — pentru a detecta schimbari majore (zi/filtru)
                if (!window.__hartaAmprentaGps) window.__hartaAmprentaGps = '';

                // Stil "fade" — sincronizat cu sofer/traseu.blade.php
                const STIL_HARTA_FADE = [
                    { elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
                    { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#9ca3af' }] },
                    { elementType: 'labels.text.stroke', stylers: [{ color: '#ffffff' }] },
                    { featureType: 'administrative', elementType: 'geometry', stylers: [{ color: '#e5e7eb' }] },
                    { featureType: 'administrative.country', elementType: 'labels.text.fill', stylers: [{ color: '#6b7280' }] },
                    { featureType: 'administrative.locality', elementType: 'labels.text.fill', stylers: [{ color: '#4b5563' }] },
                    { featureType: 'poi', stylers: [{ visibility: 'off' }] },
                    { featureType: 'poi.park', elementType: 'geometry', stylers: [{ color: '#eef2f5' }, { visibility: 'on' }] },
                    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
                    { featureType: 'road.arterial', elementType: 'geometry', stylers: [{ color: '#fafafa' }] },
                    { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#f3f4f6' }] },
                    { featureType: 'road.local', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
                    { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#9ca3af' }] },
                    { featureType: 'transit', stylers: [{ visibility: 'off' }] },
                    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#dbeafe' }] },
                    { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#93c5fd' }] },
                ];

                const citestePuncte = () => {
                    const el = document.getElementById('harta-data');
                    if (!el) return [];
                    try { return JSON.parse(el.dataset.puncte || '[]'); } catch { return []; }
                };

                const citesteMasini = () => {
                    const el = document.getElementById('harta-data');
                    if (!el) return [];
                    try { return JSON.parse(el.dataset.masini || '[]'); } catch { return []; }
                };

                const escHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
                }[c]));

                // Construieste HTML-ul popup-ului — apelat la click (nu la creare marker).
                const buildInfoHtml = (p, masini) => {
                    const cantitati = [];
                    if (p.nr19l) cantitati.push(`${p.nr19l}× 19L`);
                    if (p.nr11l) cantitati.push(`${p.nr11l}× 11L`);
                    const tipBadge = p.tip === 'comanda_rapida' ? '⚡ ' : '';
                    const idCurent = (p.id_masina === null || p.id_masina === undefined) ? '' : String(p.id_masina);
                    const optiuni = ['<option value=""' + (idCurent === '' ? ' selected' : '') + '>— Nealocata —</option>']
                        .concat(masini.map(m => {
                            const sel = idCurent === String(m.id) ? ' selected' : '';
                            return `<option value="${m.id}"${sel}>${escHtml(m.denumire)}</option>`;
                        })).join('');
                    const metaParti = [escHtml(p.masina)];
                    if (p.ordine > 0) metaParti.push('#' + p.ordine);
                    if (cantitati.length) metaParti.push(cantitati.join(' · '));
                    return `<div style="font-size:12px;line-height:1.35;min-width:220px">
                        <div style="font-weight:600;color:#111">${tipBadge}${escHtml(p.titlu)}</div>
                        <div style="color:#666;margin-top:1px">${escHtml(p.subtitlu)}</div>
                        <div style="color:#888;margin-top:1px;font-size:11px">${metaParti.join(' · ')}</div>
                        <div style="margin-top:6px;display:flex;align-items:center;gap:6px">
                            <span style="color:#666;font-size:11px">Alocă:</span>
                            <select onchange="window.__alocaDinHarta('${p.tip}',${p.id},this.value)"
                                    style="font-size:12px;padding:2px 4px;border:1px solid #d1d5db;border-radius:4px;flex:1">
                                ${optiuni}
                            </select>
                        </div>
                    </div>`;
                };

                const buildIcon = (culoare, livrat) => ({
                    path: 'M12 2C7.58 2 4 5.58 4 10c0 5.5 8 12 8 12s8-6.5 8-12c0-4.42-3.58-8-8-8z',
                    fillColor: culoare,
                    fillOpacity: livrat ? 0.5 : 1,
                    strokeColor: '#fff',
                    strokeWeight: 2,
                    scale: 1.6,
                    anchor: new google.maps.Point(12, 22),
                    labelOrigin: new google.maps.Point(12, 10),
                });

                window.__alocaDinHarta = (tip, id, valoare) => {
                    if (typeof Livewire === 'undefined') return;
                    Livewire.dispatch('aloca-masina-harta', { tip, id, valoare });
                };

                const renderHarta = () => {
                    const div = document.getElementById('harta-traseu');
                    if (!div || !window.google || !window.google.maps) return;

                    // Detectam invalidare harta (wire:navigate inlocuieste DOM-ul complet)
                    if (window.__harta) {
                        const divCached = window.__harta.getDiv();
                        if (divCached !== div || !document.body.contains(divCached)) {
                            window.__hartaMarkeriMap.forEach(e => e.marker.setMap(null));
                            window.__hartaMarkeriMap.clear();
                            if (window.__hartaInfoWindow) { window.__hartaInfoWindow.close(); window.__hartaInfoWindow = null; }
                            window.__hartaAmprentaGps = '';
                            window.__harta = null;
                        }
                    }

                    const puncte = citestePuncte();
                    const masini = citesteMasini();

                    // Amprenta GPS: string sortat de "lat,lng" — se schimba cand zi/filtru se schimba
                    const amprentaNouaGps = puncte.map(p => `${p.lat},${p.lng}`).sort().join('|');
                    const schimbaCoordonate = amprentaNouaGps !== window.__hartaAmprentaGps;
                    window.__hartaAmprentaGps = amprentaNouaGps;

                    if (puncte.length === 0) {
                        // Ascundem toti markerii existenti fara sa-i distrugem
                        window.__hartaMarkeriMap.forEach(e => e.marker.setMap(null));
                        window.__hartaMarkeriMap.clear();
                        if (window.__hartaInfoWindow) { window.__hartaInfoWindow.close(); }
                        return;
                    }

                    if (!window.__harta) {
                        window.__harta = new google.maps.Map(div, {
                            zoom: 12,
                            center: { lat: puncte[0].lat, lng: puncte[0].lng },
                            mapTypeControl: false,
                            streetViewControl: false,
                            styles: STIL_HARTA_FADE,
                        });
                    } else {
                        google.maps.event.trigger(window.__harta, 'resize');
                    }

                    const harta = window.__harta;

                    // Un singur InfoWindow reutilizat
                    if (!window.__hartaInfoWindow) {
                        window.__hartaInfoWindow = new google.maps.InfoWindow();
                        window.__hartaInfoWindow.addListener('closeclick', () => {
                            window.__hartaInfoWindowPin = null;
                        });
                    }
                    const infoWindow = window.__hartaInfoWindow;

                    // ── DIFF: cheile din noul set de puncte ─────────────────────────────
                    const keysNoi = new Set(puncte.map(p => `${p.tip}-${p.id}`));

                    // Sterge markeri care au disparut (zi schimbata, filtru)
                    window.__hartaMarkeriMap.forEach((entry, key) => {
                        if (!keysNoi.has(key)) {
                            entry.marker.setMap(null);
                            window.__hartaMarkeriMap.delete(key);
                        }
                    });

                    const bounds = schimbaCoordonate ? new google.maps.LatLngBounds() : null;

                    puncte.forEach((p) => {
                        const key = `${p.tip}-${p.id}`;
                        const pos = { lat: p.lat, lng: p.lng };
                        if (bounds) bounds.extend(pos);

                        if (window.__hartaMarkeriMap.has(key)) {
                            // ── Marker existent: actualizeaza numai ce s-a schimbat ────
                            const entry = window.__hartaMarkeriMap.get(key);
                            const culoareSchimbata = entry.culoare !== p.culoare;
                            const livratSchimbat   = entry.livrat  !== p.livrat;
                            const ordineSchimbat   = entry.ordine  !== p.ordine;

                            if (culoareSchimbata || livratSchimbat) {
                                entry.marker.setIcon(buildIcon(p.culoare, p.livrat));
                                entry.culoare = p.culoare;
                                entry.livrat  = p.livrat;
                            }
                            if (ordineSchimbat) {
                                const ordineText = p.ordine > 0 ? String(p.ordine) : ' ';
                                entry.marker.setLabel({ text: ordineText, color: '#fff', fontSize: '11px', fontWeight: 'bold' });
                                entry.ordine = p.ordine;
                            }
                            // Actualizam id_masina in entry (pentru popup la click)
                            entry.idMasina = p.id_masina;
                            entry.p = p; // referinta proaspata pentru popup
                        } else {
                            // ── Marker nou: creat o singura data ──────────────────────
                            const ordineText = p.ordine > 0 ? String(p.ordine) : ' ';
                            const marker = new google.maps.Marker({
                                position: pos,
                                map: harta,
                                title: `${p.titlu} — ${p.subtitlu}`,
                                label: { text: ordineText, color: '#fff', fontSize: '11px', fontWeight: 'bold' },
                                icon: buildIcon(p.culoare, p.livrat),
                            });

                            marker.addListener('click', () => {
                                // Construim HTML-ul popup-ului la click (nu la creare)
                                // folosind datele proaspete din entry.p
                                const entry = window.__hartaMarkeriMap.get(key);
                                if (!entry) return;
                                infoWindow.setContent(buildInfoHtml(entry.p, masini));
                                infoWindow.open(harta, marker);
                                window.__hartaInfoWindowPin = key;
                            });

                            window.__hartaMarkeriMap.set(key, {
                                marker,
                                culoare: p.culoare,
                                livrat: p.livrat,
                                ordine: p.ordine,
                                idMasina: p.id_masina,
                                p, // referinta proaspata pentru popup
                            });
                        }
                    });

                    // fitBounds doar cand setul de coordonate s-a schimbat
                    if (bounds && puncte.length > 1) harta.fitBounds(bounds);
                };

                window.__renderListaZilnica = renderHarta;
                window.initListaZilnicaMap = renderHarta;

                if (window.google && window.google.maps) {
                    renderHarta();
                }

                if (!window.__listaZilnicaHookRegistrat && typeof Livewire !== 'undefined') {
                    window.__listaZilnicaHookRegistrat = true;
                    Livewire.hook('morph.updated', () => {
                        setTimeout(() => {
                            if (typeof window.__renderListaZilnica === 'function') {
                                window.__renderListaZilnica();
                            }
                        }, 0);
                    });
                }
            })();
        </script>
    @endscript
</div>
