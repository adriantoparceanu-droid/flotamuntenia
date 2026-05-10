<?php

namespace App\Services\Facturare;

use RuntimeException;

/**
 * Faza 6.1 — Exceptie ridicata de furnizorii de facturare la orice esec
 * (autentificare, validare payload, comunicare API). Mesajul e in romana,
 * destinat afisarii in UI.
 */
class FacturareException extends RuntimeException
{
}
