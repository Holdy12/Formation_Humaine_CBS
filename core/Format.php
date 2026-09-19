<?php
// core/Format.php

class Format {
    public static function points(float $n, bool $signe = false): string {
        if (!$signe) {
            return number_format($n, 2, ',', ' ');
        }
        return ($n < 0 ? '−' : '+') . number_format(abs($n), 2, ',', ' ');
    }

    // Toujours signé, sans décimales inutiles : « +3 », « −0,25 » (site vitrine).
    public static function pointsCourts(float $n): string {
        $texte = rtrim(rtrim(number_format(abs($n), 2, ',', ' '), '0'), ',');
        return ($n < 0 ? '−' : '+') . $texte;
    }

    public static function date(?string $d): string {
        return $d ? date('d/m/Y', strtotime($d)) : '—';
    }

    public static function dateHeure(?string $d): string {
        return $d ? date('d/m/Y \à H\hi', strtotime($d)) : '—';
    }

    // Vrai pour une date AAAA-MM-JJ existante (le 30 février est refusé).
    public static function dateValide(string $d): bool {
        $date = DateTime::createFromFormat('!Y-m-d', $d);
        return $date !== false && $date->format('Y-m-d') === $d;
    }

    public static function heure(?string $h): string {
        return $h ? substr($h, 0, 5) : '—';
    }

    // Accord en genre à partir de PERSONNE.SEXE ('M' ou 'F').
    public static function genre(?string $sexe, string $masculin, string $feminin): string {
        return strtoupper((string)$sexe) === 'F' ? $feminin : $masculin;
    }
}
