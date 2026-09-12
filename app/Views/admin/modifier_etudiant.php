<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'ADMINISTRATEUR'])) {
    header('Location: ../auth/login.php?erreur=acces_interdit');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Étudiant - CBS</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .profile-container { max-width: 1050px; margin: 0 auto; font-family: inherit; }
        
        .profile-header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .profile-header-bar h2 { margin: 0; font-size: 18px; font-weight: 600; color: #1e293b; letter-spacing: -0.3px; }
        
        .btn-back { display: inline-flex; align-items: center; gap: 6px; text-decoration: none; background: #f1f5f9; color: #475569; padding: 7px 14px; border-radius: 8px; font-weight: 500; font-size: 13px; border: 1px solid #e2e8f0; transition: all 0.2s; }
        .btn-back:hover { background: #e2e8f0; color: #1e293b; }

        .profile-grid-main { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
        @media(max-width: 800px) { .profile-grid-main { grid-template-columns: 1fr; } }

        .card-profile { background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02); padding: 24px; text-align: center; position: relative; overflow: hidden; }
        .card-profile::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 75px; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); z-index: 1; }
        
        .avatar-container { position: relative; z-index: 2; margin-top: 15px; display: inline-block; }
        .profile-avatar { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 4px solid #ffffff; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25); background: #fff; }
        
        .profile-name { margin: 12px 0 2px 0; font-size: 17px; font-weight: 700; color: #0f172a; }
        .badge-matricule { display: inline-block; padding: 4px 10px; background: #e0e7ff; color: #4338ca; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 20px; }

        .card-details { background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02); padding: 24px; display: flex; flex-direction: column; gap: 20px; }
        
        .section-box-title { font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
        
        .details-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        @media(max-width: 500px) { .details-grid { grid-template-columns: 1fr; } }

        .detail-item { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 14px; }
        
        .detail-key { display: block; font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 4px; text-transform: uppercase; }
        .form-input, .form-select { width: 100%; font-size: 14px; font-weight: 500; color: #0f172a; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; outline: none; transition: border-color 0.2s; box-sizing: border-box; }
        .form-input:focus, .form-select:focus { border-color: #4f46e5; box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1); }

        .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; border-top: 1px solid #f1f5f9; padding-top: 15px; }
        .btn-submit { background: #4f46e5; color: white; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: background 0.2s; }
        .btn-submit:hover { background: #4338ca; }
    </style>
</head>
<body>
    <div class="main-content" style="margin-left: 0; padding: 20px;">
        <div class="profile-container">
            
            <div class="profile-header-bar">
                <h2>Modifier le Dossier Étudiant</h2>
                <a href="index.php?action=voir_etudiant&id=<?= htmlspecialchars($etudiant['ID_ETUDIANT'] ?? '') ?>" class="btn-back">
                    ← Annuler
                </a>
            </div>

            <form action="index.php?action=update&id=<?= htmlspecialchars($etudiant['ID_ETUDIANT'] ?? '') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_etudiant" value="<?= htmlspecialchars($etudiant['ID_ETUDIANT'] ?? '') ?>">

                <div class="profile-grid-main">
                    
                    <div class="card-profile">
                        <div class="avatar-container">
                            <?php $photoPath = !empty($etudiant['PHOTO']) ? '/' . htmlspecialchars($etudiant['PHOTO']) : '/assets/images/default-avatar.png'; ?>
                            <img src="<?= $photoPath ?>" alt="Avatar" class="profile-avatar">
                        </div>
                        
                        <h3 class="profile-name"><?= htmlspecialchars(mb_strtoupper($etudiant['NOM'] ?? '') . ' ' . ($etudiant['PRENOM'] ?? '')) ?></h3>
                        <div>
                            <span class="badge-matricule">Matricule : <?= htmlspecialchars($etudiant['MATRICULE'] ?? 'N/A') ?></span>
                        </div>

                        <div style="text-align: left; border-top: 1px solid #f1f5f9; padding-top: 15px; display: flex; flex-direction: column; gap: 12px;">
                            <div>
                                <label class="detail-key">Changer la photo</label>
                                <input type="file" name="photo" class="form-input" style="font-size: 12px; padding: 4px;">
                            </div>
                            <div>
                                <label class="detail-key">Sexe</label>
                                <select name="sexe" class="form-select">
                                    <option value="M" <?= (($etudiant['SEXE'] ?? '') === 'M') ? 'selected' : '' ?>>Masculin</option>
                                    <option value="F" <?= (($etudiant['SEXE'] ?? '') === 'F') ? 'selected' : '' ?>>Féminin</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-details">
                        
                        <div>
                            <div class="section-box-title">📋 Identité & Coordonnées</div>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <label class="detail-key">Nom</label>
                                    <input type="text" name="nom" class="form-input" value="<?= htmlspecialchars($etudiant['NOM'] ?? '') ?>" required>
                                </div>
                                <div class="detail-item">
                                    <label class="detail-key">Prénom</label>
                                    <input type="text" name="prenom" class="form-input" value="<?= htmlspecialchars($etudiant['PRENOM'] ?? '') ?>" required>
                                </div>
                                <div class="detail-item">
                                    <label class="detail-key">Email</label>
                                    <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($etudiant['EMAIL'] ?? '') ?>">
                                </div>
                                <div class="detail-item">
                                    <label class="detail-key">Téléphone</label>
                                    <input type="text" name="telephone" class="form-input" value="<?= htmlspecialchars($etudiant['TELEPHONE'] ?? '') ?>">
                                </div>
                                <div class="detail-item">
                                    <label class="detail-key">Date de Naissance</label>
                                    <input type="date" name="date_naissance" class="form-input" value="<?= htmlspecialchars($etudiant['DATE_NAISSANCE'] ?? '') ?>">
                                </div>
                                <div class="detail-item">
                                    <label class="detail-key">Adresse Postale</label>
                                    <input type="text" name="adresse" class="form-input" value="<?= htmlspecialchars($etudiant['ADRESSE'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="section-box-title">🎓 Cursus Académique</div>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <label class="detail-key">Niveau / Licence</label>
                                    <input type="text" name="niveau" class="form-input" value="<?= htmlspecialchars($etudiant['NIVEAU'] ?? '') ?>">
                                </div>
                                <div class="detail-item">
                                    <label class="detail-key">Filière</label>
                                    <input type="text" name="filiere" class="form-input" value="<?= htmlspecialchars($etudiant['FILIERE'] ?? '') ?>">
                                </div>
                                <div class="detail-item">
                                    <label class="detail-key">Promotion</label>
                                    <input type="text" name="code_promo" class="form-input" value="<?= htmlspecialchars($etudiant['CODE_PROMO'] ?? '') ?>">
                                </div>
                                <div class="detail-item">
                                    <label class="detail-key">Club Associé</label>
                                    <input type="text" name="nom_club" class="form-input" value="<?= htmlspecialchars($etudiant['NOM_CLUB'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="index.php?action=voir_etudiant&id=<?= htmlspecialchars($etudiant['ID_ETUDIANT'] ?? '') ?>" class="btn-back" style="text-decoration: none;">Annuler</a>
                            <button type="submit" class="btn-submit">Enregistrer les modifications</button>
                        </div>

                    </div>

                </div>
            </form>

        </div>
    </div>
</body>
</html>