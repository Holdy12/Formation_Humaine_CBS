<?php
// app/Views/admin/ajouter_etudiant.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'ADMINISTRATEUR'])) {
    header('Location: ../auth/login.php?erreur=acces_interdit');
    exit();
}

require_once __DIR__ . '/../../config/database.php';
$db = Database::getConnection();

// Récupérer les promotions et les clubs pour les listes déroulantes
$promotions = $db->query("SELECT * FROM PROMOTION ORDER BY CODE_PROMO ASC")->fetchAll(PDO::FETCH_ASSOC);
$clubs = $db->query("SELECT * FROM CLUB ORDER BY NOM_CLUB ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Étudiant - Formation Humaine CBS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body { display: flex; height: 100vh; background-color: #f8f9fa; overflow: hidden; }
        .sidebar { width: 280px; background-color: #1a1a1a; color: white; display: flex; flex-direction: column; justify-content: space-between; padding: 20px; overflow-y: auto; border-right: 4px solid #ff7f00; }
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding: 30px; }
        h2 { color: #333; font-size: 20px; margin-bottom: 20px; }
        form { background: white; padding: 25px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); max-width: 800px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group.full { grid-column: span 2; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #333; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; }
        button { background: #ff7f00; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; cursor: pointer; font-size: 13px; }
        button:hover { background: #e06f00; }
        .btn-back { background: #6c757d; text-decoration: none; color: white; padding: 10px 20px; border-radius: 4px; font-size: 13px; margin-right: 10px; display: inline-block; }
        .alert-error { background: #fde8e8; color: #c81e1e; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; border: 1px solid #fbd5d5; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div style="background: white; padding: 10px; text-align: center; border-radius: 4px; margin-bottom: 20px;">
                <img src="../../assets/images/images.jpeg" alt="Logo CBS" style="max-width: 100%; height: 40px; object-fit: contain;">
            </div>
            <ul style="list-style: none;">
                <li><a href="etudiants.php" style="color: #ff7f00; text-decoration: none; display: block; padding: 8px 12px; font-weight: bold;">← Retour à la liste</a></li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <h2>Enregistrer un Nouvel Étudiant</h2>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert-error">
                <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <form action="../Controllers/EtudiantController.php?action=store" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom" required>
                </div>
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="prenom" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="text" name="telephone" required>
                </div>
                <div class="form-group">
                    <label>Sexe</label>
                    <select name="sexe" required>
                        <option value="M">Masculin</option>
                        <option value="F">Féminin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date de Naissance</label>
                    <input type="date" name="date_naissance" required>
                </div>
                <div class="form-group">
                    <label>Promotion</label>
                    <select name="id_promo" required>
                        <?php foreach ($promotions as $promo): ?>
                            <option value="<?= $promo['ID_PROMO'] ?>"><?= htmlspecialchars($promo['CODE_PROMO']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Club (Optionnel)</label>
                    <select name="id_club">
                        <option value="">Aucun club</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?= $club['ID_CLUB'] ?>"><?= htmlspecialchars($club['NOM_CLUB']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Adresse</label>
                    <textarea name="adresse" rows="2"></textarea>
                </div>
                <div class="form-group full">
                    <label>Photo de profil</label>
                    <input type="file" name="photo" accept="image/*">
                </div>
            </div>
            <div>
                <a href="etudiants.php" class="btn-back">Annuler</a>
                <button type="submit">Enregistrer l'étudiant</button>
            </div>
        </form>
    </div>

</body>
</html>