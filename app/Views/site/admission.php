<?php // app/Views/site/admission.php — attend $contenu
$a = $contenu['admission'];
$contact = $contenu['contact'];
?>
        <section class="page-tete">
            <p class="surtitre">Admission</p>
            <h1>Rejoindre CBS à la rentrée.</h1>
            <p class="grand"><?= htmlspecialchars($a['intro']) ?></p>
        </section>

        <section class="bloc bloc-frise bande-sable">
            <div class="bloc-titre">
                <h2>Trois étapes</h2>
            </div>
            <ol class="frise">
                <?php foreach ($a['etapes'] as [$titreEtape, $texte]): ?>
                    <li><h3><?= htmlspecialchars($titreEtape) ?></h3><p><?= htmlspecialchars($texte) ?></p></li>
                <?php endforeach; ?>
            </ol>
            <figure class="calendrier" role="img" aria-label="Candidatures ouvertes de juillet à septembre">
                <ol class="calendrier-mois" aria-hidden="true">
                    <?php foreach (['J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D'] as $i => $lettre): ?>
                        <li style="--n:<?= $i ?>"<?= in_array($i + 1, $a['mois_ouverts'], true) ? ' class="ouvert"' : '' ?>><?= $lettre ?></li>
                    <?php endforeach; ?>
                </ol>
                <figcaption aria-hidden="true">Candidatures ouvertes de juillet à septembre</figcaption>
            </figure>
        </section>

        <section class="bloc">
            <div class="bloc-titre">
                <h2>Conditions par niveau</h2>
            </div>
            <div class="bloc-corps">
                <table class="tableau">
                    <thead><tr><th scope="col">Vous avez</th><th scope="col">Vous entrez en</th><th scope="col">Sélection</th></tr></thead>
                    <tbody>
                        <?php foreach ($a['conditions'] as [$avoir, $entree, $selection]): ?>
                            <tr><td><?= htmlspecialchars($avoir) ?></td><td><?= htmlspecialchars($entree) ?></td><td><?= htmlspecialchars($selection) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="note"><?= htmlspecialchars($a['frais']) ?></p>
            </div>
        </section>

        <section class="bloc bloc-appel">
            <div class="bloc-titre">
                <h2>Candidater</h2>
            </div>
            <div class="bloc-corps">
                <div class="actions">
                    <a class="bouton" href="<?= htmlspecialchars($a['inscription_url']) ?>" rel="external">Candidater en ligne</a>
                    <a class="lien-second" href="mailto:<?= htmlspecialchars($contact['email']) ?>">Écrire au secrétariat</a>
                    <a class="lien-second" href="tel:<?= htmlspecialchars($contact['telephones'][0][1]) ?>"><?= htmlspecialchars($contact['telephones'][0][0]) ?></a>
                </div>
            </div>
        </section>
