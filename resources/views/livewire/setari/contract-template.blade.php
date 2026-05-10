<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-document-text class="w-6 h-6 text-indigo-600" />
            Sablon contract
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

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
            @error('html')
                <div class="px-4 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm">{{ $message }}</div>
            @enderror

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-sm text-gray-600 dark:text-gray-300">
                <p class="flex gap-2">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" />
                    <span>
                        Acesta e <strong>template-ul global</strong> al contractului de prestari servicii. Cand un client e accesat pentru prima oara la tab-ul „Contract" pe pagina detalii, template-ul e clonat si datele clientului sunt substituite in placeholdere. Adminul poate edita ulterior contractul individual al fiecarui client fara a afecta template-ul global.
                    </span>
                </p>
            </div>

            <!-- Placeholdere disponibile -->
            <div class="bg-amber-50 border border-amber-200 sm:rounded-lg p-5">
                <div class="flex items-start gap-3 mb-3">
                    <x-heroicon-o-bars-3-bottom-left class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <h3 class="font-semibold text-amber-900">Placeholdere disponibile</h3>
                        <p class="text-xs text-amber-800 mt-0.5">Insereaza-le exact ca in lista de mai jos (cu acolade) — vor fi inlocuite la generarea fiecarui contract.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach($placeholdere as $cheie => $descriere)
                        <div class="bg-white border border-amber-200 rounded px-3 py-1.5 text-xs flex items-center gap-2">
                            <code class="font-mono font-semibold text-amber-700 whitespace-nowrap">{{ $cheie }}</code>
                            <span class="text-gray-700 truncate" title="{{ $descriere }}">{{ $descriere }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Editor TinyMCE -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <x-heroicon-o-pencil-square class="w-5 h-5 text-indigo-600" />
                        Editor template
                    </h3>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="restaureazaImplicit"
                                wire:confirm="Sigur vrei sa restaurezi template-ul implicit? Editarile curente vor fi pierdute (dar nu salvate pana nu apesi „Salveaza")."
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

                <div wire:ignore
                     x-data="contractTemplateEditor({
                         initial: @js($html),
                         setHtml: (v) => $wire.set('html', v, false),
                     })"
                     x-init="init()"
                     x-on:livewire:navigating.window="destroy()"
                     class="border border-gray-200 rounded">
                    <textarea x-ref="editor" id="contract-template-editor"></textarea>
                </div>
            </div>

            <!-- Sectiune Preview -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <x-heroicon-o-eye class="w-5 h-5 text-indigo-600" />
                        Preview cu date reale
                    </h3>
                    <div class="flex items-center gap-2">
                        @if($modPreview)
                            <button type="button" wire:click="inchidePreview"
                                    class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                                Inchide preview
                            </button>
                        @endif
                    </div>
                </div>

                @if(! $modPreview)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Client pentru preview</label>
                            <select wire:model="clientPreviewId"
                                    class="w-full text-sm border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @forelse($clientiPreview as $c)
                                    <option value="{{ $c->id }}">{{ $c->denumire }} ({{ $c->cod_client }})</option>
                                @empty
                                    <option disabled>Niciun client disponibil</option>
                                @endforelse
                            </select>
                        </div>
                        <button type="button" wire:click="activeazaPreview"
                                @class([
                                    'inline-flex items-center justify-center gap-1.5 text-sm px-4 py-2 rounded text-white font-medium',
                                    'bg-emerald-600 hover:bg-emerald-700' => $clientiPreview->isNotEmpty(),
                                    'bg-gray-300 cursor-not-allowed' => $clientiPreview->isEmpty(),
                                ])
                                @disabled($clientiPreview->isEmpty())>
                            <x-heroicon-o-eye class="w-4 h-4" />
                            Genereaza preview
                        </button>
                    </div>
                @else
                    <div class="border border-gray-200 rounded p-6 bg-gray-50 max-h-[600px] overflow-y-auto prose prose-sm max-w-none">
                        {!! $previewHtml !!}
                    </div>
                @endif
            </div>

        </div>
    </div>

    @script
    <script>
        // Wrapper Alpine pentru editorul TinyMCE pe template-ul global.
        // `contractTemplateEditor` e referit din x-data; expus pe window
        // ca sa fie accesibil indiferent de momentul evaluarii scriptului.
        window.contractTemplateEditor = function ({ initial, setHtml }) {
            return {
                editor: null,
                async init() {
                    // Distrugem orice instanta veche pe acelasi target
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
