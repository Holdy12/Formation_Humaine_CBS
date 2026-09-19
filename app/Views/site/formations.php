<?php // app/Views/site/formations.php — attend $contenu
$f = $contenu['formations'];
$contact = $contenu['contact'];
?>
        <section class="page-tete">
            <p class="surtitre">Formations</p>
            <h1>Un cursus LMD, du baccalauréat au master.</h1>
            <p class="grand"><?= htmlspecialchars($f['intro']) ?></p>
        </section>

        <section class="bloc bloc-cours">
            <div class="bloc-titre">
                <h2>Licences</h2>
                <p class="duree"><span class="capital-mini"><?= Composant::blocs(3, 'bloc-pt-club') ?></span><span>Trois ans, après le baccalauréat</span></p>
                <p class="note"><?= htmlspecialchars($f['licences_note']) ?></p>
            </div>
            <div class="bloc-corps">
                <ul class="tableau-cours">
                    <?php foreach ($f['licences'] as $l): ?>
                        <li><?= htmlspecialchars($l) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <section class="bloc bloc-cours bande-sable">
            <div class="bloc-titre">
                <h2>Masters et MBA</h2>
                <p class="duree"><span class="capital-mini"><?= Composant::blocs(2, 'bloc-pt-club') ?></span><span>Deux ans, après une licence</span></p>
            </div>
            <div class="bloc-corps">
                <ul class="tableau-cours">
                    <?php foreach ($f['masters'] as $m): ?>
                        <li><?= htmlspecialchars($m) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <section class="bloc">
            <div class="bloc-titre">
                <h2>Formation continue</h2>
            </div>
            <div class="bloc-corps">
                <p><?= htmlspecialchars($f['continue']) ?></p>
                <p><a href="mailto:<?= htmlspecialchars($contact['email']) ?>"><?= htmlspecialchars($contact['email']) ?></a><br><a href="tel:<?= htmlspecialchars($contact['telephones'][0][1]) ?>"><?= htmlspecialchars($contact['telephones'][0][0]) ?></a></p>
            </div>
        </section>

        <section class="bande-appel">
            <div class="bande-appel-interieur">
                <p>Les candidatures sont ouvertes chaque année de juillet à septembre.</p>
                <a class="bouton" href="admission">Candidater pour la rentrée</a>
            </div>
        </section>
