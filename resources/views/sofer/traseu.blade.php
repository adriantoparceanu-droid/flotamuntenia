<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Traseul meu &mdash; {{ now()->format('d.m.Y') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <p>Aici vei vedea lista comenzilor zilnice asignate masinii tale.</p>
                <p class="text-xs text-gray-400 mt-3">
                    Pagina va fi populata in Faza 2 a implementarii (Operatiuni Core).
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
