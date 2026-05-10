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

    // Master data — Faza 1.2
    Route::prefix('setari')->name('setari.')->group(function () {
        Route::get('masini', \App\Livewire\Setari\Masini::class)->name('masini');
        Route::get('depozite', \App\Livewire\Setari\Depozite::class)->name('depozite');
        Route::get('catalog', \App\Livewire\Setari\Catalog::class)->name('catalog');
        Route::get('tva', \App\Livewire\Setari\Tva::class)->name('tva');
        Route::get('utilizatori', \App\Livewire\Setari\Utilizatori::class)->name('utilizatori');
        Route::get('facturare', \App\Livewire\Setari\Facturare::class)->name('facturare');
        // Template global contract de prestari servicii — Faza 6.2
        Route::get('contract-template', \App\Livewire\Setari\ContractTemplate::class)->name('contract-template');
        // Faza 6.5 — template-uri email + Faza 6.9 — SMTP
        Route::get('template-email', \App\Livewire\Setari\TemplateuriEmail::class)->name('template-email');
        Route::get('smtp', \App\Livewire\Setari\Smtp::class)->name('smtp');
        // Faza 6.8 — cron jobs (configurare URL-uri + token)
        Route::get('cron', \App\Livewire\Setari\Cron::class)->name('cron');
    });

    // Clienti — Faza 1.3
    // Atentie la ordinea rutelor: 'nou' inainte de '{client}' ca sa nu fie capturat ca parametru.
    Route::prefix('clienti')->name('clienti.')->group(function () {
        Route::get('/', \App\Livewire\Clienti\Index::class)->name('index');
        Route::get('nou', \App\Livewire\Clienti\Form::class)->name('nou');
        Route::get('{client}', \App\Livewire\Clienti\Detalii::class)->name('detalii');
        Route::get('{client}/editare', \App\Livewire\Clienti\Form::class)->name('editare');
        // Faza 6.2 — Descarcare PDF contract per client (DomPDF, inline render)
        Route::get('{client}/contract.pdf', \App\Http\Controllers\ContractPdfController::class)->name('contract-pdf');
        // Faza 6.7 — Descarcare document atasat (toate tipurile, disk privat)
        Route::get('documente/{document}/download', \App\Http\Controllers\DocumentDownloadController::class)
            ->name('document-download');
    });

    // Comenzi — Faza 2.1
    // Atentie la ordine: 'noua' inainte de '{comanda}' ca sa nu fie capturat ca parametru.
    Route::prefix('comenzi')->name('comenzi.')->group(function () {
        Route::get('/', \App\Livewire\Comenzi\Index::class)->name('index');
        Route::get('noua', \App\Livewire\Comenzi\Form::class)->name('noua');
        // Aprobare comenzi portal client — Faza 3.3
        Route::get('aprobare', \App\Livewire\Comenzi\Aprobare::class)->name('aprobare');
        Route::get('{comanda}/editare', \App\Livewire\Comenzi\Form::class)->name('editare');
    });

    // Lista zilnica + harta — Faza 2.2 (acum afiseaza ambele tipuri)
    Route::get('lista-zilnica', \App\Livewire\Comenzi\ListaZilnica::class)->name('lista-zilnica');

    // Comenzi rapide — Faza 3.1
    // Folosim {rapida} ca nume parametru pentru route-model binding pe ComandaRapida
    // (numele coincide cu argumentul `$rapida` din componenta).
    Route::prefix('comenzi-rapide')->name('comenzi-rapide.')->group(function () {
        Route::get('/', \App\Livewire\ComenziRapide\Index::class)->name('index');
        Route::get('noua', \App\Livewire\ComenziRapide\Form::class)->name('noua');
        Route::get('{rapida}/editare', \App\Livewire\ComenziRapide\Form::class)->name('editare');
    });

    // Probleme / Intervenții — Faza 3.2
    // {problema} pentru route-model binding pe Problema (argument $problema in componenta).
    Route::prefix('probleme')->name('probleme.')->group(function () {
        Route::get('/', \App\Livewire\Probleme\Index::class)->name('index');
        Route::get('noua', \App\Livewire\Probleme\Form::class)->name('noua');
        Route::get('{problema}/editare', \App\Livewire\Probleme\Form::class)->name('editare');
    });

    // Dozatoare cu bidoane — Faza 4.1
    // CRUD prin modal pe pagina /dozatoare; vizite + reminder pe aceeasi pagina.
    Route::prefix('dozatoare')->name('dozatoare.')->group(function () {
        Route::get('/', \App\Livewire\Dozatoare\Index::class)->name('index');
    });

    // Cheltuieli — Faza 5.1 (facturi achizitii + miscari stoc IN)
    Route::prefix('cheltuieli')->name('cheltuieli.')->group(function () {
        Route::get('/', \App\Livewire\Cheltuieli\Index::class)->name('index');
        Route::get('noua', \App\Livewire\Cheltuieli\Form::class)->name('noua');
        Route::get('{cheltuiala}/editare', \App\Livewire\Cheltuieli\Form::class)->name('editare');
    });

    // Rapoarte — Faza 5.2+ (stoc curent, cheltuieli vs vanzari, abonamente lipsa, financiar bidoane)
    Route::prefix('rapoarte')->name('rapoarte.')->group(function () {
        Route::get('stoc', \App\Livewire\Rapoarte\Stoc::class)->name('stoc');
        Route::get('cheltuieli-vanzari', \App\Livewire\Rapoarte\CheltuieliVanzari::class)->name('cheltuieli-vanzari');
        Route::get('abonamente-lipsa', \App\Livewire\Rapoarte\AbonamenteLipsa::class)->name('abonamente-lipsa');
        Route::get('financiar-bidoane', \App\Livewire\Rapoarte\FinanciarBidoane::class)->name('financiar-bidoane');
    });
});

