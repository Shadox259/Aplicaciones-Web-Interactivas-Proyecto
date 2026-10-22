<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\BookFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PDO;
use PDOException;
use Symfony\Component\Process\Process;

class InstallWonderland extends Command
{
    protected $signature = 'wonderland:install
                            {--fresh : Borra TODAS las tablas y las crea de nuevo (se pierden los datos)}
                            {--no-seed : No cargar los datos de demostracion}
                            {--wait=0 : Segundos que espera a que la base de datos este disponible (Docker usa 120)}';

    protected $description = 'Crea la base de datos (si no existe), ejecuta las migraciones y carga los datos de demostracion';

    public function handle(): int
    {
        $this->components->info('Library of Wonderland · instalación');

        if ($this->ensureEnvFile()) {
            return $this->continueInNewProcess();
        }

        $this->preflight();

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (! $this->ensureDatabase($config, (int) $this->option('wait'))) {
            return self::FAILURE;
        }

        $this->ensureDirectories();

        if ($this->option('fresh')) {
            $this->call('migrate:fresh', ['--force' => true]);
            $this->purgeBookFiles();
        } else {
            $this->call('migrate', ['--force' => true]);
        }

        if (! $this->option('no-seed') && ($this->option('fresh') || User::query()->doesntExist())) {
            $this->components->task('Cargando datos de demostración (usuarios, libros con PDF, biblioteca, reseñas y actividad)', function () {
                $this->callSilent('db:seed', ['--force' => true]);
            });
        } elseif (! $this->option('no-seed')) {
            $this->components->info('La base ya tiene datos: no se cargó la demostración (usa --fresh para reiniciarla).');
        }

        $this->printSummary();

        return self::SUCCESS;
    }

    /** Con --fresh se vacia la base, asi que tambien se borran los PDF y portadas para no dejar archivos huerfanos. */
    private function purgeBookFiles(): void
    {
        $disk = Storage::disk('local');
        $disk->deleteDirectory(BookFiles::PDF_DIR);
        $disk->makeDirectory(BookFiles::PDF_DIR);

        $covers = app(BookFiles::class)->coversPath();

        if (File::isDirectory($covers)) {
            foreach (File::files($covers, true) as $file) {
                if (! in_array($file->getFilename(), ['.gitkeep', '.htaccess'], true)) {
                    File::delete($file->getPathname());
                }
            }
        }
    }

    /** Devuelve true si acaba de crear el .env (la configuracion ya cargada quedo obsoleta). */
    private function ensureEnvFile(): bool
    {
        if (app()->runningUnitTests()) {
            return false;
        }

        $created = false;

        if (! File::exists(base_path('.env')) && File::exists(base_path('.env.example'))) {
            File::copy(base_path('.env.example'), base_path('.env'));
            $this->components->warn('Se creó .env a partir de .env.example.');
            $created = true;
        }

        if (blank(config('app.key'))) {
            $this->callSilent('key:generate', ['--force' => true]);
            $this->components->info('Se generó la clave de la aplicación (APP_KEY) en el archivo .env.');
        }

        return $created;
    }

    /** Laravel ya leyo la configuracion antes del .env nuevo: se repite el comando en un proceso limpio. */
    private function continueInNewProcess(): int
    {
        $this->components->info('Continúo en un proceso nuevo para cargar la configuración del .env recién creado...');

        $command = [PHP_BINARY, base_path('artisan'), 'wonderland:install', '--no-interaction', '--wait='.(int) $this->option('wait')];

        foreach (['fresh', 'no-seed'] as $flag) {
            if ($this->option($flag)) {
                $command[] = '--'.$flag;
            }
        }

        $process = new Process($command, base_path());
        $process->setTimeout(null);
        $process->run(fn ($type, $buffer) => $this->output->write($buffer));

        return $process->getExitCode() ?? self::FAILURE;
    }

