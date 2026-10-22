<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookRead;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_three_roles_books_with_pdfs_and_activity(): void
    {
        Storage::fake('local');

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());
        $this->assertGreaterThanOrEqual(2, User::where('role', User::ROLE_AUTHOR)->count());
        $this->assertGreaterThanOrEqual(2, User::where('role', User::ROLE_CUSTOMER)->count());

        foreach (config('wonderland.demo_accounts') as $account) {
            $user = User::where('email', $account['email'])->firstOrFail();
            $this->assertTrue(password_verify($account['password'], $user->password), "Contraseña de {$account['email']}");
            $this->assertTrue($user->is_active);
        }

        $published = Book::published()->get();
        $this->assertGreaterThanOrEqual(10, $published->count());

        foreach ($published as $book) {
            $this->assertTrue($book->hasPdf(), "{$book->title} debe tener PDF");
            Storage::disk('local')->assertExists($book->pdf_path);
            $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($book->pdf_path));
        }

        $this->assertTrue(Book::where('status', Book::STATUS_DRAFT)->exists());
        $this->assertGreaterThan(0, Review::count());
        $this->assertGreaterThan(0, BookRead::count());
        $this->assertGreaterThan(0, User::where('email', 'cliente@wonderland.test')->first()->library()->count());
    }

    public function test_seeding_twice_does_not_duplicate_data(): void
    {
        Storage::fake('local');

        $this->seed(DatabaseSeeder::class);
        $counts = [User::count(), Book::count(), Review::count(), BookRead::count()];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts, [User::count(), Book::count(), Review::count(), BookRead::count()]);
    }

    public function test_install_command_migrates_and_seeds(): void
    {
        Storage::fake('local');

        $this->artisan('wonderland:install')->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'admin@wonderland.test', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'autor@wonderland.test', 'role' => 'autor']);
        $this->assertDatabaseHas('users', ['email' => 'cliente@wonderland.test', 'role' => 'cliente']);
    }
}
