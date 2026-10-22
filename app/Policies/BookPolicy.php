<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /** Editar, borrar, publicar: admin o el autor del libro. */
    public function manage(User $user, Book $book): bool
    {
        return $user->isAdmin() || $book->user_id === $user->id;
    }

    /**
     * Leer / descargar el PDF: cualquiera (incluso sin cuenta) si el libro esta publicado;
     * los borradores solo los ven su autor y el administrador.
     */
    public function read(?User $user, Book $book): bool
    {
        return $book->isVisibleTo($user);
    }
}
