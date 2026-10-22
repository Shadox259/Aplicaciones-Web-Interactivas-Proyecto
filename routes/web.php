<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\Panel\BookController as PanelBookController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\GenreController;
use App\Http\Controllers\Panel\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Library of Wonderland - rutas web
|--------------------------------------------------------------------------
| Plataforma GRATUITA. Roles: admin | autor (creador de libros) | cliente
|  - publico ........ inicio, catalogo, detalle de libro, autor, LECTURA y descarga del PDF
|  - auth ........... login / registro
|  - con sesion ..... mi biblioteca (guardar libros), resenas, perfil
|  - admin+autor .... /panel (libros)   |   solo admin: usuarios y generos
*/

// ---------------------------------------------------------------- Publico
Route::get('/', [HomeController::class, 'index'])->name('inicio');
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalogo');
Route::get('/libros/{book}', [BookController::class, 'show'])->name('libros.show');
Route::get('/autores/{author}', [AuthorController::class, 'show'])->name('autores.show');

// Lector de PDF: libre para libros publicados; los borradores solo los ve su autor o el admin (BookPolicy@read).
Route::get('/leer/{book}', [ReaderController::class, 'show'])->name('leer.show');
Route::get('/leer/{book}/pdf', [ReaderController::class, 'pdf'])->name('leer.pdf');
Route::get('/leer/{book}/descargar', [ReaderController::class, 'download'])->name('leer.descargar');

// ---------------------------------------------------------------- Autenticacion
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('registro');
    Route::post('/registro', [AuthController::class, 'register'])->name('registro.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------------------------------------------------------------- Cualquier usuario con sesion
Route::middleware('auth')->group(function () {
    Route::get('/mi-biblioteca', [LibraryController::class, 'index'])->name('biblioteca');
    Route::post('/libros/{book}/guardar', [LibraryController::class, 'store'])->name('biblioteca.guardar');
    Route::delete('/libros/{book}/guardar', [LibraryController::class, 'destroy'])->name('biblioteca.quitar');

    Route::post('/libros/{book}/resenas', [ReviewController::class, 'store'])->name('resenas.store');
    Route::delete('/resenas/{review}', [ReviewController::class, 'destroy'])->name('resenas.destroy');

    Route::get('/perfil', [ProfileController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('perfil.update');
    Route::put('/perfil/password', [ProfileController::class, 'updatePassword'])->name('perfil.password');
    // "Eliminar cuenta" = desactivarla (las cuentas no se borran: sus libros conservan al creador).
    Route::delete('/perfil', [ProfileController::class, 'destroy'])->name('perfil.destroy');
});

// ---------------------------------------------------------------- Panel (admin + autor)
Route::prefix('panel')->name('panel.')->middleware(['auth', 'role:admin,autor'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/libros', [PanelBookController::class, 'index'])->name('libros.index');
    Route::get('/libros/crear', [PanelBookController::class, 'create'])->name('libros.create');
    Route::post('/libros', [PanelBookController::class, 'store'])->name('libros.store');
    Route::get('/libros/{book}/editar', [PanelBookController::class, 'edit'])->name('libros.edit');
    Route::put('/libros/{book}', [PanelBookController::class, 'update'])->name('libros.update');
    Route::delete('/libros/{book}', [PanelBookController::class, 'destroy'])->name('libros.destroy');
    Route::patch('/libros/{book}/publicar', [PanelBookController::class, 'togglePublish'])->name('libros.publicar');

    // Solo administradores
    Route::middleware('role:admin')->group(function () {
        Route::patch('/libros/{book}/destacar', [PanelBookController::class, 'toggleFeatured'])->name('libros.destacar');

        Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
        Route::patch('/usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update');
        // "Eliminar usuario" = desactivar la cuenta (conserva al creador de sus libros).
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');

        Route::get('/generos', [GenreController::class, 'index'])->name('generos.index');
        Route::post('/generos', [GenreController::class, 'store'])->name('generos.store');
        Route::put('/generos/{genre}', [GenreController::class, 'update'])->name('generos.update');
        Route::delete('/generos/{genre}', [GenreController::class, 'destroy'])->name('generos.destroy');
    });
});
