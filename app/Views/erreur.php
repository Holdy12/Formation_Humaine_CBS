<?php // app/Views/erreur.php — attend $code, $titre, $message, $lienTexte, $lienUrl, $icone ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titre) ?> - Formation Humaine CBS</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/espace.css">
</head>
<body class="espace-etudiant page-erreur">
    <main class="erreur">
        <div class="erreur-carte">
            <div class="erreur-icone"><?= Icone::svg($icone, 30) ?></div>
            <div class="erreur-code">Erreur <?= (int)$code ?></div>
            <h1><?= htmlspecialchars($titre) ?></h1>
            <p><?= htmlspecialchars($message) ?></p>
            <a href="<?= $lienUrl ?>" class="btn btn-principal"><?= htmlspecialchars($lienTexte) ?></a>
        </div>
        <p class="erreur-pied">Formation Humaine CBS</p>
    </main>
    <script src="assets/js/espace.js"></script>
</body>
</html>
