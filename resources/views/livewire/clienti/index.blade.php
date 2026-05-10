<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-users class="w-6 h-6 text-indigo-600" />
            Clienti
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

                {{-- Bara actiuni --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                        <div class="relative w-full sm:w-80">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                            <input type="text" wire:model.live.debounce.300ms="cautare"
                                   placeholder="Cauta dupa denumire, CIF, cod, email, telefon..."
                                   class="pl-9 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm w-full" />
                        </div>

                        <select wire:model.live="status"
                                class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                            <option value="activi">Doar activi</option>
                            <option value="reziliati">Doar reziliati</option>
                            <option value="toti">Toti</option>
                        </select>

                        <select wire:model.live="tip"
                                class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                            <option value="toti">Orice tip</option>
                            <option value="pj">Persoane juridice</option>
                            <option value="pf">Persoane fizice</option>
                        </select>
                    </div>

                    <a href="{{ route('clienti.nou') }}" wire:navigate
                       class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        Adauga client
                    </a>
                </div>

                {{-- Tabel --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Cod</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Denumire</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Tip</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">CIF / CNP</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Contact</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Adrese</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Stare</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse($clienti as $c)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-mono text-xs">{{ $c->cod_client }}</td>
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                        <a href="{{ route('clienti.detalii', $c) }}" wire:navigate
                                           class="hover:text-indigo-600">
                                            {{ $c->denumire }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-2">
                                        @if($c->isPJ())
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">
                                                <x-heroicon-m-building-office-2 class="w-3 h-3" />
                                                PJ
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700">
                                                <x-heroicon-m-user class="w-3 h-3" />
                                                PF
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-mono text-xs">{{ $c->cif ?: '—' }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300 text-xs">
                                        @if($c->email)<div class="truncate max-w-xs">{{ $c->email }}</div>@endif
                                        @if($c->telefon)<div class="text-gray-500">{{ $c->telefon }}</div>@endif
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">{{ $c->adrese_count }}</td>
                                    <td class="px-4 py-2 text-center">
                                        @if($c->reziliat)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                <x-heroicon-s-x-circle class="w-3.5 h-3.5" />
                                                Reziliat
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                                Activ
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="{{ route('clienti.detalii', $c) }}" wire:navigate
                                           class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm">
                                            <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4" />
                                            Detalii
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <x-heroicon-o-users class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                        Niciun client.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $clienti->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
