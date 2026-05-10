<div>
    @if($trimis)
        <div class="text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center mb-3">
                <x-heroicon-o-check-circle class="w-6 h-6 text-emerald-600" />
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Cerere inregistrata</h2>
            <p class="text-sm text-gray-600 mb-4">
                Daca adresa de email este asociata unui cont activ, vei primi in scurt timp un email
                cu un link nou de activare. Verifica si folder-ul de spam.
            </p>
            <a href="{{ route('login') }}" wire:navigate
               class="inline-flex items-center gap-2 text-sm text-sky-700 hover:text-sky-900">
                <x-heroicon-o-arrow-left class="w-4 h-4" />
                Inapoi la autentificare
            </a>
        </div>
    @else
        <div class="text-center mb-5">
            <div class="mx-auto w-12 h-12 rounded-full bg-sky-100 flex items-center justify-center mb-3">
                <x-heroicon-o-envelope class="w-6 h-6 text-sky-600" />
            </div>
            <h2 class="text-lg font-semibold text-gray-900">Cere o noua invitatie</h2>
            <p class="text-sm text-gray-600 mt-1">
                Introdu adresa de email asociata contului tau si vom retrimite linkul de activare.
            </p>
        </div>

        <form wire:submit="trimite" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="email">Adresa de email</label>
                <input wire:model="email" type="email" id="email" autocomplete="email"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
                @error('email')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-sky-600 text-white text-sm font-medium rounded-md hover:bg-sky-700 transition">
                <x-heroicon-o-paper-airplane class="w-4 h-4" />
                Trimite link de activare
            </button>

            <div class="text-center">
                <a href="{{ route('login') }}" wire:navigate
                   class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-700">
                    <x-heroicon-o-arrow-left class="w-3.5 h-3.5" />
                    Inapoi la autentificare
                </a>
            </div>
        </form>
    @endif
</div>
