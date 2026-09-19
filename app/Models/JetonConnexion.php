<?php
// app/Models/JetonConnexion.php — appareils mémorisés, « rester connecté » (table JETON_CONNEXION).
// Le cookie porte « sélecteur.validateur » : le sélecteur sert à retrouver la ligne, le validateur
// n'est stocké que haché. Chaque reprise de session remplace le validateur (rotation).
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Journal.php';

class JetonConnexion {
    public const DUREE_JOURS = 10;
    // Délai pendant lequel le validateur remplacé reste accepté : deux onglets rouverts ensemble
    // envoient le même cookie, et le second arrive après le renouvellement fait pour le premier.
    public const DELAI_CONCURRENCE_SECONDES = 30;

    private static function hacher(string $validateur): string {
        return hash('sha256', $validateur);
    }

    // Crée un jeton pour la personne et renvoie la valeur à déposer dans le cookie.
    public static function emettre(int $idPersonne): string {
        $db = Database::getConnection();
        $db->exec("DELETE FROM JETON_CONNEXION WHERE DATE_EXPIRATION < NOW()");
        $selecteur = bin2hex(random_bytes(12));
        $validateur = bin2hex(random_bytes(32));
        $stmt = $db->prepare("
            INSERT INTO JETON_CONNEXION (ID_PERSONNE, SELECTEUR, VALIDATEUR_HASH, DATE_EXPIRATION)
            VALUES (:id, :selecteur, :hash, DATE_ADD(NOW(), INTERVAL :jours DAY))
        ");
        $stmt->execute(['id' => $idPersonne, 'selecteur' => $selecteur, 'hash' => self::hacher($validateur), 'jours' => self::DUREE_JOURS]);
        return $selecteur . '.' . $validateur;
    }

    // Renvoie la ligne du jeton si le cookie est valide, sinon null. CONCURRENT vaut vrai quand le
    // cookie porte le validateur tout juste remplacé : la session est rouverte sans nouveau renouvellement.
    // Tout autre validateur pour un sélecteur connu signale un cookie volé puis rejoué :
    // tous les appareils de la personne sont alors déconnectés.
    public static function verifier(string $cookie): ?array {
        if (!preg_match('/^([a-f0-9]{24})\.([a-f0-9]{64})$/', $cookie, $m)) {
            return null;
        }
        $stmt = Database::getConnection()->prepare("
            SELECT *, TIMESTAMPDIFF(SECOND, DATE_UTILISATION, NOW()) AS SECONDES_DEPUIS_RENOUVELLEMENT
            FROM JETON_CONNEXION WHERE SELECTEUR = :selecteur
        ");
        $stmt->execute(['selecteur' => $m[1]]);
        $jeton = $stmt->fetch();
        if (!$jeton) {
            return null;
        }
        $hash = self::hacher($m[2]);
        $jeton['CONCURRENT'] = !hash_equals($jeton['VALIDATEUR_HASH'], $hash)
            && $jeton['VALIDATEUR_PRECEDENT'] !== null
            && hash_equals($jeton['VALIDATEUR_PRECEDENT'], $hash)
            && $jeton['SECONDES_DEPUIS_RENOUVELLEMENT'] !== null
            && (int)$jeton['SECONDES_DEPUIS_RENOUVELLEMENT'] >= 0
            && (int)$jeton['SECONDES_DEPUIS_RENOUVELLEMENT'] <= self::DELAI_CONCURRENCE_SECONDES;
        if (!$jeton['CONCURRENT'] && !hash_equals($jeton['VALIDATEUR_HASH'], $hash)) {
            self::revoquerTous((int)$jeton['ID_PERSONNE']);
            Journal::ecrire('Connexion', 'Jeton de reconnexion invalide, tous les appareils déconnectés', 'ECHEC', (int)$jeton['ID_PERSONNE']);
            return null;
        }
        if (strtotime($jeton['DATE_EXPIRATION']) < time()) {
            self::revoquer($m[1]);
            return null;
        }
        return $jeton;
    }

    // Nouveau validateur et nouvelle échéance : la durée court à partir de la dernière utilisation.
    public static function renouveler(array $jeton): string {
        $validateur = bin2hex(random_bytes(32));
        $stmt = Database::getConnection()->prepare("
            UPDATE JETON_CONNEXION
            SET VALIDATEUR_PRECEDENT = VALIDATEUR_HASH, VALIDATEUR_HASH = :hash,
                DATE_EXPIRATION = DATE_ADD(NOW(), INTERVAL :jours DAY), DATE_UTILISATION = NOW()
            WHERE ID_JETON = :id
        ");
        $stmt->execute(['hash' => self::hacher($validateur), 'jours' => self::DUREE_JOURS, 'id' => (int)$jeton['ID_JETON']]);
        return $jeton['SELECTEUR'] . '.' . $validateur;
    }

    public static function revoquer(string $selecteur): void {
        $stmt = Database::getConnection()->prepare("DELETE FROM JETON_CONNEXION WHERE SELECTEUR = :selecteur");
        $stmt->execute(['selecteur' => $selecteur]);
    }

    public static function revoquerTous(int $idPersonne): void {
        $stmt = Database::getConnection()->prepare("DELETE FROM JETON_CONNEXION WHERE ID_PERSONNE = :id");
        $stmt->execute(['id' => $idPersonne]);
    }
}
