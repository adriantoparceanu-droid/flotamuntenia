<x-app-layout>
    <div class="min-h-[60vh] flex items-center justify-center px-4">
        <div class="max-w-md w-full text-center">

            {{-- Iconița modulului dacă îl găsim, altfel generic --}}
            @php
                $definitie = $definitii[$cheie] ?? null;
                $icon = $definitie ? $definitie['icon'] : 'puzzle-piece';
                $numeMod = $definitie ? $definitie['nume'] : ucwords(str_replace('_', ' ', $slug));
            @endphp

            <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-6">
                <x-dynamic-component :component="'heroicon-o-' . $icon" class="w-8 h-8 text-gray-400" />
            </div>

            <h1 class="text-xl font-semibold text-gray-800 mb-2">
                Acest modul nu este activ
            </h1>

            <p class="text-gray-500 mb-2">
                Modulul <span class="font-medium text-gray-700">„{{ $numeMod }}"</span>
                nu este activat pe această instalare.
            </p>

            <p class="text-sm text-gray-400 mb-8">
                Contactați administratorul platformei pentru mai multe informații.
            </p>

            <a href="{{ route(auth()->user()->homeRoute()) }}"
               wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                <x-heroicon-m-arrow-left class="w-4 h-4" />
                Înapoi la Panou principal
            </a>
        </div>
    </div>
</x-app-layout>
