<?php // app/Views/site/cadre_debut.php — attend $contenu, $titre, $description, $connecte, $prenom, $lienEspace, $pageCourante, $chemin
$ecole = $contenu['ecole'];
$adresseCanonique = BASE_URL . '/' . $chemin;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titre) ?></title>
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($adresseCanonique) ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="<?= htmlspecialchars($ecole['nom']) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($titre) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($adresseCanonique) ?>">
    <meta property="og:image" content="<?= htmlspecialchars(BASE_URL . '/assets/images/site/campus-batiment.jpg') ?>">
    <meta name="theme-color" content="#1c1a17">
    <link rel="icon" href="favicon.ico" sizes="16x16 32x32 48x48">
    <link rel="apple-touch-icon" href="assets/images/site/icone-180.png">
    <link rel="preload" href="assets/fonts/oswald.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="assets/css/site.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/site.css') ?>">
</head>
<body class="site">
    <a class="site-evitement" href="#contenu">Aller au contenu</a>
    <header class="site-entete" data-entete>
        <div class="site-entete-interieur">
            <a class="site-marque" href="./" aria-label="<?= htmlspecialchars($ecole['nom']) ?>, accueil">
                <img src="assets/images/images.jpeg" alt="" width="113" height="44">
            </a>
            <button class="site-menu-bouton" type="button" aria-expanded="false" aria-controls="site-nav" data-menu>
                <span class="burger" aria-hidden="true"></span>
                <span class="visuellement-cache">Menu</span>
            </button>
            <nav class="site-nav" id="site-nav" aria-label="Navigation principale" data-nav>
                <ul>
                    <?php foreach (SiteController::PAGES as $actionNav => [$vueNav, $libelle, $cheminPage]): ?>
                        <li><a href="<?= $cheminPage === '' ? './' : htmlspecialchars($cheminPage) ?>"<?= $actionNav === $pageCourante ? ' aria-current="page"' : '' ?>><?= htmlspecialchars($libelle) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <div class="site-nav-pied">
                    <a class="bouton bouton-contour bouton-petit site-compte" href="<?= htmlspecialchars($lienEspace) ?>">
                        <?php if ($connecte): ?>
                            Mon espace<?= $prenom !== '' ? '<small>' . htmlspecialchars($prenom) . '</small>' : '' ?>
                        <?php else: ?>
                            Se connecter
                        <?php endif; ?>
                    </a>
                    <p class="site-nav-contact">
                        <a href="tel:<?= htmlspecialchars($contenu['contact']['telephones'][0][1]) ?>"><?= htmlspecialchars($contenu['contact']['telephones'][0][0]) ?></a>
                        <a href="https://wa.me/<?= htmlspecialchars($contenu['contact']['whatsapp']) ?>" rel="external">WhatsApp</a>
                    </p>
                </div>
            </nav>
        </div>
    </header>
    <main id="contenu" class="site-contenu">
