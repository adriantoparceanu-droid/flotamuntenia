<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Faza 6.9 — Configurare SMTP stocata in DB cu parola criptata.
 *
 * Cast `encrypted:string` pe `password` — la save, Eloquent cripteaza
 * automat folosind APP_KEY; la read, decripteaza. ATENTIE: rotatia APP_KEY
 * invalideaza parola — backup inainte de orice rotation.
 *
 * Regula: un singur rand `activ=true` la un moment dat. Helper-ul `activ()`
 * returneaza inregistrarea curenta sau null. UI-ul forteaza dezactivarea
 * celorlalte la activarea unuia (similar pattern facturare_setari).
 */
class SetariSmtp extends Model
{
    protected $table = 'setari_smtp';

    public const ENCRYPTION_TLS = 'tls';
    public const ENCRYPTION_SSL = 'ssl';
    public const ENCRYPTION_NONE = 'none';

    protected $fillable = [
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_email',
        'from_name',
        'activ',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted:string',
            'port' => 'integer',
            'activ' => 'boolean',
        ];
    }

    /**
     * Returneaza configurarea SMTP activa sau null daca niciuna.
     * Folosit de SmtpConfigService pentru a aplica config dinamic la trimitere.
     */
    public static function activ(): ?self
    {
        return static::where('activ', true)->first();
    }

    /**
     * Verifica daca toate cheile minime cerute sunt prezente.
     * Folosit de UI pentru badge „Configurat / Neconfigurat".
     */
    public function esteConfigurat(): bool
    {
        return ! empty($this->host)
            && ! empty($this->port)
            && ! empty($this->from_email)
            && ! empty($this->from_name);
    }
}
