<div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Comenzile mele</h1>
                <p class="text-sm text-gray-500 mt-1">Istoric comenzi plasate si statusul livrarii.</p>
            </div>
            <a href="{{ route('portal.comenzi.noua') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-md hover:bg-sky-700 transition">
                <x-heroicon-o-plus-circle class="w-4 h-4" />
                Comanda noua
            </a>
        </div>

        @if(session('mesaj'))
            <div class="mb-4 px-4 py-3 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                {{ session('mesaj') }}
            </div>
        @endif

        {{-- Filtre --}}
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                    <select wire:model.live="filtruStatus" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="toate">Toate</option>
                        <option value="in_asteptare">In asteptare</option>
                        <option value="aprobate">Aprobate (nelivrate)</option>
                        <option value="livrate">Livrate</option>
                        <option value="respinse">Respinse</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">De la</label>
                    <input type="date" wire:model.live="dataDeLa" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Pana la</label>
                    <input type="date" wire:model.live="dataPanaLa" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
                </div>
                <div class="flex items-end">
                    <button wire:click="reseteazaFiltre" type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 hover:text-gray-800">
                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                        Reseteaza
                    </button>
                </div>
            </div>
        </div>

        {{-- Lista --}}
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            @if($comenzi->isEmpty())
                <div class="p-10 text-center text-gray-500">
                    <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                    <p class="text-sm">Nu ai comenzi care sa corespunda filtrelor.</p>
                </div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-medium text-gray-600 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-2 w-8"></th>
                            <th class="px-4 py-2">Data livrare</th>
                            <th class="px-4 py-2">Adresa</th>
                            <th class="px-4 py-2">Tip</th>
                            <th class="px-4 py-2 text-right">Total</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($comenzi as $c)
                            <tr wire:key="cmd-{{ $c->id }}" wire:click="expandeaza({{ $c->id }})"
                                class="hover:bg-sky-50 cursor-pointer">
                                <td class="px-4 py-3 text-gray-400">
                                    @if($expandatId === $c->id)
                                        <x-heroicon-m-chevron-down class="w-4 h-4" />
                                    @else
                                        <x-heroicon-m-chevron-right class="w-4 h-4" />
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $c->data_livrare?->format('d.m.Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $c->adresa?->adresa ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $c->etichetaTip() }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">
                                    {{ number_format($c->total(), 2, ',', '.') }} lei
                                </td>
                                <td class="px-4 py-3">
                                    @if($c->isInAsteptare())
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                            <x-heroicon-m-clock class="w-3.5 h-3.5" />
                                            In asteptare
                                        </span>
                                    @elseif($c->isRespinsa())
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <x-heroicon-m-x-circle class="w-3.5 h-3.5" />
                                            Respinsa
                                        </span>
                                    @elseif($c->livrat)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                            <x-heroicon-m-check-circle class="w-3.5 h-3.5" />
                                            Livrata
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-800">
                                            <x-heroicon-m-truck class="w-3.5 h-3.5" />
                                            Aprobata
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @if($expandatId === $c->id)
                                <tr wire:key="cmd-detail-{{ $c->id }}" class="bg-gray-50">
                                    <td colspan="6" class="px-4 py-4">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <div class="text-xs uppercase tracking-wider text-gray-500 mb-2">Produse</div>
                                                @if($c->produse->isEmpty())
                                                    <p class="text-sm text-gray-400">Fara linii.</p>
                                                @else
                                                    <ul class="space-y-1 text-sm">
                                                        @foreach($c->produse as $linie)
                                                            <li class="flex justify-between border-b border-gray-200 pb-1">
                                                                <span>
                                                                    {{ $linie->produs?->denumire ?? 'Produs sters' }}
                                                                    <span class="text-gray-400">× {{ (int) $linie->cantitate }}</span>
                                                                </span>
                                                                <span class="text-gray-700">
                                                                    {{ number_format((float) $linie->cantitate * (float) $linie->pret, 2, ',', '.') }} lei
                                                                </span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wider text-gray-500 mb-2">Detalii livrare</div>
                                                <dl class="text-sm space-y-1">
                                                    <div class="flex justify-between"><dt class="text-gray-500">Interval:</dt><dd>{{ $c->interval_livrare ?: '—' }}</dd></div>
                                                    <div class="flex justify-between"><dt class="text-gray-500">Modalitate plata:</dt><dd>{{ $c->etichetaModPlata() }}</dd></div>
                                                    <div class="flex justify-between"><dt class="text-gray-500">Achitat:</dt><dd>{{ $c->achitat ? 'Da' : 'Nu' }}</dd></div>
                                                    @if($c->observatii)
                                                        <div class="pt-2"><dt class="text-gray-500 text-xs">Observatii:</dt><dd class="text-gray-700">{{ $c->observatii }}</dd></div>
                                                    @endif
                                                </dl>
                                            </div>
                                            <div>
                                                <div class="text-xs uppercase tracking-wider text-gray-500 mb-2">Status comanda</div>
                                                @if($c->isRespinsa() && $c->motiv_respingere)
                                                    <div class="text-sm">
                                                        <p class="text-red-700 font-medium mb-1">Motiv respingere:</p>
                                                        <p class="text-gray-700">{{ $c->motiv_respingere }}</p>
                                                    </div>
                                                @elseif($c->isInAsteptare())
                                                    <p class="text-sm text-gray-600">
                                                        Comanda a fost trimisa spre aprobare. Vei primi un email
                                                        cand este aprobata sau respinsa.
                                                    </p>
                                                @elseif($c->livrat)
                                                    <p class="text-sm text-gray-600">Comanda a fost livrata cu succes.</p>
                                                @else
                                                    <p class="text-sm text-gray-600">Comanda este programata pentru livrare.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="mt-4">
            {{ $comenzi->links() }}
        </div>
    </div>
</div>
