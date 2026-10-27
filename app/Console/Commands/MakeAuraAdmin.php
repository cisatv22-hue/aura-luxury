<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class MakeAuraAdmin extends Command
{
    protected $signature = 'aura:make-admin {email : Correo de la cuenta del personal}';

    protected $description = 'Crea o promueve una cuenta del personal con acceso completo al panel AURA';

    public function handle(): int
    {
        $emailArgument = $this->argument('email');
        if (! is_string($emailArgument)) {
            $this->error('Escribe un correo válido.');

            return self::FAILURE;
        }
        $email = strtolower(trim($emailArgument));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Escribe un correo válido.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $user->exists ? $user->name : $this->ask('Nombre');

        if (! $user->exists) {
            $password = $this->secret('Contraseña temporal (mínimo 12 caracteres)');
            if (! is_string($password) || strlen($password) < 12) {
                $this->error('La contraseña temporal debe tener al menos 12 caracteres.');

                return self::FAILURE;
            }
            $user->password = Hash::make($password);
            $user->email_verified_at = Carbon::now();
        }

        $user->save();
        $user->syncRoles(['admin']);
        $this->info("{$email} ya tiene el rol admin. Configura y confirma 2FA antes de entrar a /admin.");

        return self::SUCCESS;
    }
}
