<?php

use Illuminate\Support\Facades\Route;

// Pagina publica (welcome) — redirect catre login daca nu e autentificat
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->homeRoute());
    }
    return redirect()->route('login');
});

// Rute admin (tip=1) si superadmin (tip=100)
Route::middleware(['auth', 'rol:admin,superadmin'])->group(function () {
    Route::get('dashboard', \App\Livewire\Dashboard::class)->name('dashboard');

    // Master data — Faza 1.2 (core — fara middleware modul)
    Route::prefix('setari')->name('setari.')->group(function () {
        Route::get('masini', \App\Livewire\Setari\Masini::class)->name('masini');
        Route::get('depozite', \App\Livewire\Setari\Depozite::class)->name('depozite');
        Route::get('catalog', \App\Livewire\Setari\Catalog::class)->name('catalog');
        Route::get('tva', \App\Livewire\Setari\Tva::class)->name('tva');
        Route::get('utilizatori', \App\Livewire\Setari\Utilizatori::class)->name('utilizatori');

        // Modul: Facturare Electronică
        Route::get('facturare', \App\Livewire\Setari\Facturare::class)
            ->middleware('modul:facturare')
            ->name('facturare');

        // Modul: Contracte PDF
        Route::get('contract-template', \App\Livewire\Setari\ContractTemplate::class)
            ->middleware('modul:contracte')
            ->name('contract-template');

        // Modul: Email & Notificari
        Route::get('template-email', \App\Livewire\Setari\TemplateuriEmail::class)
            ->middleware('modul:email')
            ->name('template-email');
        Route::get('smtp', \App\Livewire\Setari\Smtp::class)
            ->middleware('modul:email')
            ->name('smtp');

        // Modul: Cron & Automatizari
        Route::get('cron', \App\Livewire\Setari\Cron::class)
            ->middleware('modul:cron')
            ->name('cron');
    });

    // Clienti — Faza 1.3 (core — fara middleware modul)
    // Atentie la ordinea rutelor: 'nou' inainte de '{client}' ca sa nu fie capturat ca parametru.
    Route::prefix('clienti')->name('clienti.')->group(function () {
        Route::get('/', \App\Livewire\Clienti\Index::class)->name('index');
        Route::get('nou', \App\Livewire\Clienti\Form::class)->name('nou');
        Route::get('{client}', \App\Livewire\Clienti\Detalii::class)->name('detalii');
        Route::get('{client}/editare', \App\Livewire\Clienti\Form::class)->name('editare');

        // Modul: Contracte PDF
        Route::get('{client}/contract.pdf', \App\Http\Controllers\ContractPdfController::class)
            ->middleware('modul:contracte')
            ->name('contract-pdf');

        // Faza 6.7 — Descarcare document atasat (toate tipurile, disk privat)
        Route::get('documente/{document}/download', \App\Http\Controllers\DocumentDownloadController::class)
            ->name('document-download');
    });

    // Comenzi — Faza 2.1 (core — fara middleware modul)
    // Atentie la ordine: 'noua' inainte de '{comanda}' ca sa nu fie capturat ca parametru.
    Route::prefix('comenzi')->name('comenzi.')->group(function () {
        Route::get('/', \App\Livewire\Comenzi\Index::class)->name('index');
        Route::get('noua', \App\Livewire\Comenzi\Form::class)->name('noua');

        // Modul: Portal Client (aprobarea e necesara doar cand portalul e activ)
        Route::get('aprobare', \App\Livewire\Comenzi\Aprobare::class)
            ->middleware('modul:portal_client')
            ->name('aprobare');

        Route::get('{comanda}/editare', \App\Livewire\Comenzi\Form::class)->name('editare');
    });

    // Lista zilnica + harta — Faza 2.2 (core — fara middleware modul; harta e conditionata in Blade)
    Route::get('lista-zilnica', \App\Livewire\Comenzi\ListaZilnica::class)->name('lista-zilnica');

    // Modul: Comenzi Rapide — Faza 3.1
    Route::prefix('comenzi-rapide')->name('comenzi-rapide.')->middleware('modul:comenzi_rapide')->group(function () {
        Route::get('/', \App\Livewire\ComenziRapide\Index::class)->name('index');
        Route::get('noua', \App\Livewire\ComenziRapide\Form::class)->name('noua');
        Route::get('{rapida}/editare', \App\Livewire\ComenziRapide\Form::class)->name('editare');
    });

    // Modul: Probleme / Interventii — Faza 3.2
    Route::prefix('probleme')->name('probleme.')->middleware('modul:probleme')->group(function () {
        Route::get('/', \App\Livewire\Probleme\Index::class)->name('index');
        Route::get('noua', \App\Livewire\Probleme\Form::class)->name('noua');
        Route::get('{problema}/editare', \App\Livewire\Probleme\Form::class)->name('editare');
    });

    // Modul: Dozatoare — Faza 4.1
    Route::prefix('dozatoare')->name('dozatoare.')->middleware('modul:dozatoare')->group(function () {
        Route::get('/', \App\Livewire\Dozatoare\Index::class)->name('index');
    });

    // Modul: Stoc & Costuri — Faza 5.1
    Route::prefix('cheltuieli')->name('cheltuieli.')->middleware('modul:stoc')->group(function () {
        Route::get('/', \App\Livewire\Cheltuieli\Index::class)->name('index');
        Route::get('noua', \App\Livewire\Cheltuieli\Form::class)->name('noua');
        Route::get('{cheltuiala}/editare', \App\Livewire\Cheltuieli\Form::class)->name('editare');
    });

    // Modul: Rapoarte — Faza 5.2+
    Route::prefix('rapoarte')->name('rapoarte.')->middleware('modul:rapoarte')->group(function () {
        Route::get('stoc', \App\Livewire\Rapoarte\Stoc::class)->name('stoc');
        Route::get('cheltuieli-vanzari', \App\Livewire\Rapoarte\CheltuieliVanzari::class)->name('cheltuieli-vanzari');
        Route::get('abonamente-lipsa', \App\Livewire\Rapoarte\AbonamenteLipsa::class)->name('abonamente-lipsa');
        Route::get('financiar-bidoane', \App\Livewire\Rapoarte\FinanciarBidoane::class)->name('financiar-bidoane');
    });
});

