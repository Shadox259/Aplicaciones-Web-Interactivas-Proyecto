<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateAdmin extends Command
{
    protected $signature = 'wonderland:admin
                            {email : Correo del administrador}
                            {password? : Contraseña (mínimo 8 caracteres; se pide si se omite)}
                            {--name=Administrador : Nombre que se mostrará}';

    protected $description = 'Crea un administrador o restablece la contraseña (y reactiva) el de ese correo';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $password = $this->argument('password') ?? $this->secret('Contraseña (mínimo 8 caracteres)');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('El correo no es válido.');

            return self::FAILURE;
        }

        if (strlen((string) $password) < 8) {
            $this->components->error('La contraseña debe tener al menos 8 caracteres.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;

        if ($isNew) {
            $user->name = (string) $this->option('name');
        }

        $user->password = $password;
        $user->role = User::ROLE_ADMIN;
        $user->is_active = true;
        $user->email_verified_at ??= now();
        $user->save();

        $this->components->info($isNew ? "Administrador creado: {$email}" : "Administrador actualizado: {$email}");

        return self::SUCCESS;
    }
}
