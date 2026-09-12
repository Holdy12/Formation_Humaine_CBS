<?php
// app/Controllers/EtudiantController.php
require_once __DIR__ . '/../../config/database.php';

class EtudiantController {
  
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

    public function storeEtudiant(array $postData, array $fileData) {
        $photoPath = null;

        if (isset($fileData['photo']) && $fileData['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $fileData['photo']['tmp_name'];
            $fileName = $fileData['photo']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/../../public/assets/uploads/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $photoPath = 'assets/uploads/' . $newFileName;
                }
            }
        }

        $postData['photo'] = $photoPath;
        $result = $this->ajouterEtudiant($postData);

        if ($result) {
            header('Location: index.php?action=etudiants');
        } else {
            header('Location: index.php?action=ajouter_etudiant');
        }
        exit();
    }

    // Méthode unique appelée par le routeur pour la mise à jour
    public function update($id, array $postData, array $fileData) {
        if (!$id) {
            header('Location: index.php?action=etudiants');
            exit();
        }

        try {
            $db = Database::getConnection();
            
            // Résolution sécurisée : trouver l'ID_PERSONNE à partir de l'ID_ETUDIANT transmis par l'URL
            $stmtGet = $db->prepare("SELECT ID_PERSONNE FROM ETUDIANT WHERE ID_ETUDIANT = :id");
            $stmtGet->execute(['id' => $id]);
            $etudiant = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if (!$etudiant) {
                throw new Exception("Étudiant introuvable.");
            }
            
            $idPersonne = (int)$etudiant['ID_PERSONNE'];
            $photoPath = null;

            if (isset($fileData['photo']) && $fileData['photo']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $fileData['photo']['tmp_name'];
                $fileName = $fileData['photo']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($fileExtension, $allowedExtensions)) {
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $uploadFileDir = __DIR__ . '/../../public/assets/uploads/';
                    
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0755, true);
                    }
                    
                    $dest_path = $uploadFileDir . $newFileName;
                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        $photoPath = 'assets/uploads/' . $newFileName;
                        
                        // Suppression de l'ancienne photo
                        $ancienEtudiant = $this->recupererEtudiant($idPersonne);
                        if ($ancienEtudiant && !empty($ancienEtudiant['PHOTO'])) {
                            $oldFile = __DIR__ . '/../../public/' . $ancienEtudiant['PHOTO'];
                            if (file_exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }
                    }
                }
            }

            if ($photoPath) {
                $postData['photo'] = $photoPath;
            }

            $this->modifierEtudiant($idPersonne, $postData);

        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erreur lors de la modification : " . $e->getMessage();
        }

        header('Location: index.php?action=etudiants');
        exit();
    }
    public function createEtudiantForm() {
        $promotions = [];
        $clubs = [];

        try {
            $db = Database::getConnection();

            $stmtPromo = $db->query("SELECT ID_PROMO, CODE_PROMO FROM PROMOTION");
            $promotions = $stmtPromo->fetchAll(PDO::FETCH_ASSOC);

            $stmtClub = $db->query("SELECT ID_CLUB, NOM_CLUB FROM CLUB");
            $clubs = $stmtClub->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erreur lors du chargement des listes : " . $e->getMessage();
        }

        $data = [
            'promotions' => $promotions,
            'clubs' => $clubs
        ];

        // Correction du chemin d'accès (on remonte deux fois pour atteindre la racine puis app/Views)
        require_once __DIR__ . '/../../app/Views/admin/ajouter_etudiant.php';
    }

    // Méthode appelée par le routeur pour la suppression
    public function delete($id) {
        if (!$id) {
            header('Location: index.php?action=etudiants');
            exit();
        }
        
        try {
            $db = Database::getConnection();
            $stmtGet = $db->prepare("SELECT ID_PERSONNE FROM ETUDIANT WHERE ID_ETUDIANT = :id");
            $stmtGet->execute(['id' => $id]);
            $etudiant = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if ($etudiant) {
                $this->supprimerEtudiant((int)$etudiant['ID_PERSONNE']);
            } else {
                throw new Exception("Étudiant introuvable.");
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erreur lors de la suppression : " . $e->getMessage();
        }

        header('Location: index.php?action=etudiants');
        exit();
    }

    public function supprimerEtudiant(int $idPersonne): bool {
        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $stmtPhoto = $db->prepare("SELECT PHOTO FROM PERSONNE WHERE ID_PERSONNE = :id");
            $stmtPhoto->execute(['id' => $idPersonne]);
            $personne = $stmtPhoto->fetch(PDO::FETCH_ASSOC);

            if ($personne && !empty($personne['PHOTO'])) {
                $photoPath = __DIR__ . '/../../public/' . $personne['PHOTO'];
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
public function listerEtudiantsParSexe(?string $sexe = null): array {
        try {
            $db = Database::getConnection();
            
            if ($sexe && in_array(strtoupper($sexe), ['M', 'F'])) {
                $stmt = $db->prepare("
                    SELECT p.*, e.ID_ETUDIANT, e.ID_PROMO, e.ID_CLUB 
                    FROM PERSONNE p 
                    JOIN ETUDIANT e ON p.ID_PERSONNE = e.ID_PERSONNE 
                    WHERE p.SEXE = :sexe
                    ORDER BY p.NOM ASC
                ");
                $stmt->execute(['sexe' => strtoupper($sexe)]);
            } else {
                // Tri par défaut avec ordre alphabétique et regroupement optionnel par sexe
                $stmt = $db->query("
                    SELECT p.*, e.ID_ETUDIANT, e.ID_PROMO, e.ID_CLUB 
                    FROM PERSONNE p 
                    JOIN ETUDIANT e ON p.ID_PERSONNE = e.ID_PERSONNE 
                    ORDER BY p.SEXE ASC, p.NOM ASC
                ");
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur lors du chargement de la liste : " . $e->getMessage();
            return [];
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

public function voirEtudiant($id) {
        if (!$id) {
            $_SESSION['error_message'] = "ID d'étudiant non spécifié.";
            header('Location: index.php?action=etudiants');
            exit();
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT p.*, e.ID_PROMO, e.ID_CLUB, 
                       pr.CODE_PROMO, c.NOM_CLUB,
                       COALESCE(n.LIBELLE_NIVEAU, 'Licence 1') AS NIVEAU, 
                       'Génie Informatique' AS FILIERE 
                FROM PERSONNE p 
                JOIN ETUDIANT e ON p.ID_PERSONNE = e.ID_PERSONNE 
                LEFT JOIN PROMOTION pr ON e.ID_PROMO = pr.ID_PROMO
                LEFT JOIN NIVEAU n ON pr.ID_NIVEAU = n.ID_NIVEAU
                LEFT JOIN CLUB c ON e.ID_CLUB = c.ID_CLUB
                WHERE p.ID_PERSONNE = :id
            ");
            $stmt->execute(['id' => $id]);
            $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$etudiant) {
                $_SESSION['error_message'] = "Étudiant introuvable en base de données.";
                header('Location: index.php?action=etudiants');
                exit();
            }

            // Chargement de la vue de profil
            require_once __DIR__ . '/../Views/admin/voir_etudiant.php';

        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erreur technique : " . $e->getMessage();
            header('Location: index.php?action=etudiants');
            exit();
        }
    }

    public function editForm($id) {
    if (!$id) {
        header('Location: index.php?action=etudiants');
        exit();
    }

    try {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT p.*, e.* 
            FROM ETUDIANT e 
            JOIN PERSONNE p ON e.ID_PERSONNE = p.ID_PERSONNE 
            WHERE e.ID_PERSONNE = :id
        ");
        $stmt->execute(['id' => $id]);
        $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$etudiant) {
            header('Location: index.php?action=etudiants');
            exit();
        }

        require_once __DIR__ . '/../Views/admin/modifier_etudiant.php';

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        header('Location: index.php?action=etudiants');
        exit();
    }
}


}
