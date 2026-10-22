<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookRead;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->isAdmin()
            ? $this->adminDashboard($user)
            : $this->authorDashboard($user);
    }

    private function adminDashboard(User $user): View
    {
        return view('panel.dashboard', [
            'user' => $user,
            'mode' => 'admin',
            'stats' => [
                'users_total' => User::query()->count(),
                'users_admins' => User::query()->where('role', User::ROLE_ADMIN)->count(),
                'users_authors' => User::query()->where('role', User::ROLE_AUTHOR)->count(),
                'users_customers' => User::query()->where('role', User::ROLE_CUSTOMER)->count(),
                ...$this->buildActivityStats(),
            ],
            'readsChart' => $this->buildReadsChart(),
            'topBooks' => $this->topBooksQuery()->take(5)->get(),
            'latestReviews' => $this->latestReviewsQuery()->take(6)->get(),
            'recentUsers' => User::query()
                ->withCount('books')
                ->latest()
                ->take(6)
                ->get(),
        ]);
    }

    private function authorDashboard(User $user): View
    {
        return view('panel.dashboard', [
            'user' => $user,
            'mode' => 'author',
            'stats' => $this->buildActivityStats($user),
            'readsChart' => $this->buildReadsChart($user),
            'topBooks' => $this->topBooksQuery($user)->take(5)->get(),
            'latestReviews' => $this->latestReviewsQuery($user)->take(6)->get(),
            'recentUsers' => collect(),
        ]);
    }

    /**
     * @return array<string, int|float>
     */
    private function buildActivityStats(?User $user = null): array
    {
        $booksQuery = Book::query()->when($user, fn (Builder $query) => $query->where('user_id', $user->id));
        $readsQuery = BookRead::query()->when($user, function (Builder $query) use ($user) {
            $query->whereHas('book', fn (Builder $bookQuery) => $bookQuery->where('user_id', $user->id));
        });
        $reviewsQuery = Review::query()->when($user, function (Builder $query) use ($user) {
            $query->whereHas('book', fn (Builder $bookQuery) => $bookQuery->where('user_id', $user->id));
        });

        $savesCount = DB::table('book_user')
            ->join('books', 'books.id', '=', 'book_user.book_id')
            ->when($user, fn ($query) => $query->where('books.user_id', $user->id))
            ->count();

        return [
            'published_books' => (clone $booksQuery)->published()->count(),
            'draft_books' => (clone $booksQuery)->where('status', Book::STATUS_DRAFT)->count(),
            'reads_total' => (clone $readsQuery)->where('type', BookRead::TYPE_READ)->count(),
            'reads_last_7' => (clone $readsQuery)
                ->where('type', BookRead::TYPE_READ)
                ->whereBetween('created_at', [now()->startOfDay()->subDays(6), now()->endOfDay()])
                ->count(),
            'downloads_total' => (clone $readsQuery)->where('type', BookRead::TYPE_DOWNLOAD)->count(),
            'saves_total' => $savesCount,
            'reviews_total' => (clone $reviewsQuery)->count(),
            'reviews_average' => round((float) ((clone $reviewsQuery)->avg('rating') ?? 0), 1),
        ];
    }

    /**
     * @return array<int, array{date:string,label:string,short_label:string,reads:int,height:float}>
     */
    private function buildReadsChart(?User $user = null): array
    {
        $from = now()->startOfDay()->subDays(6);
        $to = now()->endOfDay();

        $grouped = BookRead::query()
            ->where('type', BookRead::TYPE_READ)
            ->when($user, function (Builder $query) use ($user) {
                $query->whereHas('book', fn (Builder $bookQuery) => $bookQuery->where('user_id', $user->id));
            })
            ->whereBetween('created_at', [$from, $to])
            ->get(['created_at'])
            ->groupBy(fn (BookRead $read) => $read->created_at->toDateString());

        $rows = collect(range(6, 0))
            ->map(function (int $offset) use ($grouped) {
                $date = Carbon::today()->subDays($offset);
                $reads = $grouped->get($date->toDateString(), collect())->count();

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->translatedFormat('d \d\e M'),
                    'short_label' => mb_strtoupper($date->translatedFormat('D')),
                    'reads' => $reads,
                ];
            });

        $maxReads = max(1, (int) $rows->max('reads'));

        return $rows
            ->map(fn (array $row) => [
                ...$row,
                'height' => $row['reads'] > 0 ? round($row['reads'] / $maxReads * 100, 2) : 0,
            ])
            ->all();
    }

    private function topBooksQuery(?User $user = null): Builder
    {
        return Book::query()
            ->when($user, fn (Builder $query) => $query->where('user_id', $user->id))
            ->with(['author:id,name'])
            ->withRating()
            ->withStats()
            ->orderByDesc('reads_count')
            ->orderByDesc('downloads_count')
            ->orderByDesc('saves_count');
    }

    private function latestReviewsQuery(?User $user = null): Builder
    {
        return Review::query()
            ->with([
                'user:id,name',
                'book:id,title,slug,user_id',
            ])
            ->when($user, function (Builder $query) use ($user) {
                $query->whereHas('book', fn (Builder $bookQuery) => $bookQuery->where('user_id', $user->id));
            })
            ->latest();
    }
}
