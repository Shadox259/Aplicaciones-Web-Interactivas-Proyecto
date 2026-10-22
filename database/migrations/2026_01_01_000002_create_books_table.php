<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            // Autor (creador) del libro. Las cuentas no se borran, se desactivan: un libro siempre tiene creador.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('genre_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('synopsis');
            $table->string('language', 40)->default('Español');
            $table->unsignedSmallInteger('pages')->nullable();
            // Portada: ruta relativa a /public (ej. uploads/covers/abc.jpg). Null = portada generada.
            $table->string('cover_path')->nullable();
            // PDF del libro: ruta dentro de storage/app/private (nunca publico: se entrega por controlador).
            $table->string('pdf_path')->nullable();
            $table->string('pdf_original_name')->nullable();
            $table->unsignedBigInteger('pdf_size')->default(0);
            // draft | published
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
