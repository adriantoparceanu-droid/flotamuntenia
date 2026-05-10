<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-truck class="w-6 h-6 text-indigo-600" />
            Masini
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

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                        <div class="relative w-full sm:w-80">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                            <input type="text" wire:model.live.debounce.300ms="cautare"
                                   placeholder="Cauta dupa denumire sau nr. inmatriculare..."
                                   class="pl-9 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm w-full" />
                        </div>
                        <label class="inline-flex items-center text-sm text-gray-600 dark:text-gray-300">
                            <input type="checkbox" wire:model.live="arataInactive"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="ml-2">Arata si inactive</span>
                        </label>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button wire:click="adaugaSofer"
                                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-md">
                            <x-heroicon-m-user-plus class="w-4 h-4" />
                            Adauga sofer
                        </button>
                        <button wire:click="nou"
                                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                            <x-heroicon-m-plus class="w-4 h-4" />
                            Adauga masina
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Denumire</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Nr. inmatriculare</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Depozit</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Sofer</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Culoare</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Stare</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse($masini as $m)
                                @php $sofer = $soferiPerMasina->get($m->id); @endphp
                                <tr>
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">{{ $m->denumire }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-mono">{{ $m->nr_inmatriculare }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $m->depozit?->denumire ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                        @if($sofer)
                                            <div class="flex items-center gap-1">
                                                <x-heroicon-m-user-circle class="w-4 h-4 text-emerald-600" />
                                                <span>{{ $sofer->name }}</span>
                                                @if(! $sofer->confirmat)
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-700" title="Cont dezactivat">inactiv</span>
                                                @endif
                                            </div>
                                            @if($sofer->username)
                                                <div class="text-[11px] text-gray-500 font-mono">@<span>{{ $sofer->username }}</span></div>
                                            @endif
                                        @else
                                            <span class="text-xs text-gray-400 italic">— nealocat —</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <span class="inline-block w-6 h-6 rounded-full border border-gray-300 align-middle"
                                              style="background-color: {{ $m->culoare }}"
                                              title="{{ $m->culoare }}"></span>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <button wire:click="comutaActiv({{ $m->id }})"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium {{ $m->activ ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                            @if($m->activ)
                                                <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                                Activa
                                            @else
                                                <x-heroicon-s-x-circle class="w-3.5 h-3.5" />
                                                Inactiva
                                            @endif
                                        </button>
                                    </td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        <div class="inline-flex flex-col items-end gap-1">
                                            <button wire:click="editeaza({{ $m->id }})"
                                                    class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm">
                                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                                                Editeaza
                                            </button>
                                            <button wire:click="editeazaSofer({{ $m->id }})"
                                                    class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-800 text-xs">
                                                <x-heroicon-m-user class="w-3.5 h-3.5" />
                                                {{ $sofer ? 'Editeaza sofer' : 'Asociaza sofer' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <x-heroicon-o-truck class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                        Nicio masina.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $masini->links() }}
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
                    {{ $editandId ? 'Editare masina' : 'Adauga masina' }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Denumire</label>
                        <input type="text" wire:model="denumire" maxlength="100"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        @error('denumire') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nr. inmatriculare</label>
                        <input type="text" wire:model="nr_inmatriculare" maxlength="20"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono uppercase" />
                        @error('nr_inmatriculare') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Depozit</label>
                        <select wire:model="id_depozit"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                            <option value="">— niciunul —</option>
                            @foreach($depozite as $d)
                                <option value="{{ $d->id }}">{{ $d->denumire }}</option>
                            @endforeach
                        </select>
                        @error('id_depozit') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Culoare (marker harta)</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input type="color" wire:model="culoare"
                                   class="h-10 w-16 rounded border-gray-300 dark:border-gray-700" />
                            <input type="text" wire:model="culoare" maxlength="7"
                                   class="block w-32 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono" />
                        </div>
                        @error('culoare') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" wire:model="activ"
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
        </div>
    </div>

    {{-- Modal sofer (creare / editare din contextul masinii) --}}
    <div x-data="{ deschis: @entangle('modalSoferDeschis') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModalSofer()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">

        <div x-show="deschis" x-on:click="$wire.inchideModalSofer()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"></div>

        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-lg sm:mx-auto">
            <form wire:submit.prevent="salveazaSofer" class="p-6">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                    <x-heroicon-o-user-circle class="w-5 h-5 text-emerald-600" />
                    {{ $soferEditandId ? 'Editare sofer' : 'Adauga sofer' }}
                </h3>
                <p class="text-xs text-gray-500 mb-4">
                    Cont de tip <strong>Sofer</strong>. Va vedea DOAR comenzile alocate masinii sale.
                </p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nume complet</label>
                        <input type="text" wire:model="soferName" maxlength="255" autofocus
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        @error('soferName') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input type="email" wire:model="soferEmail" maxlength="255"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        @error('soferEmail') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Username
                            <span class="text-xs text-gray-500">(optional, pentru login rapid)</span>
                        </label>
                        <input type="text" wire:model="soferUsername" maxlength="50"
                               placeholder="ex: ion.popescu"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono lowercase" />
                        <p class="text-[11px] text-gray-500 mt-1">Litere mici, cifre, punct sau sublinie (3-50). Soferul poate folosi fie email, fie username la login.</p>
                        @error('soferUsername') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Masina asociata
                            @if($soferDenumireMasina)
                                <span class="text-xs text-emerald-600 ml-1">(pre-selectata)</span>
                            @endif
                        </label>
                        <select wire:model="soferIdMasina"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                            <option value="">— alege masina —</option>
                            @foreach($masiniPentruSelect as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->denumire }} ({{ $opt->nr_inmatriculare }})</option>
                            @endforeach
                        </select>
                        @error('soferIdMasina') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Parola
                            @if($soferEditandId)
                                <span class="text-xs text-gray-500">(lasati gol pentru a pastra parola actuala)</span>
                            @endif
                        </label>
                        <input type="password" wire:model="soferPassword" maxlength="255" autocomplete="new-password"
                               placeholder="{{ $soferEditandId ? 'Lasati gol pentru pastrare' : 'Minim 6 caractere' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        @error('soferPassword') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" wire:model="soferConfirmat"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="ml-2">Cont activat (poate face login)</span>
                    </label>
                </div>

                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" wire:click="inchideModalSofer"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm rounded-md">
                        <x-heroicon-m-x-mark class="w-4 h-4" />
                        Anuleaza
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-check class="w-4 h-4" />
                        Salveaza sofer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
