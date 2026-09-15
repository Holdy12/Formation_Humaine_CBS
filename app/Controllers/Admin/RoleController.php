namespace App\Controllers\Admin;

use App\Models\Role;

class RoleController {
    public function index(): void {
        $roles = Role::tous();
        require __DIR__ . '/../../Views/admin/roles/index.php';
    }

    public function creer(): void {
        $message = null;
        $succes = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $libelle = trim($_POST['libelle'] ?? '');

            if ($libelle === '') {
                $message = "Le libellé du rôle ne peut pas être vide.";
            } else {
                try {
                    Role::creer($libelle);
                    $succes = true;
                    $message = "Le rôle a été créé avec succès.";
                } catch (\Exception $e) {
                    $message = "Erreur : ce rôle existe probablement déjà.";
                }
            }
        }

        require __DIR__ . '/../../Views/admin/roles/creer.php';
    }
}