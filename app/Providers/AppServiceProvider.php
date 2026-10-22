<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Compatibilidad con versiones antiguas de MySQL/MariaDB (indices utf8mb4).
        Schema::defaultStringLength(191);

        // $libros->links() usa la paginacion en espanol de resources/views/vendor/pagination
        Paginator::defaultView('vendor.pagination.wonderland');
    }
}
