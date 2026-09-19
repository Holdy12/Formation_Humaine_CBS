<?php // app/Views/site/vie_etudiante.php — attend $contenu, $connecte, $lienEspace
$photos = $contenu['photos'];
?>
        <section class="vie-hero">
            <figure class="vie-hero-photo">
                <?= Composant::photo($photos['portail'], '100vw', true) ?>
                <figcaption><?= htmlspecialchars($photos['portail']['legende']) ?></figcaption>
            </figure>
            <div class="vie-hero-plaque">
                <p class="surtitre">Vie étudiante et Formation Humaine</p>
                <h1>Ce que le CBS attend de ses étudiants, et comment il le mesure.</h1>
                <p class="grand">La Formation Humaine est la manière dont le CBS suit, explique et valorise le comportement de chaque étudiant : présence, engagement dans les clubs, respect du campus, action citoyenne.</p>
            </div>
        </section>

        <section class="bloc">
            <div class="bloc-titre">
                <h2>Pourquoi</h2>
            </div>
            <div class="bloc-corps">
                <p class="accroche"><?= htmlspecialchars($contenu['pourquoi_accroche']) ?></p>
                <p><?= htmlspecialchars($contenu['pourquoi']) ?></p>
            </div>
        </section>

        <section class="bloc bloc-domaines bande-encre">
            <div class="bloc-titre">
                <h2>Les quatre domaines</h2>
                <p class="note">Barème indicatif ; les valeurs en vigueur sont fixées par la direction.</p>
                <?php require __DIR__ . '/capital.php'; ?>
            </div>
            <div class="bloc-corps">
                <div class="registre">
                    <?php foreach ($contenu['domaines'] as $domaine): ?>
                        <section class="registre-domaine">
                            <h3><?= htmlspecialchars($domaine['nom']) ?></h3>
                            <p><?= htmlspecialchars($domaine['definition']) ?></p>
                            <dl class="registre-lignes">
                                <?php foreach ($domaine['exemples'] as [$libelle, $valeur]): ?>
                                    <div><dt><?= htmlspecialchars($libelle) ?></dt><dd class="<?= $valeur < 0 ? 'perte' : 'gain' ?>"><?= Format::pointsCourts((float)$valeur) ?></dd></div>
                                <?php endforeach; ?>
                            </dl>
                            <?php if ($domaine['plafond'] !== null): ?>
                                <p class="registre-plafond">Bonus plafonné à <?= (int)$domaine['plafond'] ?> points par semestre</p>
                            <?php endif; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="bloc bloc-fonctionnement">
            <div class="bloc-titre">
                <h2>Comment ça marche</h2>
                <figure class="cadre-photo bloc-photo-aside">
                    <?= Composant::photo($photos['classe'], '(min-width: 880px) 420px, 100vw') ?>
                    <figcaption><?= htmlspecialchars($photos['classe']['legende']) ?></figcaption>
                </figure>
            </div>
            <div class="bloc-corps">
                <ol class="etapes">
                    <?php foreach ($contenu['fonctionnement'] as [$titreEtape, $texte]): ?>
                        <li><h3><?= htmlspecialchars($titreEtape) ?></h3><p><?= htmlspecialchars($texte) ?></p></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>

        <section class="bloc bloc-appel bande-sable">
            <div class="bloc-titre">
                <h2>Accéder à mon espace</h2>
            </div>
            <div class="bloc-corps">
                <p><?= htmlspecialchars($contenu['aide_connexion']) ?></p>
                <div class="actions">
                    <a class="bouton" href="<?= htmlspecialchars($lienEspace) ?>"><?= $connecte ? 'Ouvrir mon espace' : 'Se connecter' ?></a>
                </div>
            </div>
        </section>
