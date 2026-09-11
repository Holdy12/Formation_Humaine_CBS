<?php
// app/Controllers/EtudiantController.php
require_once __DIR__ . '/../../config/database.php';

class EtudiantController {
    
    public function listerEtudiants(): array {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT p.*, e.ID_PROMO, pr.CODE_PROMO,
                       (SELECT MAX(j.DATE_CONNEXION) FROM JOURNAL_CONNEXION j WHERE j.ID_PERSONNE = p.ID_PERSONNE) AS DERNIERE_CONNEXION
                FROM PERSONNE p 
                JOIN ETUDIANT e ON p.ID_PERSONNE = e.ID_PERSONNE 
                JOIN PROMOTION pr ON e.ID_PROMO = pr.ID_PROMO 
                ORDER BY p.NOM ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function ajouterEtudiant(array $data): bool {
        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $anneeCourante = date('Y');
            $stmt = $db->query("SELECT COUNT(*) FROM PERSONNE");
            $count = $stmt->fetchColumn() + 1;
            $matricule = sprintf("CBS%s-%04d", $anneeCourante, $count);

            $plainPassword = bin2hex(random_bytes(4)); 
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

            $stmtRole = $db->prepare("SELECT ID_ROLE FROM ROLE WHERE CODE_ROLE = 'ETUDIANT' LIMIT 1");
            $stmtRole->execute();
            $roleEtudiant = $stmtRole->fetch(PDO::FETCH_ASSOC);
            
            if (!$roleEtudiant) {
                throw new Exception("Le rôle ETUDIANT n'existe pas dans la base de données.");
            }

            $stmtPersonne = $db->prepare("
                INSERT INTO PERSONNE (ID_ROLE, NOM, PRENOM, EMAIL, MOT_DE_PASSE, TELEPHONE, SEXE, DATE_NAISSANCE, ADRESSE, PHOTO, STATUT_COMPTE, MATRICULE) 
                VALUES (:id_role, :nom, :prenom, :email, :password, :telephone, :sexe, :date_naissance, :adresse, :photo, 'ACTIF', :matricule)
            ");
            
            $stmtPersonne->execute([
                'id_role' => $roleEtudiant['ID_ROLE'],
                'nom' => trim($data['nom']),
                'prenom' => trim($data['prenom']),
                'email' => trim($data['email']),
                'password' => $hashedPassword,
                'telephone' => trim($data['telephone']),
                'sexe' => $data['sexe'],
                'date_naissance' => $data['date_naissance'],
                'adresse' => $data['adresse'] ?? null,
                'photo' => $data['photo'] ?? null,
                'matricule' => $matricule
            ]);

            $idPersonne = $db->lastInsertId();

            $stmtEtudiant = $db->prepare("
                INSERT INTO ETUDIANT (ID_PERSONNE, ID_PROMO, ID_CLUB) 
                VALUES (:id_personne, :id_promo, :id_club)
            ");
            
            $stmtEtudiant->execute([
                'id_personne' => $idPersonne,
                'id_promo' => $data['id_promo'],
                'id_club' => !empty($data['id_club']) ? $data['id_club'] : null
            ]);

            $db->commit();
            $_SESSION['success_message'] = "Étudiant ajouté avec succès. Matricule : $matricule | Mot de passe temporaire : $plainPassword";
            return true;

        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['error_message'] = "Erreur lors de l'enregistrement : " . $e->getMessage();
            return false;
        }
    }

    public function supprimerEtudiant(int $idPersonne): bool {
        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $stmtPhoto = $db->prepare("SELECT PHOTO FROM PERSONNE WHERE ID_PERSONNE = :id");
            $stmtPhoto->execute(['id' => $idPersonne]);
            $personne = $stmtPhoto->fetch(PDO::FETCH_ASSOC);

            if ($personne && !empty($personne['PHOTO'])) {
                $photoPath = __DIR__ . '/../../' . $personne['PHOTO'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }

            $stmtEtudiant = $db->prepare("DELETE FROM ETUDIANT WHERE ID_PERSONNE = :id");
            $stmtEtudiant->execute(['id' => $idPersonne]);

            $stmtPersonne = $db->prepare("DELETE FROM PERSONNE WHERE ID_PERSONNE = :id");
            $stmtPersonne->execute(['id' => $idPersonne]);

            $db->commit();
            $_SESSION['success_message'] = "Étudiant supprimé avec succès.";
            return true;

        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['error_message'] = "Erreur lors de la suppression : " . $e->getMessage();
            return false;
        }
    }

    public function recupererEtudiant(int $idPersonne): ?array {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT p.*, e.ID_PROMO, e.ID_CLUB 
                FROM PERSONNE p 
                JOIN ETUDIANT e ON p.ID_PERSONNE = e.ID_PERSONNE 
                WHERE p.ID_PERSONNE = :id
            ");
            $stmt->execute(['id' => $idPersonne]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function modifierEtudiant(int $idPersonne, array $data): bool {
        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $stmtPersonne = $db->prepare("
                UPDATE PERSONNE 
                SET NOM = :nom, PRENOM = :prenom, EMAIL = :email, TELEPHONE = :telephone, 
                    SEXE = :sexe, DATE_NAISSANCE = :date_naissance, ADRESSE = :adresse" . 
                    (!empty($data['photo']) ? ", PHOTO = :photo" : "") . "
                WHERE ID_PERSONNE = :id
            ");
            
            $params = [
                'id' => $idPersonne,
                'nom' => trim($data['nom']),
                'prenom' => trim($data['prenom']),
                'email' => trim($data['email']),
                'telephone' => trim($data['telephone']),
                'sexe' => $data['sexe'],
                'date_naissance' => $data['date_naissance'],
                'adresse' => $data['adresse'] ?? null
            ];
            
            if (!empty($data['photo'])) {
                $params['photo'] = $data['photo'];
            }
            
            $stmtPersonne->execute($params);

            $stmtEtudiant = $db->prepare("
                UPDATE ETUDIANT 
                SET ID_PROMO = :id_promo, ID_CLUB = :id_club 
                WHERE ID_PERSONNE = :id
            ");
            
            $stmtEtudiant->execute([
                'id' => $idPersonne,
                'id_promo' => $data['id_promo'],
                'id_club' => !empty($data['id_club']) ? $data['id_club'] : null
            ]);

            $db->commit();
            $_SESSION['success_message'] = "Étudiant modifié avec succès.";
            return true;

        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['error_message'] = "Erreur lors de la modification : " . $e->getMessage();
            return false;
        }
    }
}

// Gestion centralisée des actions (Store / Update / Delete)
if (isset($_GET['action'])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $controller = new EtudiantController();

    if ($_GET['action'] === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['photo']['tmp_name'];
            $fileName = $_FILES['photo']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/../../public/uploads/etudiants/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $photoPath = 'public/uploads/etudiants/' . $newFileName;
                }
            }
        }
        
        $data = $_POST;
        $data['photo'] = $photoPath;
        
        if ($controller->ajouterEtudiant($data)) {
            header('Location: ../Views/admin/etudiants.php');
            exit();
        } else {
            header('Location: ../Views/admin/ajouter_etudiant.php');
            exit();
        }
    }

    if ($_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
        $idPersonne = (int)$_GET['id'];
        $photoPath = null;
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['photo']['tmp_name'];
            $fileName = $_FILES['photo']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/../../public/uploads/etudiants/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $photoPath = 'public/uploads/etudiants/' . $newFileName;
                    
                    $ancienEtudiant = $controller->recupererEtudiant($idPersonne);
                    if ($ancienEtudiant && !empty($ancienEtudiant['PHOTO'])) {
                        $oldFile = __DIR__ . '/../../' . $ancienEtudiant['PHOTO'];
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                }
            }
        }
        
        $data = $_POST;
        if ($photoPath) {
            $data['photo'] = $photoPath;
        }
        
        if ($controller->modifierEtudiant($idPersonne, $data)) {
            header('Location: ../Views/admin/etudiants.php');
            exit();
        } else {
            header('Location: ../Views/admin/modifier_etudiant.php?id=' . $idPersonne);
            exit();
        }
    }

    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $controller->supprimerEtudiant((int)$_GET['id']);
        header('Location: ../Views/admin/etudiants.php');
        exit();
    }
}