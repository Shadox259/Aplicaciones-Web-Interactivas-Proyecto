<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_AUTHOR = 'autor';

    public const ROLE_CUSTOMER = 'cliente';

    public const ROLES = [
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_AUTHOR => 'Autor',
        self::ROLE_CUSTOMER => 'Cliente',
    ];

    /**
     * `role` e `is_active` NO son asignables en masa a proposito (evita escalada de privilegios).
     * Se asignan de forma explicita: $user->role = 'autor'; o con los estados del factory.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ------------------------------------------------------------------
    // Roles
    // ------------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAuthor(): bool
    {
        return $this->role === self::ROLE_AUTHOR;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    /** Puede entrar al panel y crear libros (admin o autor). */
    public function canPublish(): bool
    {
        return $this->isAdmin() || $this->isAuthor();
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    /** Pagina a la que se envia al usuario despues de iniciar sesion. */
    public function homeRoute(): string
    {
        return $this->canPublish() ? 'panel.dashboard' : 'inicio';
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    // ------------------------------------------------------------------
    // Relaciones
    // ------------------------------------------------------------------

    /** Libros que este usuario ha creado (como autor). */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /** "Mi biblioteca": libros que el usuario guardo. */
    public function library(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_user')->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(BookRead::class);
    }

    // ------------------------------------------------------------------
    // Biblioteca (cacheada por peticion, no por instancia)
    // ------------------------------------------------------------------

    /** @return list<int> */
    public function savedBookIds(): array
    {
        $bag = request()->attributes;
        $key = "wl.saved.{$this->id}";

        if (! $bag->has($key)) {
            $bag->set($key, array_map('intval', $this->library()->pluck('books.id')->all()));
        }

        return $bag->get($key);
    }

    public function hasSaved(Book|int $book): bool
    {
        return in_array($book instanceof Book ? $book->id : $book, $this->savedBookIds(), true);
    }

    /** Llamar despues de guardar/quitar libros dentro de la misma peticion. */
    public function flushCaches(): void
    {
        request()->attributes->remove("wl.saved.{$this->id}");
    }
}
