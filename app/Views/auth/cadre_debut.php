<?php // app/Views/auth/cadre_debut.php — attend $titre ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titre) ?> - Formation Humaine CBS</title>
    <link rel="icon" href="favicon.ico" sizes="16x16 32x32 48x48">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/espace.css">
    <link rel="stylesheet" href="assets/css/personnel.css">
    <style>@view-transition { navigation: auto; } @media (prefers-reduced-motion: reduce) { @view-transition { navigation: none; } }</style>
</head>
<body class="espace-etudiant page-auth">
    <div class="auth">
        <aside class="auth-marque">
            <div class="auth-logo"><img src="assets/images/images.jpeg" alt="CBS"></div>
            <div class="auth-marque-texte">
                <p class="auth-nom">Formation Humaine</p>
                <h1>Suivre, comprendre et valoriser le comportement de chaque étudiant.</h1>
                <p class="auth-description">Présences, signalements, points et résultats semestriels, au même endroit, pour les étudiants comme pour l'équipe pédagogique du CBS.</p>
                <ul class="auth-domaines">
                    <li><?= Icone::svg('marteau', 18) ?><span>Discipline<small>Ponctualité, tenue, respect des règles</small></span></li>
                    <li><?= Icone::svg('groupe', 18) ?><span>Vie des clubs<small>Participation, engagement, initiative</small></span></li>
                    <li><?= Icone::svg('batiment', 18) ?><span>Comportement écologique<small>Respect du campus et des ressources</small></span></li>
                    <li><?= Icone::svg('etoile', 18) ?><span>Action citoyenne<small>Engagement communautaire, concours, distinctions</small></span></li>
                </ul>
            </div>
            <p class="auth-pied">© <?= date('Y') ?> CBS, tous droits réservés</p>
        </aside>
        <main class="auth-contenu">
            <div class="auth-carte">
