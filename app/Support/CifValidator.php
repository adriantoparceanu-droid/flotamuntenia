<?php

namespace App\Support;

/**
 * Faza 6.6 — Validator CIF Romanesc cu checksum oficial.
 *
 * Algoritm: cheia 753217532 — fiecare cifra a CIF-ului (mai putin ultima)
 * inmultita cu cifra corespunzatoare din cheia (aliniate la dreapta), suma
 * inmultita cu 10, modulo 11. Daca rezultatul e 10, devine 0.
 * Trebuie sa fie egal cu ultima cifra (cifra de control).
 *
 * Acceptam optionalul prefix 'RO' (PJ inregistrate ca platitor TVA).
 *
 * Folosit inainte de apel ANAF — evita request-uri inutile cu CIF-uri syntactic
 * invalide (typo-uri operator).
 */
class CifValidator
{
    /**
     * Cheia oficiala pentru calcul checksum CIF (de la ANAF).
     */
    private const CHEIE = [7, 5, 3, 2, 1, 7, 5, 3, 2];

    /**
     * Verifica daca un CIF este valid (format + checksum).
     */
    public static function esteValid(?string $cif): bool
    {
        if ($cif === null || $cif === '') {
            return false;
        }

        $cif = self::normalizeaza($cif);

        // Format: doar cifre, lungime 2-10
        if (! preg_match('/^\d{2,10}$/', $cif)) {
            return false;
        }

        // Cifra de control = ultima
        $cifreControl = (int) substr($cif, -1);
        $cifre = substr($cif, 0, -1);

        // Aliniem cifrele la dreapta cheii — daca CIF-ul e mai scurt decat cheia,
        // padding cu zerouri la stanga (in mod logic — algoritmul cere alinierea
        // ultimei cifre din CIF cu ultima cifra din cheie).
        $cifre = str_pad($cifre, count(self::CHEIE), '0', STR_PAD_LEFT);

        $suma = 0;
        for ($i = 0; $i < count(self::CHEIE); $i++) {
            $suma += ((int) $cifre[$i]) * self::CHEIE[$i];
        }

        $rest = ($suma * 10) % 11;
        if ($rest === 10) {
            $rest = 0;
        }

        return $rest === $cifreControl;
    }

    /**
     * Returneaza forma normalizata a CIF-ului — fara prefix 'RO', fara spatii,
     * uppercase. Folosit ca input pentru API ANAF si ca cheie cache.
     */
    public static function normalizeaza(?string $cif): string
    {
        if ($cif === null) {
            return '';
        }

        $cif = trim($cif);
        $cif = preg_replace('/\s+/', '', $cif) ?? $cif;
        $cif = strtoupper($cif);

        // Strip prefix 'RO' daca exista
        if (str_starts_with($cif, 'RO')) {
            $cif = substr($cif, 2);
        }

        return $cif;
    }
}
