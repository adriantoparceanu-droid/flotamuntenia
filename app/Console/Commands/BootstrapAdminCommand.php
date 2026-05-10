<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Creeaza primul admin la deploy automat (din .cpanel.yml).
 *
 * Citeste din .env:
 *   INITIAL_ADMIN_EMAIL=...
 *   INITIAL_ADMIN_PASSWORD=...
 *   INITIAL_ADMIN_NAME=... (optional, default "Administrator")
 *
 * Idempotent:
 *  - daca env vars nu sunt setate -> skip silent (success)
 *  - daca user-ul cu acel email exista deja -> skip (success)
 *  - daca validarea esueaza -> failure (deploy va trece, dar admin nu se creeaza)
 *
 * Dupa primul deploy reusit, sterge cele 3 env vars din .env si redeploy.
 */
class BootstrapAdminCommand extends Command
{
    protected $signature = 'app:bootstrap-admin';

    protected $description = 'Creeaza primul admin din variabile .env (INITIAL_ADMIN_*). Idempotent.';

    public function handle(): int
    {
        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');
        $name = env('INITIAL_ADMIN_NAME', 'Administrator');

        if (empty($email) || empty($password)) {
            $this->info('[bootstrap-admin] INITIAL_ADMIN_EMAIL / INITIAL_ADMIN_PASSWORD lipsesc — skip.');
            return self::SUCCESS;
        }

        if (User::where('email', $email)->exists()) {
            $this->info("[bootstrap-admin] User {$email} exista deja — skip.");
            return self::SUCCESS;
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:8',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $err) {
                $this->error("[bootstrap-admin] {$err}");
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

        $this->info("[bootstrap-admin] Admin creat: {$user->email} (id={$user->id})");
        $this->warn('[bootstrap-admin] Sterge INITIAL_ADMIN_* din .env dupa primul deploy.');
        return self::SUCCESS;
    }
}
