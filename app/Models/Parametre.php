<?php
// app/Models/Parametre.php
require_once __DIR__ . '/../../config/database.php';

class Parametre {
    private static array $cache = [];

    public static function valeur(string $code, string $defaut): string {
        if (!array_key_exists($code, self::$cache)) {
            $stmt = Database::getConnection()->prepare("SELECT VALEUR FROM PARAMETRE_SYSTEME WHERE CODE_PARAMETRE = :code");
            $stmt->execute(['code' => $code]);
            $valeur = $stmt->fetchColumn();
            self::$cache[$code] = $valeur === false ? null : (string)$valeur;
        }
        return self::$cache[$code] ?? $defaut;
    }

    public static function viderCache(): void {
        self::$cache = [];
    }

    public static function nombre(string $code, float $defaut): float {
        return (float)self::valeur($code, (string)$defaut);
    }
}
