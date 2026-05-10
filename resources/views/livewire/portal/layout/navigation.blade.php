<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<aside x-data="{ open: false }"
       class="lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-sky-900 text-sky-100">

    {{-- Buton hamburger mobil --}}
    <div class="lg:hidden flex items-center justify-between px-4 py-3 bg-sky-900 text-sky-100">
        <a href="{{ route('portal.comenzi.index') }}" wire:navigate class="flex items-center gap-2 font-semibold">
            <x-heroicon-o-beaker class="w-6 h-6 text-sky-300" />
            Portal client
        </a>
        <button @click="open = !open" class="p-2 rounded hover:bg-sky-800">
            <x-heroicon-o-bars-3 x-show="!open" class="h-6 w-6" />
            <x-heroicon-o-x-mark x-show="open" class="h-6 w-6" />
        </button>
    </div>

    {{-- Continut sidebar --}}
    <div :class="open ? 'block' : 'hidden'" class="lg:block lg:flex-1 lg:flex lg:flex-col">
        {{-- Header sidebar (logo + nume aplicatie) --}}
        <div class="hidden lg:flex items-center h-16 px-6 bg-sky-950 border-b border-sky-800">
            <a href="{{ route('portal.comenzi.index') }}" wire:navigate class="flex items-center gap-2 text-lg font-semibold text-white">
                <x-heroicon-o-beaker class="w-6 h-6 text-sky-300" />
                Portal client
            </a>
        </div>

        {{-- Navigatie portal client --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <a href="{{ route('portal.comenzi.index') }}" wire:navigate
               @class([
                   'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition',
                   'bg-sky-800 text-white' => request()->routeIs('portal.comenzi.index'),
                   'text-sky-100 hover:bg-sky-800 hover:text-white' => ! request()->routeIs('portal.comenzi.index'),
               ])>
                <x-heroicon-o-clipboard-document-list class="w-5 h-5" />
                Comenzile mele
            </a>
            <a href="{{ route('portal.comenzi.noua') }}" wire:navigate
               @class([
                   'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition',
                   'bg-sky-800 text-white' => request()->routeIs('portal.comenzi.noua'),
                   'text-sky-100 hover:bg-sky-800 hover:text-white' => ! request()->routeIs('portal.comenzi.noua'),
               ])>
                <x-heroicon-o-plus-circle class="w-5 h-5" />
                Comanda noua
            </a>
            <a href="{{ route('portal.cont') }}" wire:navigate
               @class([
                   'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition',
                   'bg-sky-800 text-white' => request()->routeIs('portal.cont'),
                   'text-sky-100 hover:bg-sky-800 hover:text-white' => ! request()->routeIs('portal.cont'),
               ])>
                <x-heroicon-o-user-circle class="w-5 h-5" />
                Contul meu
            </a>
            <a href="{{ route('profile') }}" wire:navigate
               @class([
                   'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition',
                   'bg-sky-800 text-white' => request()->routeIs('profile'),
                   'text-sky-100 hover:bg-sky-800 hover:text-white' => ! request()->routeIs('profile'),
               ])>
                <x-heroicon-o-key class="w-5 h-5" />
                Schimba parola
            </a>
        </nav>

        {{-- Footer sidebar: utilizator + logout --}}
        <div class="border-t border-sky-800 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 min-w-0">
                    <x-heroicon-o-user-circle class="w-8 h-8 text-sky-400 flex-shrink-0" />
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-sky-300 truncate">
                            @if(auth()->user()->client)
                                {{ auth()->user()->client->denumire }}
                            @else
                                Client
                            @endif
                        </div>
                    </div>
                </div>
                <button wire:click="logout"
                        title="Iesire"
                        class="ml-3 p-2 rounded text-sky-300 hover:bg-sky-800 hover:text-white transition">
                    <x-heroicon-o-arrow-right-on-rectangle class="h-5 w-5" />
                </button>
            </div>
        </div>
    </div>
</aside>
