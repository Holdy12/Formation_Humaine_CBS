<?php
// app/Views/partials/entete.php — attend $titre, $actif, $utilisateur (nom, sousTitre, photo), $menu (sections)
$avatar = !empty($utilisateur['photo'])
    ? '<img src="' . htmlspecialchars($utilisateur['photo']) . '" alt="">'
    : Icone::svg('personne', 22);
$succes = $_SESSION['success_message'] ?? null;
$erreur = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titre) ?> - Formation Humaine CBS</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/espace.css">
    <link rel="stylesheet" href="assets/css/personnel.css">
    
</head>
<body class="espace-etudiant">
    <div class="voile" id="voile"></div>

    <nav class="sidebar" id="navigation" aria-label="Menu principal">
        <div>
            <div class="logo-box">
                <img src="assets/images/images.jpeg" alt="Logo CBS">
            </div>
            <div class="user-card">
                <span class="avatar avatar-grand"><?= $avatar ?></span>
                <div class="info">
                    <h4><?= htmlspecialchars($utilisateur['nom']) ?></h4>
                    <p><?= htmlspecialchars($utilisateur['sousTitre']) ?></p>
                </div>
            </div>

            <?php foreach ($menu as $section): ?>
            <div class="menu-section">
                <div class="menu-section-title"><?= htmlspecialchars($section['titre']) ?></div>
                <ul>
                    <?php foreach ($section['entrees'] as $e): ?>
                        <li><a href="index.php?action=<?= htmlspecialchars($e['action']) ?>"<?= $actif === $e['cle'] ? ' class="active" aria-current="page"' : '' ?>><?= Icone::svg($e['icone']) ?><span><?= htmlspecialchars($e['libelle']) ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-footer">
            <a href="index.php?action=logout" class="lien-deconnexion"><?= Icone::svg('deconnexion', 16) ?><span>Se déconnecter</span></a>
        </div>
    </nav>



    <div class="main-content">
        <header class="topbar-custom">
            <div class="topbar-gauche">
                <button type="button" class="bouton-menu" id="boutonMenu" aria-controls="navigation" aria-expanded="false" aria-label="Ouvrir le menu"><?= Icone::svg('menu', 20) ?></button>
                <h1 class="titre-page"><?= htmlspecialchars($titre) ?></h1>
            </div>
            <div class="topbar-droite">
                <button type="button" class="bascule-theme" id="basculeTheme" aria-label="Changer de thème" title="Changer de thème">
                    <span class="bascule-piste"><span class="bascule-curseur"><?= Icone::svg('soleil', 12, 'icone-soleil') ?><?= Icone::svg('lune', 12, 'icone-lune') ?></span></span>
                </button>
                <div class="topbar-admin">
                    <span class="avatar"><?= $avatar ?></span>
                    <span class="matricule"><?= htmlspecialchars($utilisateur['identifiant'] ?? '') ?></span>
                </div>
            </div>
        </header>

        <main class="content-body">
            <?php if ($succes): ?><div class="alerte alerte-succes" role="status"><?= Icone::svg('valide', 16) ?><span><?= htmlspecialchars($succes) ?></span></div><?php endif; ?>
            <?php if ($erreur): ?><div class="alerte alerte-erreur" role="alert"><?= Icone::svg('erreur', 16) ?><span><?= htmlspecialchars($erreur) ?></span></div><?php endif; ?>