// Rute sofer (tip=5)
Route::middleware(['auth', 'rol:sofer'])->prefix('sofer')->name('sofer.')->group(function () {
    Route::get('traseu', \App\Livewire\Sofer\Traseu::class)->name('traseu');
});

// Cron tasks publice — Faza 6.8
// Securizate prin token UUID (validat in CronController cu hash_equals).
// Throttle 60/min per IP — anti spam/DoS. Token gresit -> 404 anti-enumerare.
// URL pattern: GET /cron/{token}/{job}
Route::middleware('throttle:60,1')->prefix('cron')->name('cron.')->group(function () {
    Route::get('{token}/igienizari-zilnice', [\App\Http\Controllers\CronController::class, 'igienizariZilnice'])
        ->name('igienizari-zilnice');
    Route::get('{token}/mentenanta-verifica', [\App\Http\Controllers\CronController::class, 'mentenantaVerifica'])
        ->name('mentenanta-verifica');
});

// Portal client (tip=3) — Faza 6.3
// Rute publice (activare cont prin token + cerere noua invitatie)
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('activare/{token}', \App\Livewire\Portal\Activare::class)->name('activare');
    Route::get('cere-invitatie', \App\Livewire\Portal\CereInvitatie::class)->name('cere-invitatie');
});

// Rute portal autentificate
Route::middleware(['auth', 'rol:client'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', fn () => redirect()->route('portal.comenzi.index'));

    Route::prefix('comenzi')->name('comenzi.')->group(function () {
        Route::get('/', \App\Livewire\Portal\Comenzi\Index::class)->name('index');
        Route::get('noua', \App\Livewire\Portal\Comenzi\Form::class)->name('noua');
    });

    Route::get('cont', \App\Livewire\Portal\Cont::class)->name('cont');
});

// Rute gestiune (tip=10)
Route::middleware(['auth', 'rol:gestiune'])->prefix('gestiune')->name('gestiune.')->group(function () {
    Route::view('comenzi', 'gestiune.comenzi')->name('comenzi');
});

// Profil — accesibil tuturor utilizatorilor autentificati
Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

require __DIR__.'/auth.php';