    /** Avisos utiles (no detienen la instalacion). */
    private function preflight(): void
    {
        $driverExtension = match (config('database.connections.'.config('database.default').'.driver')) {
            'pgsql' => 'pdo_pgsql',
            'sqlite' => 'pdo_sqlite',
            default => 'pdo_mysql',
        };

        $missing = array_filter([$driverExtension, 'mbstring', 'fileinfo', 'openssl', 'ctype', 'tokenizer', 'xml'], fn ($ext) => ! extension_loaded($ext));

        if ($missing) {
            $this->components->warn('Faltan extensiones de PHP: '.implode(', ', $missing).'. En XAMPP actívalas en php.ini (quita el ";" de extension=...) y reinicia Apache.');
        }

        $upload = $this->toBytes(ini_get('upload_max_filesize'));
        $post = $this->toBytes(ini_get('post_max_size'));
        $needed = (int) config('wonderland.max_pdf_kb') * 1024;

        if (($upload > 0 && $upload < $needed) || ($post > 0 && $post < $needed)) {
            $this->components->warn(sprintf(
                'PHP limita las subidas a %s (upload_max_filesize) / %s (post_max_size). Para PDFs de hasta %s sube ambos valores en php.ini.',
                ini_get('upload_max_filesize'), ini_get('post_max_size'), format_bytes($needed)
            ));
        }
    }

    private function ensureDatabase(array $config, int $wait): bool
    {
        return match ($config['driver']) {
            'mysql', 'mariadb' => $this->ensureMysql($config, $wait),
            'pgsql' => $this->ensurePgsql($config, $wait),
            'sqlite' => $this->ensureSqlite($config),
            default => true,
        };
    }

    private function ensureSqlite(array $config): bool
    {
        if ($config['database'] !== ':memory:' && ! File::exists($config['database'])) {
            File::ensureDirectoryExists(dirname($config['database']));
            File::put($config['database'], '');
        }

        $this->components->twoColumnDetail('Base de datos', '<fg=green>SQLite</> '.$config['database']);

        return true;
    }

    private function ensureMysql(array $config, int $wait): bool
    {
        $host = $config['host'];
        $port = $config['port'] ?? 3306;
        $database = $config['database'];
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5];
        $deadline = time() + max(0, $wait);
        $error = null;

        do {
            // 1) La base ya existe y hay acceso: listo.
            try {
                new PDO("mysql:host={$host};port={$port};dbname={$database}", $config['username'], $config['password'] ?? '', $options);
                $this->components->twoColumnDetail('Base de datos', "<fg=green>{$database}</> en {$host}:{$port} (MySQL/MariaDB)");

                return true;
            } catch (PDOException $e) {
                $error = $e;
                $code = $this->driverCode($e);
            }

            // 2) No existe: conectar sin base y crearla.
            if ($code === 1049) {
                try {
                    $pdo = new PDO("mysql:host={$host};port={$port}", $config['username'], $config['password'] ?? '', $options);
                    $name = str_replace('`', '``', $database);
                    $pdo->exec(sprintf(
                        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s',
                        $name, $config['charset'] ?? 'utf8mb4', $config['collation'] ?? 'utf8mb4_unicode_ci'
                    ));
                    $this->components->twoColumnDetail('Base de datos', "<fg=green>{$database}</> creada en {$host}:{$port}");

                    return true;
                } catch (PDOException $e) {
                    $this->components->error("No se pudo crear la base de datos '{$database}': ".$e->getMessage());

                    return false;
                }
            }

            if ($code === 1045) {
                break; // credenciales incorrectas: reintentar no sirve
            }

            if (time() < $deadline) {
                $this->components->twoColumnDetail('Esperando a MySQL...', "{$host}:{$port}");
                sleep(2);
            }
        } while (time() < $deadline);

        $this->components->error("No se pudo conectar a MySQL en {$host}:{$port} con el usuario '{$config['username']}'.");
        $this->line('  · En XAMPP: abre el Panel de Control y pulsa <options=bold>Start</> en la fila de MySQL.');
        $this->line('  · Revisa DB_HOST, DB_PORT, DB_USERNAME y DB_PASSWORD en el archivo .env (XAMPP: root sin contraseña).');
        $this->line('  · Detalle: '.$error?->getMessage());

