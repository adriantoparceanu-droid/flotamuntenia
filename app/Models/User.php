<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const TIP_ADMIN = 1;
    public const TIP_CLIENT = 3;
    public const TIP_SOFER = 5;
    public const TIP_GESTIUNE = 10;
    public const TIP_SUPERADMIN = 100;

    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'tip',
        'confirmat',
        'id_client',
        'id_masina',
        'activation_token',
        'activation_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tip' => 'integer',
            'confirmat' => 'boolean',
            'activation_expires_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->tip === self::TIP_ADMIN || $this->tip === self::TIP_SUPERADMIN;
    }

    public function isClient(): bool
    {
        return $this->tip === self::TIP_CLIENT;
    }

    public function isSofer(): bool
    {
        return $this->tip === self::TIP_SOFER;
    }

    public function isGestiune(): bool
    {
        return $this->tip === self::TIP_GESTIUNE;
    }

    public function isSuperadmin(): bool
    {
        return $this->tip === self::TIP_SUPERADMIN;
    }

    public function homeRoute(): string
    {
        return match ($this->tip) {
            self::TIP_SOFER => 'sofer.traseu',
            self::TIP_CLIENT => 'portal.comenzi.index',
            self::TIP_GESTIUNE => 'gestiune.comenzi',
            default => 'dashboard',
        };
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'id_client');
    }

    public function masina(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'id_masina');
    }

    /**
     * Cauta un user pentru autentificare dupa email sau username.
     * Daca identifierul contine `@` se considera email; altfel username.
     * Returneaza null daca nu exista — apelul de autentificare ramane uniform
     * (nu trebuie sa stie ce coloana s-a folosit).
     */
    public static function findForLogin(string $identifier): ?self
    {
        $coloana = str_contains($identifier, '@') ? 'email' : 'username';
        return static::where($coloana, $identifier)->first();
    }
}
