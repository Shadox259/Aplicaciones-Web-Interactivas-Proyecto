<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Book extends Model
{
    /** @use HasFactory<\Database\Factories\BookFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    /**
     * `user_id` (autor) se asigna con $user->books()->create([...]) y
     * `is_featured` se cambia de forma explicita (solo el admin).
     *
     * @var list<string>
     */
    protected $fillable = [
        'genre_id',
        'title',
        'slug',
        'synopsis',
        'language',
        'pages',
        'cover_path',
        'pdf_path',
        'pdf_original_name',
        'pdf_size',
        'status',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'pdf_size' => 'integer',
            'pages' => 'integer',
        ];
    }

    /** Las URLs usan el slug: /libros/el-jardin-de-los-relojes */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ------------------------------------------------------------------
    // Relaciones
    // ------------------------------------------------------------------

    /** Creador del libro. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Usuarios que guardaron este libro en su biblioteca. */
    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_user')->withTimestamps();
    }

    /** Lecturas en linea y descargas (columna `type`: read | download). */
    public function reads(): HasMany
    {
        return $this->hasMany(BookRead::class);
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /** Agrega reviews_avg_rating y reviews_count (evita consultas N+1). */
    public function scopeWithRating(Builder $query): Builder
    {
        return $query->withAvg('reviews', 'rating')->withCount('reviews');
    }

    /** Agrega reads_count (lecturas), downloads_count (descargas) y saves_count (guardados). */
    public function scopeWithStats(Builder $query): Builder
    {
        return $query
            ->withCount(['reads as reads_count' => fn (Builder $q) => $q->where('type', BookRead::TYPE_READ)])
            ->withCount(['reads as downloads_count' => fn (Builder $q) => $q->where('type', BookRead::TYPE_DOWNLOAD)])
            ->withCount('savedBy as saves_count');
    }

    /** Busca por titulo, sinopsis o nombre del autor. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        // whereLike es insensible a mayusculas en todos los motores (ILIKE en PostgreSQL).
        return $query->where(function (Builder $q) use ($like) {
            $q->whereLike('title', $like)
                ->orWhereLike('synopsis', $like)
                ->orWhereHas('author', fn (Builder $a) => $a->whereLike('name', $like));
        });
    }

    // ------------------------------------------------------------------
    // Ayudas
    // ------------------------------------------------------------------

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function hasPdf(): bool
    {
        return filled($this->pdf_path);
    }

    /** Publicado para todos; borrador solo para su autor y el admin. */
    public function isVisibleTo(?User $user): bool
    {
        if ($this->isPublished()) {
            return true;
        }

        return $user && ($user->isAdmin() || $user->id === $this->user_id);
    }

    /** URL publica de la portada subida, o null si usa la portada generada. */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => $this->cover_path ? asset($this->cover_path) : null);
    }

    /** Promedio de estrellas (0 si no hay resenas). */
    protected function rating(): Attribute
    {
        return Attribute::get(function () {
            $avg = array_key_exists('reviews_avg_rating', $this->attributes)
                ? $this->attributes['reviews_avg_rating']
                : $this->reviews()->avg('rating');

            return round((float) $avg, 1);
        });
    }

    /** Numero de resenas. */
    protected function reviewsTotal(): Attribute
    {
        return Attribute::get(function () {
            return array_key_exists('reviews_count', $this->attributes)
                ? (int) $this->attributes['reviews_count']
                : $this->reviews()->count();
        });
    }

    /** Lecturas en linea (usar scopeWithStats para evitar consultas extra). */
    protected function readsTotal(): Attribute
    {
        return Attribute::get(function () {
            return array_key_exists('reads_count', $this->attributes)
                ? (int) $this->attributes['reads_count']
                : $this->reads()->where('type', BookRead::TYPE_READ)->count();
        });
    }

    protected function downloadsTotal(): Attribute
    {
        return Attribute::get(function () {
            return array_key_exists('downloads_count', $this->attributes)
                ? (int) $this->attributes['downloads_count']
                : $this->reads()->where('type', BookRead::TYPE_DOWNLOAD)->count();
        });
    }

    protected function savesTotal(): Attribute
    {
        return Attribute::get(function () {
            return array_key_exists('saves_count', $this->attributes)
                ? (int) $this->attributes['saves_count']
                : $this->savedBy()->count();
        });
    }

    /** Tamano del PDF legible: "2.4 MB". */
    protected function pdfSizeLabel(): Attribute
    {
        return Attribute::get(fn () => format_bytes((int) $this->pdf_size));
    }

    /** Slug unico a partir del titulo (se conserva aunque despues cambie el titulo). */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'libro';
        $slug = $base;
        $i = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
