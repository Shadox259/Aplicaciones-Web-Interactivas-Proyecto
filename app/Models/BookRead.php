<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de actividad: una lectura en linea o una descarga de un libro.
 */
class BookRead extends Model
{
    public const TYPE_READ = 'read';

    public const TYPE_DOWNLOAD = 'download';

    public const UPDATED_AT = null;

    protected $fillable = ['book_id', 'user_id', 'type'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra la actividad de un lector. No cuenta la del propio autor del libro.
     */
    public static function record(Book $book, ?User $user, string $type = self::TYPE_READ): ?self
    {
        if ($user && $user->id === $book->user_id) {
            return null;
        }

        return static::create([
            'book_id' => $book->id,
            'user_id' => $user?->id,
            'type' => $type,
        ]);
    }
}
