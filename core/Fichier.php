<?php
// core/Fichier.php

class Fichier {
    public const TAILLE_MAX = 10 * 1024 * 1024;

    private const TYPES = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'mp4'  => 'video/mp4',
    ];

    public static function racine(): string {
        return dirname(__DIR__) . '/storage/';
    }

    public static function estPresent(array $fichier): bool {
        return isset($fichier['error']) && $fichier['error'] !== UPLOAD_ERR_NO_FILE;
    }

    public static function normaliserMultiple(array $fichiers): array {
        $liste = [];
        foreach ((array)($fichiers['name'] ?? []) as $i => $nom) {
            if (($fichiers['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $liste[] = [
                'name'     => $nom,
                'tmp_name' => $fichiers['tmp_name'][$i],
                'error'    => $fichiers['error'][$i],
                'size'     => $fichiers['size'][$i],
            ];
        }
        return $liste;
    }

    public static function racinePublique(): string {
        return dirname(__DIR__) . '/public/';
    }

    // Retourne le chemin relatif à storage/ (ex. "justificatifs/ab12....pdf") ou lève une exception.
    public static function enregistrer(array $fichier, string $dossier, array $extensions): string {
        return self::stocker($fichier, self::racine(), $dossier, $extensions);
    }

    // Même contrôle, mais rangé sous public/ (photos de profil) : chemin relatif à public/.
    public static function enregistrerPublic(array $fichier, string $dossier, array $extensions): string {
        return self::stocker($fichier, self::racinePublique(), $dossier, $extensions);
    }

    public static function supprimerPublic(?string $cheminRelatif): void {
        if (!$cheminRelatif) {
            return;
        }
        $racine = realpath(self::racinePublique() . 'assets/uploads');
        $chemin = realpath(self::racinePublique() . $cheminRelatif);
        if ($racine && $chemin && strpos($chemin, $racine) === 0 && is_file($chemin)) {
            unlink($chemin);
        }
    }

    private static function stocker(array $fichier, string $racine, string $dossier, array $extensions): string {
        if (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException("Le fichier n'a pas pu être reçu.");
        }
        if ($fichier['size'] > self::TAILLE_MAX) {
            throw new InvalidArgumentException("Le fichier dépasse 10 Mo.");
        }
        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $extensions, true) || !isset(self::TYPES[$extension])) {
            throw new InvalidArgumentException("Format non accepté (" . implode(', ', $extensions) . ").");
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($fichier['tmp_name']);
        $entete = (string)file_get_contents($fichier['tmp_name'], false, null, 0, 5);
        if ($mime !== self::TYPES[$extension] || ($extension === 'pdf' && $entete !== '%PDF-')) {
            throw new InvalidArgumentException("Le contenu du fichier ne correspond pas à son extension.");
        }
        $cible = $racine . $dossier;
        if (!is_dir($cible)) {
            mkdir($cible, 0755, true);
        }
        $nom = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($fichier['tmp_name'], $cible . '/' . $nom)) {
            throw new RuntimeException("Impossible d'enregistrer le fichier.");
        }
        return $dossier . '/' . $nom;
    }

    public static function envoyer(string $cheminRelatif, string $nomAffiche): never {
        $chemin = realpath(self::racine() . $cheminRelatif);
        if ($chemin === false || strpos($chemin, realpath(self::racine())) !== 0 || !is_file($chemin)) {
            http_response_code(404);
            exit("Fichier introuvable.");
        }
        $extension = strtolower(pathinfo($chemin, PATHINFO_EXTENSION));
        header('Content-Type: ' . (self::TYPES[$extension] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($chemin));
        header('Content-Disposition: inline; filename="' . rawurlencode($nomAffiche) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($chemin);
        exit();
    }
}
