<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    //
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Inregistrarea conturilor noi se face de catre administrator.
        Daca ai un cod de client si vrei sa-ti creezi un cont in portalul clientilor,
        contacteaza-ne pentru a-l activa.
    </div>

    <div class="flex items-center justify-end mt-4">
        <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
           href="{{ route('login') }}" wire:navigate>
            Inapoi la autentificare
        </a>
    </div>
</div>