        return false;
    }

    private function ensurePgsql(array $config, int $wait): bool
    {
        if (! extension_loaded('pdo_pgsql')) {
            $this->components->error('Falta la extensión pdo_pgsql de PHP. En XAMPP quita el ";" de extension=pdo_pgsql y extension=pgsql en php.ini y reinicia Apache.');

            return false;
        }

        $host = $config['host'];
        $port = $config['port'] ?? 5432;
        $database = $config['database'];
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5];
        $deadline = time() + max(0, $wait);
        $error = null;

        do {
            try {
                new PDO("pgsql:host={$host};port={$port};dbname={$database}", $config['username'], $config['password'] ?? '', $options);
                $this->components->twoColumnDetail('Base de datos', "<fg=green>{$database}</> en {$host}:{$port} (PostgreSQL)");

                return true;
            } catch (PDOException $e) {
                $error = $e;
                $message = $e->getMessage();
            }

            if (str_contains($message, 'does not exist') && str_contains($message, 'database')) {
                try {
                    $pdo = new PDO("pgsql:host={$host};port={$port};dbname=postgres", $config['username'], $config['password'] ?? '', $options);
                    $exists = $pdo->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
                    $exists->execute([$database]);

                    if (! $exists->fetchColumn()) {
                        $pdo->exec('CREATE DATABASE "'.str_replace('"', '""', $database).'" ENCODING \'UTF8\'');
                    }

                    $this->components->twoColumnDetail('Base de datos', "<fg=green>{$database}</> creada en {$host}:{$port} (PostgreSQL)");

                    return true;
                } catch (PDOException $e) {
                    $this->components->error("No se pudo crear la base de datos '{$database}': ".$e->getMessage());

                    return false;
                }
            }

            if (str_contains($message, 'password authentication failed')) {
                break;
            }

            if (time() < $deadline) {
                $this->components->twoColumnDetail('Esperando a PostgreSQL...', "{$host}:{$port}");
                sleep(2);
            }
        } while (time() < $deadline);

        $this->components->error("No se pudo conectar a PostgreSQL en {$host}:{$port} con el usuario '{$config['username']}'.");
        $this->line('  · Revisa DB_HOST, DB_PORT, DB_USERNAME y DB_PASSWORD en el archivo .env.');
        $this->line('  · Detalle: '.$error?->getMessage());

        return false;
    }

    private function driverCode(PDOException $e): ?int
    {
        if (preg_match('/\[(\d{4})\]/', $e->getMessage(), $matches)) {
            return (int) $matches[1];
        }

        return isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : null;
    }

    private function ensureDirectories(): void
    {
        foreach ([
            public_path('uploads/covers'),
            storage_path('app/private/books'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ] as $directory) {
            File::ensureDirectoryExists($directory);
        }
    }

    private function printSummary(): void
    {
        $this->newLine();
        $this->components->info('Instalación terminada.');

        if (config('wonderland.show_demo_accounts') || $this->option('fresh') || ! $this->option('no-seed')) {
            $this->line('  <options=bold>Cuentas de demostración</>');
            $this->table(
                ['Rol', 'Correo', 'Contraseña'],
                collect(config('wonderland.demo_accounts'))->map(fn ($a) => [$a['role'], $a['email'], $a['password']])->all()
            );
        }

        $this->line('  <options=bold>Abre la aplicación</>');
        $this->line('  · Docker:         <fg=cyan>http://localhost:8080</>');
        $this->line('  · Sin Apache:     <fg=cyan>php artisan serve</>  →  http://127.0.0.1:8000');
        $this->line('  · XAMPP (Apache): <fg=cyan>http://localhost/'.basename(base_path()).'/public</>');
        $this->newLine();
    }

    private function toBytes(string|false $value): int
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
