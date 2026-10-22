<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * "migrate:fresh" en SQLite ejecuta VACUUM, que no se puede lanzar dentro de la transaccion de RefreshDatabase;
 * por eso este caso vive aparte, sin ese trait (usa su propia base SQLite en memoria).
 */
class InstallFreshTest extends TestCase
{
    public function test_fresh_install_removes_orphaned_book_files_and_reseeds(): void
    {
        Storage::fake('local');

        $covers = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wonderland-covers-'.uniqid();
        config(['wonderland.covers_path' => $covers]);
        File::ensureDirectoryExists($covers);
        File::put($covers.'/orphan.jpg', 'x');
        File::put($covers.'/.gitkeep', '');
        Storage::disk('local')->put('books/orphan.pdf', '%PDF-1.4 orphan');

        $this->artisan('wonderland:install', ['--fresh' => true])->assertSuccessful();

        Storage::disk('local')->assertMissing('books/orphan.pdf');
        $this->assertFileDoesNotExist($covers.'/orphan.jpg');
        $this->assertFileExists($covers.'/.gitkeep');
        $this->assertNotEmpty(Storage::disk('local')->files('books'), 'el seeder vuelve a generar los PDF de demostración');
        $this->assertDatabaseHas('users', ['email' => 'admin@wonderland.test']);

        File::deleteDirectory($covers);
    }
}