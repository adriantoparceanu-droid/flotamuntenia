<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-building-storefront class="w-6 h-6 text-indigo-600" />
            Depozite
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                @if (session('mesaj'))
                    <div class="mb-4 px-4 py-2 rounded bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                        <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 flex-shrink-0" />
                        {{ session('mesaj') }}
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                        <div class="relative w-full sm:w-72">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                            <input type="text" wire:model.live.debounce.300ms="cautare"
                                   placeholder="Cauta dupa denumire sau adresa..."
                                   class="pl-9 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm w-full" />
                        </div>
                        <label class="inline-flex items-center text-sm text-gray-600 dark:text-gray-300">
                            <input type="checkbox" wire:model.live="arataInactive"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="ml-2">Arata si inactive</span>
                        </label>
                    </div>
                    <button wire:click="nou"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        Adauga depozit
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Denumire</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Adresa</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Stare</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse($depozite as $d)
                                <tr>
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">{{ $d->denumire }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $d->adresa ?: '—' }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <button wire:click="comutaActiv({{ $d->id }})"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium {{ $d->activ ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                            @if($d->activ)
                                                <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                                Activ
                                            @else
                                                <x-heroicon-s-x-circle class="w-3.5 h-3.5" />
                                                Inactiv
                                            @endif
                                        </button>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <button wire:click="editeaza({{ $d->id }})"
                                                class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm">
                                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                                            Editeaza
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                        Niciun depozit.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $depozite->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal create/edit --}}
    <div x-data="{ deschis: @entangle('modalDeschis') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModal()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">

        <div x-show="deschis" x-on:click="$wire.inchideModal()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>

        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-lg sm:mx-auto">
            <form wire:submit.prevent="salveaza" class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    {{ $editandId ? 'Editare depozit' : 'Adauga depozit' }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Denumire</label>
                        <input type="text" wire:model="denumire" maxlength="255"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        @error('denumire') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adresa</label>
                        <textarea wire:model="adresa" rows="2" maxlength="500"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
                        @error('adresa') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" wire:model="activ"
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
        </div>
    </div>
</div>
