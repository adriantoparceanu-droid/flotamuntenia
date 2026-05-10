<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Creeaza un cont de administrator pentru productie.
 *
 * Rulare:
 *   php artisan app:create-admin
 *
 * Interactiv — cere nume, email, parola. Foloseste bcrypt (NU MD5).
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin
                            {--name= : Numele administratorului}
                            {--email= : Adresa de email}
                            {--password= : Parola (min 8 caractere); daca lipseste, se cere interactiv}';

    protected $description = 'Creeaza un cont de administrator (tip=1) pentru productie';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nume administrator');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Parola (min 8 caractere)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
            ],
            [
                'email.unique' => 'Exista deja un cont cu acest email.',
                'password.min' => 'Parola trebuie sa aiba minim 8 caractere.',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $err) {
                $this->error($err);
            }
            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'tip' => User::TIP_ADMIN,
            'confirmat' => true,
        ]);

        $this->info("Administrator creat: {$user->email} (id={$user->id})");
        return self::SUCCESS;
    }
}
