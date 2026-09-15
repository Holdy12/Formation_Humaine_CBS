namespace App\Models;

use PDO;
use App\Services\Database;

class Role {
    public static function creer(string $libelle): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO ROLE (LIBELLE) VALUES (:libelle)");
        return $stmt->execute(['libelle' => $libelle]);
    }

    public static function tous(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM ROLE ORDER BY ID_ROLE ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}