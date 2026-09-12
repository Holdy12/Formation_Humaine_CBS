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
    <title>Profil Étudiant - CBS</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .profile-container { max-width: 1050px; margin: 0 auto; font-family: inherit; }
        
        /* En-tête de page */
        .profile-header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .profile-header-bar h2 { margin: 0; font-size: 18px; font-weight: 600; color: #1e293b; letter-spacing: -0.3px; }
        
        .btn-back { display: inline-flex; align-items: center; gap: 6px; text-decoration: none; background: #f1f5f9; color: #475569; padding: 7px 14px; border-radius: 8px; font-weight: 500; font-size: 13px; border: 1px solid #e2e8f0; transition: all 0.2s; }
        .btn-back:hover { background: #e2e8f0; color: #1e293b; }

        /* Structure Grid principale */
        .profile-grid-main { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
        @media(max-width: 800px) { .profile-grid-main { grid-template-columns: 1fr; } }

        /* Carte de gauche (Identité) */
        .card-profile { background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02); padding: 24px; text-align: center; position: relative; overflow: hidden; }
        .card-profile::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 75px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); z-index: 1; }
        
        .avatar-container { position: relative; z-index: 2; margin-top: 15px; display: inline-block; }
        .profile-avatar { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 4px solid #ffffff; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25); background: #fff; }
        
        .profile-name { margin: 12px 0 2px 0; font-size: 17px; font-weight: 700; color: #0f172a; }
        .profile-gender { font-size: 13px; color: #64748b; margin-bottom: 12px; }
        
        .badge-matricule { display: inline-block; padding: 4px 10px; background: #e0e7ff; color: #4338ca; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 20px; }

        .profile-contacts-list { border-top: 1px solid #f1f5f9; padding-top: 15px; text-align: left; display: flex; flex-direction: column; gap: 10px; }
        .contact-row { display: flex; flex-direction: column; gap: 2px; }
        .contact-label { font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 600; letter-spacing: 0.5px; }
        .contact-val { font-size: 13px; color: #334155; font-weight: 500; word-break: break-all; }

        /* Carte de droite (Informations et Cursus) */
        .card-details { background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02); padding: 24px; display: flex; flex-direction: column; gap: 24px; }
        
        .section-box-title { font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
        
        .details-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        @media(max-width: 500px) { .details-grid { grid-template-columns: 1fr; } }

        .detail-item { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 12px 14px; transition: border-color 0.2s; }
        .detail-item:hover { border-color: #cbd5e1; }
        
        .detail-key { display: block; font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 3px; text-transform: uppercase; }
        .detail-val { font-size: 14px; font-weight: 600; color: #0f172a; }
    </style>
</head>
<body>
    <div class="main-content" style="margin-left: 0; padding: 20px;">
        <div class="profile-container">
            
            <!-- Barre supérieure -->
            <div class="profile-header-bar">
                <h2>Fiche Signalétique</h2>
                <a href="index.php?action=etudiants" class="btn-back">
                    ← Retour
                </a>
            </div>

            <!-- Grille d'affichage -->
            <div class="profile-grid-main">
                
                <!-- Colonne de Gauche : Identité & Contact rapide -->
                <div class="card-profile">
                    <div class="avatar-container">
                        <?php $photoPath = !empty($etudiant['PHOTO']) ? '/' . htmlspecialchars($etudiant['PHOTO']) : '/assets/images/default-avatar.png'; ?>
                        <img src="<?= $photoPath ?>" alt="Avatar" class="profile-avatar">
                    </div>
                    
                    <h3 class="profile-name"><?= htmlspecialchars(mb_strtoupper($etudiant['NOM'] ?? '') . ' ' . ($etudiant['PRENOM'] ?? '')) ?></h3>
                    <div class="profile-gender"><?= htmlspecialchars(($etudiant['SEXE'] ?? '') === 'M' ? 'Masculin' : 'Féminin') ?></div>
                    
                    <div>
                        <span class="badge-matricule"><?= htmlspecialchars($etudiant['MATRICULE'] ?? 'N/A') ?></span>
                    </div>

                    <div class="profile-contacts-list">
                        <div class="contact-row">
                            <span class="contact-label">Email</span>
                            <span class="contact-val"><?= htmlspecialchars($etudiant['EMAIL'] ?? 'N/A') ?></span>
                        </div>
                        <div class="contact-row">
                            <span class="contact-label">Téléphone</span>
                            <span class="contact-val"><?= htmlspecialchars($etudiant['TELEPHONE'] ?? 'Aucun') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Colonne de Droite : Bloc Cursus & État civil -->
                <div class="card-details">
                    
                    <!-- Section Cursus -->
                    <div>
                        <div class="section-box-title">🎓 Cursus Académique</div>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-key">Niveau / Licence</span>
                                <span class="detail-val" style="color: #4f46e5;"><?= htmlspecialchars($etudiant['NIVEAU'] ?? 'Non défini') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-key">Filière</span>
                                <span class="detail-val"><?= htmlspecialchars($etudiant['FILIERE'] ?? 'Non définie') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-key">Promotion</span>
                                <span class="detail-val"><?= htmlspecialchars($etudiant['CODE_PROMO'] ?? 'Non assignée') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-key">Club Associé</span>
                                <span class="detail-val"><?= htmlspecialchars($etudiant['NOM_CLUB'] ?? 'Aucun club') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Section Informations Personnelles -->
                    <div>
                        <div class="section-box-title">📋 Informations Personnelles</div>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-key">Date de Naissance</span>
                                <span class="detail-val"><?= htmlspecialchars($etudiant['DATE_NAISSANCE'] ?? 'N/A') ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-key">Adresse Postale</span>
                                <span class="detail-val"><?= htmlspecialchars($etudiant['ADRESSE'] ?? 'Non renseignée') ?></span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>
</body>
</html>