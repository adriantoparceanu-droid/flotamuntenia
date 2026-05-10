<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-clock class="w-6 h-6 text-indigo-600" />
            Cron jobs
        </h2>
    </x-slot>

    <div class="py-8" x-data="{
            copiat: null,
            copy(text, id) {
                navigator.clipboard.writeText(text).then(() => {
                    this.copiat = id;
                    setTimeout(() => { this.copiat = null; }, 2000);
                });
            }
        }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if($mesaj)
                <div class="px-4 py-2 rounded bg-amber-50 text-amber-800 border border-amber-200 text-sm flex items-center gap-2">
                    <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-500 flex-shrink-0" />
                    {{ $mesaj }}
                </div>
            @endif

            {{-- Info --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                <p class="flex gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-indigo-500 flex-shrink-0 mt-0.5" />
                    <span>
                        Cron jobs <strong>NU ruleaza automat</strong> in aceasta aplicatie. Pentru a le activa, adauga URL-urile de mai jos in cPanel → Cron Jobs cu schedule-ul recomandat. Endpoint-urile sunt securizate printr-un token UUID generat aici — daca cineva afla tokenul, poate rula cron-urile remote, deci pastreaza URL-ul confidential.
                    </span>
                </p>
            </div>

            {{-- Token + actiuni --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5 space-y-4">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <x-heroicon-o-key class="w-5 h-5 text-indigo-600" />
                        Token cron
                    </h3>
                    <button type="button" wire:click="regenereaza"
                            wire:confirm="Sigur vrei sa regenerezi tokenul? Toate URL-urile din cPanel cron vor deveni invalide si trebuie actualizate manual."
                            class="inline-flex items-center gap-1.5 text-sm px-3 py-1.5 rounded border border-amber-300 text-amber-700 hover:bg-amber-50">
                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                        Regenereaza token
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <code class="flex-1 font-mono text-sm bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 select-all">{{ $token }}</code>
                    <button type="button" @click="copy('{{ $token }}', 'token')"
                            class="inline-flex items-center gap-1.5 text-sm px-3 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">
                        <x-heroicon-o-clipboard-document class="w-4 h-4" />
                        <span x-show="copiat !== 'token'">Copiaza</span>
                        <span x-show="copiat === 'token'" class="text-green-600">Copiat!</span>
                    </button>
                </div>
            </div>

            {{-- Cron jobs --}}
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">URL-uri pentru cPanel</h3>

                @foreach($cronJobs as $idx => $cron)
                    <div wire:key="cron-{{ $idx }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5 space-y-3">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <x-heroicon-o-bolt class="w-4 h-4 text-indigo-500" />
                                    {{ $cron['denumire'] }}
                                </h4>
                                <p class="text-xs text-gray-500 mt-0.5 font-mono">{{ $cron['job'] }}</p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                <x-heroicon-m-clock class="w-3.5 h-3.5" />
                                {{ $cron['schedule_human'] }}
                            </span>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $cron['descriere'] }}</p>

                        <div class="space-y-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">URL cron (copy in cPanel → Command)</label>
                                <div class="flex items-center gap-2">
                                    <code class="flex-1 font-mono text-xs bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800 break-all select-all">{{ $cron['url'] }}</code>
                                    <button type="button" @click="copy('{{ $cron['url'] }}', 'url-{{ $idx }}')"
                                            class="inline-flex items-center gap-1.5 text-xs px-2.5 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50 flex-shrink-0">
                                        <x-heroicon-o-clipboard-document class="w-4 h-4" />
                                        <span x-show="copiat !== 'url-{{ $idx }}'">Copiaza</span>
                                        <span x-show="copiat === 'url-{{ $idx }}'" class="text-green-600">Copiat!</span>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Comanda pentru cron (in cPanel → Command, foloseste curl):</label>
                                <code class="block font-mono text-xs bg-gray-900 text-gray-100 rounded px-3 py-2 break-all select-all">curl -s "{{ $cron['url'] }}" &gt; /dev/null 2&gt;&amp;1</code>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Schedule (cPanel Common Settings → Custom)</label>
                                <code class="block font-mono text-xs bg-gray-50 border border-gray-200 rounded px-3 py-2 text-gray-800">{{ $cron['schedule'] }}</code>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Documentatie cPanel --}}
            <div class="bg-amber-50 border border-amber-200 sm:rounded-lg p-5">
                <div class="flex items-start gap-3 mb-3">
                    <x-heroicon-o-academic-cap class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                    <h3 class="font-semibold text-amber-900">Cum se adauga in cPanel cron jobs</h3>
                </div>
                <ol class="list-decimal list-inside text-sm text-amber-900 space-y-1.5 ml-2">
                    <li>Logheaza-te in cPanel-ul hosting-ului tau.</li>
                    <li>Sectiunea <strong>Advanced</strong> → <strong>Cron Jobs</strong>.</li>
                    <li>La <strong>Common Settings</strong>, alege schedule-ul recomandat (sau introdu-l manual la <strong>Custom</strong>).</li>
                    <li>La <strong>Command</strong>, copiaza comanda <code>curl ... &gt; /dev/null 2&gt;&amp;1</code> de mai sus.</li>
                    <li>Click <strong>Add New Cron Job</strong>. Repeta pentru fiecare cron.</li>
                    <li>Verifica in <code>storage/logs/cron.log</code> ca apar inregistrari dupa prima rulare.</li>
                </ol>
            </div>

            {{-- Log info --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5 text-sm text-gray-700 dark:text-gray-300">
                <p class="flex gap-2">
                    <x-heroicon-o-document-text class="w-5 h-5 text-gray-500 flex-shrink-0 mt-0.5" />
                    <span>
                        Toate accesarile endpoint-urilor cron sunt loggate in <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">storage/logs/cron.log</code> (cu IP, timestamp, status). Apelurile cu token gresit primesc 404 si sunt loggate ca warning — util pentru a detecta incercari de access neautorizate.
                    </span>
                </p>
            </div>

        </div>
    </div>
</div>
