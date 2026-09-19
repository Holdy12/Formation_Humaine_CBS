<?php
// core/Erreur.php
require_once __DIR__ . '/Icone.php';

class Erreur {
    // Affiche une page d'erreur complète et arrête le script.
    public static function afficher(int $code, string $titre, string $message, string $lienTexte, string $lienUrl, string $icone = 'boussole'): never {
        http_response_code($code);
        require __DIR__ . '/../app/Views/erreur.php';
        exit();
    }

    public static function introuvable(): never {
        $connecte = !empty($_SESSION['user_id']);
        $role = strtoupper($_SESSION['user_role'] ?? '');
        // Un visiteur non connecté revient au site public (à la racine de l'application).
        $accueil = './';
        if ($connecte && $role === 'ETUDIANT') {
            $accueil = 'index.php?action=etudiant_dashboard';
        } elseif ($connecte) {
            $accueil = 'index.php?action=dashboard';
        }
        self::afficher(404, "Cette page n'existe pas",
            "L'adresse demandée ne correspond à aucune page du site ni de l'application. Elle a peut-être été déplacée, ou le lien est incomplet.",
            $connecte ? "Retour à l'accueil" : "Retour au site", $accueil, 'boussole');
    }

    public static function interdit(string $lienTexte, string $lienUrl): never {
        self::afficher(403, "Accès refusé",
            "Ce contenu ne vous appartient pas ou n'est pas accessible avec votre compte.",
            $lienTexte, $lienUrl, 'cadenas');
    }

    public static function technique(): never {
        self::afficher(500, "Un problème technique est survenu",
            "L'opération n'a pas pu aboutir. Réessayez dans un instant ; si le problème persiste, prévenez l'administration.",
            "Réessayer", htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php'), 'recharger');
    }
}
