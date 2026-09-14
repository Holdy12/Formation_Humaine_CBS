<?php
// core/Env.php — lecture du fichier .env à la racine du projet (voir .env.example).

class Env {
    private static ?array $valeurs = null;

    // Une variable d'environnement réelle a priorité sur le fichier ; sinon la valeur par défaut.
    public static function lire(string $cle, string $defaut = ''): string {
        $systeme = getenv($cle);
        if ($systeme !== false && $systeme !== '') {
            return $systeme;
        }
        return self::fichier()[$cle] ?? $defaut;
    }

    public static function fichierPresent(): bool {
        return is_readable(self::chemin());
    }

    private static function chemin(): string {
        return dirname(__DIR__) . '/.env';
    }

    private static function fichier(): array {
        if (self::$valeurs !== null) {
            return self::$valeurs;
        }
        self::$valeurs = [];
        if (!self::fichierPresent()) {
            return self::$valeurs;
        }
        foreach (file(self::chemin(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ligne) {
            $ligne = trim($ligne);
            if ($ligne === '' || $ligne[0] === '#' || !str_contains($ligne, '=')) {
                continue;
            }
            [$cle, $valeur] = explode('=', $ligne, 2);
            $valeur = trim($valeur);
            if (strlen($valeur) >= 2 && ($valeur[0] === '"' || $valeur[0] === "'") && $valeur[-1] === $valeur[0]) {
                $valeur = substr($valeur, 1, -1);
            }
            self::$valeurs[trim($cle)] = $valeur;
        }
        return self::$valeurs;
    }
}
