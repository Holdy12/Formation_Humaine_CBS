<?php
// config/database.php — connexion à la base. Les accès viennent du fichier .env (voir .env.example).
require_once __DIR__ . '/../core/Env.php';

date_default_timezone_set(Env::lire('APP_TIMEZONE', 'Africa/Ndjamena'));

define('DB_HOST', Env::lire('DB_HOST', 'localhost'));
define('DB_NAME', Env::lire('DB_NAME', 'formation_humaine_db'));
define('DB_USER', Env::lire('DB_USER'));
define('DB_PASS', Env::lire('DB_PASS'));

// Adresse à laquelle l'application est servie, sans barre finale (redirections).
define('BASE_URL', rtrim(Env::lire('BASE_URL', 'http://localhost:8000'), '/'));

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            if (DB_USER === '' && !Env::fichierPresent()) {
                die("Fichier .env introuvable : copiez .env.example en .env à la racine du projet et renseignez les accès MySQL.");
            }
            try {
                self::$instance = new PDO(
    "mysql:host=localhost;dbname=formation_humaine_db;charset=utf8mb4",
    "Maurer",
    "20031975", // 
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
                // Même fuseau horaire que PHP, pour que les délais soient calculés de la même façon des deux côtés.
                self::$instance->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
            } catch (PDOException $e) {
                die("Erreur de connexion à la base de données : " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
