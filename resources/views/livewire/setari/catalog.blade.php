<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-squares-2x2 class="w-6 h-6 text-indigo-600" />
            Catalog produse
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                @if (session('mesaj'))
                    <div class="mb-4 px-4 py-2 rounded bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                        <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 flex-shrink-0" />
                        {{ session('mesaj') }}
                    </div>
                @endif

                {{-- Tab-uri --}}
                <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
                    <nav class="-mb-px flex gap-6">
                        <button wire:click="comutaTab('produse')"
                                class="inline-flex items-center gap-1.5 py-3 border-b-2 text-sm font-medium {{ $tab === 'produse' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            <x-heroicon-o-cube class="w-4 h-4" />
                            Produse
                        </button>
                        <button wire:click="comutaTab('categorii')"
                                class="inline-flex items-center gap-1.5 py-3 border-b-2 text-sm font-medium {{ $tab === 'categorii' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            <x-heroicon-o-folder class="w-4 h-4" />
                            Categorii
                        </button>
                    </nav>
                </div>

                {{-- Bara actiuni --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                        <div class="relative w-full sm:w-72">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                            <input type="text" wire:model.live.debounce.300ms="cautare"
                                   placeholder="Cauta dupa denumire..."
                                   class="pl-9 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm w-full" />
                        </div>

                        @if($tab === 'produse')
                            <select wire:model.live="filtruCategorie"
                                    class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                <option value="">— toate categoriile —</option>
                                @foreach($categorii as $c)
                                    <option value="{{ $c->id }}">{{ $c->denumire }}</option>
                                @endforeach
                            </select>
                        @endif

                        <label class="inline-flex items-center text-sm text-gray-600 dark:text-gray-300">
                            <input type="checkbox" wire:model.live="arataInactive"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="ml-2">Arata si inactive</span>
                        </label>
                    </div>

                    @if($tab === 'produse')
                        <button wire:click="produsNou"
                                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                            <x-heroicon-m-plus class="w-4 h-4" />
                            Adauga produs
                        </button>
                    @else
                        <button wire:click="categorieNoua"
                                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                            <x-heroicon-m-plus class="w-4 h-4" />
                            Adauga categorie
                        </button>
                    @endif
                </div>

                {{-- Tabel produse --}}
                @if($tab === 'produse')
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">ID</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Denumire</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Categorie</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Pret</th>
                                    <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">TVA</th>
                                    <th class="px-4 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Stare</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Actiuni</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @forelse($itemi as $p)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400 font-mono">{{ $p->id }}</td>
                                        <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">{{ $p->denumire }}</td>
                                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $p->categorie?->denumire ?? '—' }}</td>
                                        <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($p->pret, 2) }} lei</td>
                                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $p->tva?->denumire ?? '—' }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <button wire:click="comutaActivProdus({{ $p->id }})"
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium {{ $p->activ ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                @if($p->activ)
                                                    <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                                    Activ
                                                @else
                                                    <x-heroicon-s-x-circle class="w-3.5 h-3.5" />
                                                    Inactiv
                                                @endif
                                            </button>
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <button wire:click="editeazaProdus({{ $p->id }})"
                                                    class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm">
                                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                                                Editeaza
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                            <x-heroicon-o-cube class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                            Niciun produs.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    {{-- Tabel categorii --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Denumire</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Produse</th>
                                    <th class="px-4 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Stare</th>
                                    <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Actiuni</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @forelse($itemi as $c)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">{{ $c->denumire }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">{{ $c->produse_count }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <button wire:click="comutaActivCategorie({{ $c->id }})"
                                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium {{ $c->activ ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                @if($c->activ)
                                                    <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                                    Activa
                                                @else
                                                    <x-heroicon-s-x-circle class="w-3.5 h-3.5" />
                                                    Inactiva
                                                @endif
                                            </button>
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <button wire:click="editeazaCategorie({{ $c->id }})"
                                                    class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm">
                                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                                                Editeaza
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                            <x-heroicon-o-folder class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                            Nicio categorie.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="mt-4">
                    {{ $itemi->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal create/edit (categorie sau produs) --}}
    <div x-data="{ deschis: @entangle('modalDeschis') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModal()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">

        <div x-show="deschis" x-on:click="$wire.inchideModal()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>

        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-lg sm:mx-auto">

            @if($tipModal === 'categorie')
                <form wire:submit.prevent="salveazaCategorie" class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        {{ $catId ? 'Editare categorie' : 'Adauga categorie' }}
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Denumire</label>
                            <input type="text" wire:model="catDenumire" maxlength="255"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('catDenumire') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" wire:model="catActiv"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="ml-2">Activa</span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end space-x-2">
                        <button type="button" wire:click="inchideModal"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                            <x-heroicon-m-x-mark class="w-4 h-4" />
                            Anuleaza
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                            <x-heroicon-m-check class="w-4 h-4" />
                            Salveaza
                        </button>
                    </div>
                </form>
            @elseif($tipModal === 'produs')
                <form wire:submit.prevent="salveazaProdus" class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        {{ $prodId ? 'Editare produs' : 'Adauga produs' }}
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Denumire</label>
                            <input type="text" wire:model="prodDenumire" maxlength="255"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                            @error('prodDenumire') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categorie</label>
                            <select wire:model="prodIdCategory"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                <option value="">— selecteaza —</option>
                                @foreach($categorii as $c)
                                    <option value="{{ $c->id }}">{{ $c->denumire }}</option>
                                @endforeach
                            </select>
                            @error('prodIdCategory') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pret (lei)</label>
                                <input type="number" step="0.01" min="0" wire:model="prodPret"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                                @error('prodPret') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cota TVA</label>
                                <select wire:model="prodIdTva"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                    <option value="">— niciuna —</option>
                                    @foreach($cote as $t)
                                        <option value="{{ $t->id }}">{{ $t->denumire }}</option>
                                    @endforeach
                                </select>
                                @error('prodIdTva') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" wire:model="prodActiv"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="ml-2">Activ</span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end space-x-2">
                        <button type="button" wire:click="inchideModal"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                            <x-heroicon-m-x-mark class="w-4 h-4" />
                            Anuleaza
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                            <x-heroicon-m-check class="w-4 h-4" />
                            Salveaza
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