// Rute exclusiv SuperAdmin (tip=100) — gestionare platforma
Route::middleware(['auth', 'rol:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('module', \App\Livewire\Superadmin\Module::class)->name('module');
});

// Rute sofer (tip=5)
Route::middleware(['auth', 'rol:sofer'])->prefix('sofer')->name('sofer.')->group(function () {
    Route::get('traseu', \App\Livewire\Sofer\Traseu::class)->name('traseu');
});

// Cron tasks publice — Faza 6.8
// Securizate prin token UUID (validat in CronController cu hash_equals).
// Throttle 60/min per IP — anti spam/DoS. Token gresit -> 404 anti-enumerare.
// URL pattern: GET /cron/{token}/{job}
// Modul:cron verificat in CronController (nu middleware — throttle trebuie sa ramana activ si cu modul off).
Route::middleware('throttle:60,1')->prefix('cron')->name('cron.')->group(function () {
    Route::get('{token}/igienizari-zilnice', [\App\Http\Controllers\CronController::class, 'igienizariZilnice'])
        ->name('igienizari-zilnice');
    Route::get('{token}/mentenanta-verifica', [\App\Http\Controllers\CronController::class, 'mentenantaVerifica'])
        ->name('mentenanta-verifica');
});

// Portal client (tip=3) — Faza 6.3
// Rute publice (activare cont prin token + cerere noua invitatie)
// Notă: activare/cere-invitatie raman accesibile chiar si cu modul_portal_client off
// (token-ul a fost deja trimis; mesajul de confirmare e inofensiv).
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('activare/{token}', \App\Livewire\Portal\Activare::class)->name('activare');
    Route::get('cere-invitatie', \App\Livewire\Portal\CereInvitatie::class)->name('cere-invitatie');
});

// Rute portal autentificate — Modul: Portal Client
Route::middleware(['auth', 'rol:client', 'modul:portal_client'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', fn () => redirect()->route('portal.comenzi.index'));

    Route::prefix('comenzi')->name('comenzi.')->group(function () {
        Route::get('/', \App\Livewire\Portal\Comenzi\Index::class)->name('index');
        Route::get('noua', \App\Livewire\Portal\Comenzi\Form::class)->name('noua');
    });

    Route::get('cont', \App\Livewire\Portal\Cont::class)->name('cont');
});

// Rute gestiune (tip=10)
Route::middleware(['auth', 'rol:gestiune'])->prefix('gestiune')->name('gestiune.')->group(function () {
    Route::get('comenzi', \App\Livewire\Gestiune\ListaComenzi::class)->name('comenzi');
});

// Profil — accesibil tuturor utilizatorilor autentificati
Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

require __DIR__.'/auth.php';
