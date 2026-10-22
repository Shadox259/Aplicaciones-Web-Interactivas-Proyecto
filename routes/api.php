<?php

use App\Http\Controllers\Api\BookApiController;
use App\Http\Controllers\Api\GenreApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API JSON publica (solo lectura)  ->  prefijo automatico /api
|--------------------------------------------------------------------------
|  GET /api/v1/libros            ?q=  &genero=slug  &orden=recientes|valorados|populares|titulo  &por_pagina=
|  GET /api/v1/libros/{slug}
|  GET /api/v1/generos
*/
Route::prefix('v1')->name('api.')->middleware('throttle:60,1')->group(function () {
    Route::get('/libros', [BookApiController::class, 'index'])->name('libros.index');
    Route::get('/libros/{book}', [BookApiController::class, 'show'])->name('libros.show');
    Route::get('/generos', [GenreApiController::class, 'index'])->name('generos.index');
});
