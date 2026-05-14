<?php

namespace App\Providers;

use App\Services\ModuleService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Directivă Blade pentru condiționare pe module.
        // Utilizare: @moduleActiv('portal_client') ... @endmoduleActiv
        // Acceptă și else: @moduleActiv('x') ... @else ... @endmoduleActiv
        Blade::if('moduleActiv', function (string $slug): bool {
            return ModuleService::isActive('modul_' . $slug);
        });
    }
}

