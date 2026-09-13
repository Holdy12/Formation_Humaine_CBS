<?php
// config/database.php

date_default_timezone_set('Africa/Ndjamena');

define('DB_HOST', 'localhost');
define('DB_NAME', 'formation_humaine_db');
define('DB_USER', 'Maurer');
define('DB_PASS', '20031975'); // Mets ton mot de passe MySQL s'il y en a un

// Définition de l'URL de base pour les redirections propres
define('BASE_URL', 'http://localhost:8000');

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
                self::$instance->exec("SET time_zone = '+01:00'");
            } catch (PDOException $e) {
                die("Erreur de connexion à la base de données : " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}