<div>
    <x-slot name="header">
        <h2 class="flex items-center gap-2 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            <x-heroicon-o-user-group class="w-6 h-6 text-indigo-600" />
            Utilizatori
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
                @if (session('eroare'))
                    <div class="mb-4 px-4 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm flex items-center gap-2">
                        <x-heroicon-s-x-circle class="w-5 h-5 text-red-500 flex-shrink-0" />
                        {{ session('eroare') }}
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                        <div class="relative w-full sm:w-72">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
                            <input type="text" wire:model.live.debounce.300ms="cautare"
                                   placeholder="Cauta dupa nume sau email..."
                                   class="pl-9 rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm w-full" />
                        </div>
                        <select wire:model.live="filtruRol"
                                class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                            <option value="">Toate rolurile</option>
                            @foreach($roluri as $tip => $eticheta)
                                <option value="{{ $tip }}">{{ $eticheta }}</option>
                            @endforeach
                        </select>
                        <label class="inline-flex items-center text-sm text-gray-600 dark:text-gray-300">
                            <input type="checkbox" wire:model.live="arataNeconfirmati"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                            <span class="ml-2">Arata si neconfirmati</span>
                        </label>
                    </div>
                    <button wire:click="nou"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        Adauga utilizator
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Nume</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Email</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Rol</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300 hidden md:table-cell">Asociere</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Stare</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse($utilizatori as $u)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $u->name }}
                                        @if($u->id === $userCurentId)
                                            <span class="ml-1 text-xs text-indigo-600">(tu)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-mono text-xs">
                                        <div>{{ $u->email }}</div>
                                        @if($u->username)
                                            <div class="text-[11px] text-gray-500 mt-0.5">@<span>{{ $u->username }}</span></div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $this->culoareRol($u->tip) }}">
                                            {{ $this->etichetaRol($u->tip) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300 text-xs hidden md:table-cell">
                                        @if($u->isSofer() && $u->masina)
                                            <span class="inline-flex items-center gap-1">
                                                <x-heroicon-m-truck class="w-3.5 h-3.5 text-gray-400" />
                                                {{ $u->masina->denumire }}
                                            </span>
                                        @elseif($u->isClient() && $u->client)
                                            <span class="inline-flex items-center gap-1">
                                                <x-heroicon-m-user class="w-3.5 h-3.5 text-gray-400" />
                                                {{ $u->client->denumire }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <button wire:click="comutaConfirmat({{ $u->id }})"
                                                @if($u->id === $userCurentId) disabled title="Nu va puteti dezactiva propriul cont" @endif
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium
                                                    {{ $u->confirmat ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}
                                                    {{ $u->id === $userCurentId ? 'opacity-60 cursor-not-allowed' : '' }}">
                                            @if($u->confirmat)
                                                <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                                Activ
                                            @else
                                                <x-heroicon-s-x-circle class="w-3.5 h-3.5" />
                                                Dezactivat
                                            @endif
                                        </button>
                                        {{-- Faza 6.3 — Status invitatie portal pentru tip=3 neconfirmat --}}
                                        @if($u->tip === \App\Models\User::TIP_CLIENT && ! $u->confirmat && $u->activation_token && $u->activation_expires_at)
                                            <div class="mt-1 text-[10px] {{ $u->activation_expires_at->isPast() ? 'text-red-600' : 'text-amber-700' }}">
                                                @if($u->activation_expires_at->isPast())
                                                    Invitatie expirata
                                                @else
                                                    Invitat (expira {{ $u->activation_expires_at->format('d.m.Y') }})
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <div class="inline-flex items-center gap-3">
                                            {{-- Faza 6.3 — Buton invitatie portal pentru tip=3 --}}
                                            @if($u->tip === \App\Models\User::TIP_CLIENT)
                                                <button wire:click="trimiteInvitatie({{ $u->id }})"
                                                        wire:confirm="Trimit linkul de activare catre {{ $u->email }}?"
                                                        class="inline-flex items-center gap-1 text-sky-600 hover:text-sky-800 text-sm"
                                                        title="Genereaza un nou link si il trimite prin email">
                                                    <x-heroicon-m-envelope class="w-4 h-4" />
                                                    @if($u->activation_token)
                                                        Re-trimite
                                                    @else
                                                        Invita
                                                    @endif
                                                </button>
                                            @endif
                                            <button wire:click="editeaza({{ $u->id }})"
                                                    class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm">
                                                <x-heroicon-m-pencil-square class="w-4 h-4" />
                                                Editeaza
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <x-heroicon-o-user-group class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                        Niciun utilizator pentru filtrele selectate.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $utilizatori->links() }}
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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                    @if($editandId)
                        <x-heroicon-o-pencil-square class="w-5 h-5 text-indigo-600" />
                        Editare utilizator
                    @else
                        <x-heroicon-o-user-plus class="w-5 h-5 text-indigo-600" />
                        Adauga utilizator
                    @endif
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nume</label>
                        <input type="text" wire:model="name" maxlength="255" autofocus
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input type="email" wire:model="email" maxlength="255"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Username
                            <span class="text-xs text-gray-500">(optional, pentru login rapid in loc de email)</span>
                        </label>
                        <input type="text" wire:model="username" maxlength="50"
                               placeholder="ex: ion.popescu sau ion_p"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm font-mono lowercase" />
                        <p class="text-[11px] text-gray-500 mt-1">Litere mici, cifre, punct sau sublinie (3-50 caractere). Fara spatii sau @.</p>
                        @error('username') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rol</label>
                        <select wire:model.live="tip"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                            <option value="">— alege rol —</option>
                            @foreach($roluri as $valoare => $eticheta)
                                <option value="{{ $valoare }}">{{ $eticheta }}</option>
                            @endforeach
                        </select>
                        @error('tip') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    {{-- Camp condit. — sofer trebuie asociat cu o masina --}}
                    @if($tip == \App\Models\User::TIP_SOFER)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Masina asociata
                                <span class="text-xs text-gray-500">(soferul vede DOAR comenzile pe aceasta masina)</span>
                            </label>
                            <select wire:model="idMasina"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                <option value="">— alege masina —</option>
                                @foreach($masini as $m)
                                    <option value="{{ $m->id }}">{{ $m->denumire }} ({{ $m->nr_inmatriculare }})</option>
                                @endforeach
                            </select>
                            @error('idMasina') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    {{-- Camp condit. — client portal asociat cu un client --}}
                    @if($tip == \App\Models\User::TIP_CLIENT)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Client asociat
                                <span class="text-xs text-gray-500">(contul vede DOAR comenzile acestui client)</span>
                            </label>
                            <select wire:model="idClient"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm">
                                <option value="">— alege client —</option>
                                @foreach($clienti as $c)
                                    <option value="{{ $c->id }}">{{ $c->denumire }}</option>
                                @endforeach
                            </select>
                            @error('idClient') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Parola
                            @if($editandId)
                                <span class="text-xs text-gray-500">(lasati gol pentru a pastra parola actuala)</span>
                            @endif
                        </label>
                        <input type="password" wire:model="password" maxlength="255" autocomplete="new-password"
                               placeholder="{{ $editandId ? 'Lasati gol pentru pastrare' : 'Minim 6 caractere' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100 text-sm" />
                        @error('password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" wire:model="confirmat"
                               @if($editandId === $userCurentId) disabled @endif
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="ml-2">
                            Cont activat (confirmat)
                            @if($editandId === $userCurentId)
                                <span class="text-xs text-gray-500">— nu va puteti dezactiva propriul cont</span>
                            @endif
                        </span>
                    </label>
                    @error('confirmat') <span class="block text-xs text-red-600">{{ $message }}</span> @enderror
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
