<div>
    @if(! $tokenValid)
        <div class="text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mb-3">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-600" />
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Link invalid sau expirat</h2>
            <p class="text-sm text-gray-600 mb-4">
                Linkul de activare a expirat sau nu este valid. Poti cere un link nou folosind
                adresa de email asociata contului.
            </p>
            <a href="{{ route('portal.cere-invitatie') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-md hover:bg-sky-700 transition">
                <x-heroicon-o-envelope class="w-4 h-4" />
                Cere o noua invitatie
            </a>
        </div>
    @else
        <div class="text-center mb-5">
            <div class="mx-auto w-12 h-12 rounded-full bg-sky-100 flex items-center justify-center mb-3">
                <x-heroicon-o-key class="w-6 h-6 text-sky-600" />
            </div>
            <h2 class="text-lg font-semibold text-gray-900">Activeaza-ti contul</h2>
            <p class="text-sm text-gray-600 mt-1">
                Bun venit, <strong>{{ $utilizator->name }}</strong>. Seteaza-ti o parola pentru a accesa portalul.
            </p>
            <p class="text-xs text-gray-400 mt-2">
                Email: {{ $utilizator->email }}
            </p>
        </div>

        <form wire:submit="activeaza" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="password">Parola noua</label>
                <input wire:model="password" type="password" id="password" autocomplete="new-password"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
                @error('password')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-1">Minim 8 caractere.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="password_confirmation">Confirma parola</label>
                <input wire:model="password_confirmation" type="password" id="password_confirmation" autocomplete="new-password"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-md hover:bg-sky-700 transition">
                <x-heroicon-o-check class="w-4 h-4" />
                Activeaza contul si autentifica-ma
            </button>
        </form>
    @endif
</div>
