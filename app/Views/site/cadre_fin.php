<?php // app/Views/site/cadre_fin.php — attend $contenu, $connecte, $lienEspace
$ecole = $contenu['ecole'];
$contact = $contenu['contact'];
?>
    </main>
    <footer class="site-pied">
        <div class="site-pied-interieur">
            <p class="site-pied-devise"><?php foreach ($ecole['devise'] as $mot): ?><span><?= htmlspecialchars($mot) ?></span><?php endforeach; ?></p>
            <div class="site-pied-marque">
                <p class="site-pied-nom"><?= htmlspecialchars($ecole['nom']) ?></p>
                <p class="site-pied-adresse">Institut universitaire du CEFOD<br><?= htmlspecialchars(implode(', ', $contact['adresse'])) ?></p>
            </div>
            <nav class="site-pied-nav" aria-label="Pages du site">
                <ul>
                    <?php foreach (SiteController::PAGES as $actionNav => [$vueNav, $libelle, $cheminPage]): ?>
                        <li><a href="<?= $cheminPage === '' ? './' : htmlspecialchars($cheminPage) ?>"><?= htmlspecialchars($libelle) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <ul class="site-pied-liens">
                <li><a href="<?= htmlspecialchars($lienEspace) ?>"><?= $connecte ? 'Mon espace' : 'Espace étudiant et personnel' ?></a></li>
                <li><a href="<?= htmlspecialchars($ecole['site_officiel']) ?>" rel="external">Site officiel : cbs-tchad.org</a></li>
                <li><a href="<?= htmlspecialchars($contact['facebook'][1]) ?>" rel="external">Page Facebook</a></li>
                <li><a href="mailto:<?= htmlspecialchars($contact['email']) ?>"><?= htmlspecialchars($contact['email']) ?></a></li>
            </ul>
        </div>
        <p class="site-pied-copie">© <?= date('Y') ?> <?= htmlspecialchars($ecole['nom']) ?></p>
    </footer>
    <dialog class="visionneuse" data-visionneuse aria-label="Photo en grand">
        <button type="button" class="visionneuse-fermer" data-visionneuse-fermer aria-label="Fermer"><?= Icone::svg('fermer', 24) ?></button>
        <figure>
            <img src="" alt="" data-visionneuse-image>
            <figcaption data-visionneuse-legende></figcaption>
        </figure>
    </dialog>
    <script src="assets/js/site.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/site.js') ?>"></script>
</body>
</html>
