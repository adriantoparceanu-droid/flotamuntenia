<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-envelope class="w-6 h-6 text-indigo-600" />
            Sabloane email
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('mesaj'))
                <div class="px-4 py-2 rounded bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 flex-shrink-0" />
                    {{ session('mesaj') }}
                </div>
            @endif
            @if($mesaj)
                <div class="px-4 py-2 rounded bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 flex-shrink-0" />
                    {{ $mesaj }}
                </div>
            @endif
            @if($eroare)
                <div class="px-4 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm flex items-center gap-2">
                    <x-heroicon-s-x-circle class="w-5 h-5 text-red-500 flex-shrink-0" />
                    {{ $eroare }}
                </div>
            @endif

            {{-- ========== MOD LISTA ========== --}}
            @if(! $editandId)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-sm text-gray-600 dark:text-gray-300">
                    <p class="flex gap-2">
                        <x-heroicon-o-information-circle class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" />
                        <span>
                            Aceste sabloane sunt folosite automat la trimiterea email-urilor (confirmare comenzi, remindere, etc.). Editeaza subiectul si continutul; placeholderele de tipul <code class="px-1 py-0.5 bg-amber-100 text-amber-800 rounded text-xs">{NUME}</code> sunt inlocuite automat la trimitere cu valorile reale.
                            <strong>Daca SMTP nu e configurat</strong> (vezi <a href="{{ route('setari.smtp') }}" wire:navigate class="text-indigo-600 underline">/setari/smtp</a>), email-urile sunt loggate in <code>storage/logs/email-pending.log</code> in loc sa fie trimise.
                        </span>
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-left text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3 font-medium">Denumire</th>
                                <th class="px-4 py-3 font-medium">Cheie</th>
                                <th class="px-4 py-3 font-medium">Subiect</th>
                                <th class="px-4 py-3 font-medium text-center">Stare</th>
                                <th class="px-4 py-3 font-medium text-right">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @foreach($templateuri as $t)
                                <tr wire:key="tpl-{{ $t->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                    <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">{{ $t->denumire }}</td>
                                    <td class="px-4 py-2 text-xs font-mono text-gray-600">{{ $t->cheie }}</td>
                                    <td class="px-4 py-2 text-gray-700 truncate max-w-[24rem]" title="{{ $t->subiect }}">{{ $t->subiect }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <button wire:click="comutaActiv({{ $t->id }})"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium {{ $t->activ ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                            @if($t->activ)
                                                <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                                Activ
                                            @else
                                                <x-heroicon-s-x-circle class="w-3.5 h-3.5" />
                                                Dezactivat
                                            @endif
                                        </button>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <button wire:click="editeaza({{ $t->id }})"
                                                class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm">
                                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                                            Editeaza
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                {{-- ========== MOD EDITARE ========== --}}
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ $denumire }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Cheie: <code class="font-mono">{{ $cheie }}</code></p>
                    </div>
                    <button type="button" wire:click="inchideEditor"
                            class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Inapoi la lista
                    </button>
                </div>

                {{-- Placeholdere disponibile --}}
                @if(! empty($placeholdere))
                    <div class="bg-amber-50 border border-amber-200 sm:rounded-lg p-5">
                        <div class="flex items-start gap-3 mb-3">
                            <x-heroicon-o-bars-3-bottom-left class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                            <div>
                                <h3 class="font-semibold text-amber-900">Placeholdere disponibile pentru acest template</h3>
                                <p class="text-xs text-amber-800 mt-0.5">Insereaza-le in subiect sau body cu acolade — vor fi inlocuite automat la trimitere.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($placeholdere as $cheiePh => $descriere)
                                <div class="bg-white border border-amber-200 rounded px-3 py-1.5 text-xs flex items-center gap-2">
                                    <code class="font-mono font-semibold text-amber-700 whitespace-nowrap">{{ $cheiePh }}</code>
                                    <span class="text-gray-700 truncate" title="{{ $descriere }}">{{ $descriere }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Editor --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <x-heroicon-o-pencil-square class="w-5 h-5 text-indigo-600" />
                            Editor template
                        </h3>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="restaureazaImplicit"
                                    wire:confirm="Sigur vrei sa restaurezi versiunea implicita pentru acest template? Editarile curente vor fi pierdute (dar nu salvate pana nu apesi „Salveaza")."
                                    class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">
                                <x-heroicon-o-arrow-path class="w-4 h-4" />
                                Restaureaza implicit
                            </button>
                            <button type="button" wire:click="salveaza"
                                    class="inline-flex items-center gap-1.5 text-sm px-4 py-1.5 rounded bg-indigo-600 text-white hover:bg-indigo-700 font-medium">
                                <x-heroicon-o-check class="w-4 h-4" />
                                Salveaza
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Subiect email</label>
                        <input type="text" wire:model="subiect" maxlength="255"
                               class="w-full text-sm border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        @error('subiect') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Continut HTML</label>
                        <div wire:ignore
                             wire:key="tpl-editor-{{ $editandId }}"
                             x-data="templateEmailEditor({
                                 initial: @js($continutHtml),
                                 setHtml: (v) => $wire.set('continutHtml', v, false),
                             })"
                             x-init="init()"
                             x-on:livewire:navigating.window="destroy()"
                             class="border border-gray-200 rounded">
                            <textarea x-ref="editor" id="template-email-editor-{{ $editandId }}"></textarea>
                        </div>
                        @error('continutHtml') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endif

        </div>
    </div>

    @script
    <script>
        // Wrapper Alpine pentru editorul TinyMCE pe template-urile email.
        // Refoloseste init/destroy globale (window.initContractEditor) — definite in app.js
        // pentru editorul de contract; sunt generice si functioneaza pentru orice WYSIWYG.
        window.templateEmailEditor = function ({ initial, setHtml }) {
            return {
                editor: null,
                async init() {
                    window.destroyContractEditor(this.$refs.editor);
                    const eds = await window.initContractEditor(this.$refs.editor, {
                        initialContent: initial,
                        onChange: (html) => setHtml(html),
                    });
                    this.editor = eds && eds[0];
                },
                destroy() {
                    if (this.editor) this.editor.remove();
                    this.editor = null;
                },
            };
        };
    </script>
    @endscript
</div>
