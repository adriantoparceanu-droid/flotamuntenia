<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-5">
            <h1 class="text-2xl font-semibold text-gray-900">Contul meu</h1>
            <p class="text-sm text-gray-500 mt-1">Date de contact si adrese de livrare configurate.</p>
        </div>

        @if(! $client)
            <div class="bg-white border border-gray-200 rounded-lg p-6 text-center text-gray-500">
                <x-heroicon-o-exclamation-triangle class="w-10 h-10 mx-auto mb-2 text-amber-400" />
                <p>Contul tau nu este asociat unui client. Contacteaza-ne pentru asistenta.</p>
            </div>
        @else
            {{-- Card 1: Date client --}}
            <div class="bg-white border border-gray-200 rounded-lg p-5 mb-4">
                <div class="flex items-center gap-2 mb-4">
                    <x-heroicon-o-identification class="w-5 h-5 text-sky-600" />
                    <h2 class="font-medium text-gray-900">Date {{ $client->isPJ() ? 'firma' : 'personale' }}</h2>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wide">Cod client</dt>
                        <dd class="font-medium text-gray-900">{{ $client->cod_client ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wide">Denumire</dt>
                        <dd class="font-medium text-gray-900">{{ $client->denumire ?? '—' }}</dd>
                    </div>
                    @if($client->isPJ())
                        <div>
                            <dt class="text-xs text-gray-500 uppercase tracking-wide">CUI / CIF</dt>
                            <dd class="text-gray-900">{{ $client->cif ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 uppercase tracking-wide">Reg. Comertului</dt>
                            <dd class="text-gray-900">{{ $client->reg_com ?? '—' }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wide">Email</dt>
                        <dd class="text-gray-900">{{ $client->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wide">Telefon</dt>
                        <dd class="text-gray-900">{{ $client->telefon ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-gray-500 uppercase tracking-wide">Adresa sediu</dt>
                        <dd class="text-gray-900">{{ $client->adresaCompleta() ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Card 2: Adrese de livrare --}}
            <div class="bg-white border border-gray-200 rounded-lg p-5 mb-4">
                <div class="flex items-center gap-2 mb-4">
                    <x-heroicon-o-map-pin class="w-5 h-5 text-sky-600" />
                    <h2 class="font-medium text-gray-900">Adrese de livrare</h2>
                </div>

                @if($client->adrese->isEmpty())
                    <p class="text-sm text-gray-500">Nicio adresa de livrare configurata.</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($client->adrese as $a)
                            <li class="py-3">
                                @if($a->denumire)
                                    <div class="text-sm font-medium text-gray-900">{{ $a->denumire }}</div>
                                @endif
                                <div class="text-sm text-gray-700">{{ $a->adresaCompleta() }}</div>
                                @if($a->interfon)
                                    <div class="text-xs text-gray-500 mt-1">Interfon: {{ $a->interfon }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Notificare modificari --}}
            <div class="bg-sky-50 border border-sky-200 rounded-lg p-4 text-sm text-sky-800">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-sky-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <strong>Pentru modificari</strong> (date contact, adresa noua de livrare, modificare configurare abonament)
                        contacteaza-ne la
                        @if($client->email)
                            <a href="mailto:contact@flotamuntenia.ro" class="underline">contact@flotamuntenia.ro</a>
                        @else
                            adresa de email a operatorului
                        @endif
                        sau la telefon.
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
