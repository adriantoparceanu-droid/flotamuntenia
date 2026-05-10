<div>
    @if(session('mesaj_documente'))
        <div class="mb-4 px-4 py-2 rounded bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
            <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 flex-shrink-0" />
            {{ session('mesaj_documente') }}
        </div>
    @endif
    @if(session('eroare_documente'))
        <div class="mb-4 px-4 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm flex items-center gap-2">
            <x-heroicon-s-x-circle class="w-5 h-5 text-red-500 flex-shrink-0" />
            {{ session('eroare_documente') }}
        </div>
    @endif

    {{-- Card upload --}}
    <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-5 mb-5">
        <div class="flex items-center gap-2 mb-3">
            <x-heroicon-o-arrow-up-tray class="w-5 h-5 text-indigo-600" />
            <h3 class="font-medium text-gray-900 dark:text-gray-100">Incarca documente noi</h3>
        </div>

        <form wire:submit="incarca" class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Fisiere (max 5, fiecare maxim 10 MB)
                </label>
                <input type="file" wire:model="fisiereNoi" multiple
                       class="block w-full text-sm text-gray-700 dark:text-gray-300
                              file:mr-3 file:py-2 file:px-3 file:rounded file:border-0
                              file:text-sm file:font-medium file:bg-indigo-100 file:text-indigo-700
                              hover:file:bg-indigo-200 cursor-pointer" />
                @error('fisiereNoi') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                @error('fisiereNoi.*') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                {{-- Preview lista fisiere selectate --}}
                @if(! empty($fisiereNoi))
                    <ul class="mt-2 space-y-1 text-xs text-gray-600">
                        @foreach($fisiereNoi as $f)
                            <li class="flex items-center gap-1.5">
                                <x-heroicon-m-paper-clip class="w-3.5 h-3.5 text-gray-400" />
                                {{ $f->getClientOriginalName() }}
                                <span class="text-gray-400">({{ number_format($f->getSize() / 1024, 1, ',', '.') }} KB)</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Descriere (optionala — aceeasi pentru toate fisierele din acest upload)
                </label>
                <textarea wire:model="descriereComuna" rows="2" maxlength="1000"
                          placeholder="ex: Contract semnat 2026, Factura WF12345 din 15.04.2026, etc."
                          class="block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm"></textarea>
                @error('descriereComuna') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="incarca,fisiereNoi"
                        class="inline-flex items-center gap-1.5 text-sm px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 font-medium">
                    <span wire:loading.remove wire:target="incarca,fisiereNoi" class="inline-flex items-center gap-1.5">
                        <x-heroicon-o-arrow-up-tray class="w-4 h-4" />
                        Incarca
                    </span>
                    <span wire:loading wire:target="incarca,fisiereNoi" class="inline-flex items-center gap-1.5">
                        <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin" />
                        Se incarca...
                    </span>
                </button>
            </div>
        </form>
    </div>

    {{-- Lista documente existente --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
            <x-heroicon-o-folder-open class="w-5 h-5 text-gray-500" />
            Documente atasate ({{ $documente->count() }})
        </h3>

        @if($documente->isEmpty())
            <div class="text-center py-10 text-gray-500 dark:text-gray-400 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                <x-heroicon-o-folder-open class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                <p class="text-sm">Niciun document incarcat inca.</p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($documente as $doc)
                        <li wire:key="doc-{{ $doc->id }}" class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                            <x-dynamic-component :component="'heroicon-o-' . $doc->iconHeroicon()" class="w-8 h-8 text-indigo-400 flex-shrink-0" />

                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" title="{{ $doc->nume_fisier }}">
                                    {{ $doc->nume_fisier }}
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-2 flex-wrap">
                                    <span>{{ $doc->marimeUmana() }}</span>
                                    <span class="text-gray-300">•</span>
                                    <span>{{ $doc->created_at->format('d.m.Y H:i') }}</span>
                                    @if($doc->uploadedBy)
                                        <span class="text-gray-300">•</span>
                                        <span>de {{ $doc->uploadedBy->name }}</span>
                                    @endif
                                </div>
                                @if($doc->descriere)
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 italic">{{ $doc->descriere }}</p>
                                @endif
                            </div>

                            <div class="flex items-center gap-1 flex-shrink-0">
                                <a href="{{ route('clienti.document-download', ['document' => $doc->id]) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-indigo-700 hover:bg-indigo-50 rounded">
                                    <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                                    Descarca
                                </a>
                                <button wire:click="deschideModalStergere({{ $doc->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-red-600 hover:bg-red-50 rounded">
                                    <x-heroicon-m-trash class="w-4 h-4" />
                                    Sterge
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Modal confirmare stergere --}}
    <div x-data="{ deschis: @entangle('modalStergere') }"
         x-show="deschis"
         x-on:keydown.escape.window="$wire.inchideModalStergere()"
         style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0">
        <div x-show="deschis" x-on:click="$wire.inchideModalStergere()"
             class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>

        <div x-show="deschis"
             class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:max-w-md sm:mx-auto">
            <div class="p-6">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-600" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Stergi documentul?</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            <strong>{{ $denumireDeSters }}</strong>
                            <br>
                            Aceasta actiune va sterge definitiv documentul si fisierul de pe disk. Nu se poate anula.
                        </p>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="inchideModalStergere"
                            class="inline-flex items-center px-3 py-2 rounded border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">
                        Anuleaza
                    </button>
                    <button type="button" wire:click="confirmaStergere"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded bg-red-600 text-white text-sm hover:bg-red-700">
                        <x-heroicon-m-trash class="w-4 h-4" />
                        Sterge definitiv
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
