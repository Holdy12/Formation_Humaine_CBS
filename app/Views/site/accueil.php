<?php // app/Views/site/accueil.php — attend $contenu, $connecte, $lienEspace
$ecole = $contenu['ecole'];
$photos = $contenu['photos'];
$contact = $contenu['contact'];
$f = $contenu['formations'];
?>
        <section class="accueil-hero">
            <div class="accueil-hero-titre">
                <p class="surtitre"><?= htmlspecialchars($ecole['surtitre']) ?></p>
                <h1><?= htmlspecialchars($ecole['accroche']) ?></h1>
            </div>
            <div class="accueil-hero-texte">
                <p class="accueil-presentation"><?= htmlspecialchars($ecole['presentation']) ?></p>
                <p class="devise"><?= htmlspecialchars(implode(' – ', $ecole['devise'])) ?></p>
                <div class="actions">
                    <a class="bouton" href="<?= htmlspecialchars($lienEspace) ?>"><?= $connecte ? 'Ouvrir mon espace' : 'Accéder à mon espace' ?></a>
                    <a class="lien-second" href="vie-etudiante">Comprendre la Formation Humaine</a>
                </div>
            </div>
            <figure class="accueil-hero-photo cadre-photo">
                <?= Composant::photo($photos['campus'], '(min-width: 880px) 56vw, 100vw', true) ?>
            </figure>
        </section>

        <section class="reperes" aria-label="Repères">
            <p class="reperes-phrase">
                <?php foreach ($contenu['reperes'] as [$type, $valeur]): ?><?= $type === 'nombre' ? '<strong>' . htmlspecialchars($valeur) . '</strong>' : htmlspecialchars($valeur) ?><?php endforeach; ?>
            </p>
        </section>

        <section class="bloc bloc-fh">
            <div class="bloc-titre">
                <h2>Le comportement compte autant que les notes.</h2>
                <?php $avecPlafonds = false; require __DIR__ . '/capital.php'; ?>
            </div>
            <div class="bloc-corps">
                <p class="grand">Chaque semestre, chaque étudiant dispose d'un capital de <?= (int)$contenu['capital'] ?> points, évalué dans quatre domaines. Présences, engagements et écarts sont suivis, expliqués et consultables à tout moment dans l'espace étudiant.</p>
                <ul class="cartes-domaines">
                    <?php foreach ($contenu['domaines'] as $domaine): ?>
                        <li class="carte" data-carte>
                            <div class="carte-interieur">
                                <div class="carte-face carte-recto">
                                    <h3><?= htmlspecialchars($domaine['nom']) ?></h3>
                                    <p><?= htmlspecialchars($domaine['definition']) ?></p>
                                    <p class="carte-plafond">
                                        <?php if ($domaine['plafond'] !== null): ?>
                                            <span class="capital-mini"><?= Composant::blocs((int)$domaine['plafond'], 'bloc-pt-' . $domaine['couleur']) ?></span><span>Bonus : +<?= (int)$domaine['plafond'] ?> au plus</span>
                                        <?php else: ?>
                                            <span>Retraits seulement</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="carte-face carte-verso">
                                    <p class="carte-verso-titre"><?= htmlspecialchars($domaine['nom']) ?>, exemples</p>
                                    <dl class="registre-lignes">
                                        <?php foreach ($domaine['exemples'] as [$libelle, $valeur]): ?>
                                            <div><dt><?= htmlspecialchars($libelle) ?></dt><dd class="<?= $valeur < 0 ? 'perte' : 'gain' ?>"><?= Format::pointsCourts((float)$valeur) ?></dd></div>
                                        <?php endforeach; ?>
                                    </dl>
                                </div>
                            </div>
                            <button type="button" class="carte-retourner" aria-pressed="false" data-retourner>
                                <span class="carte-retourner-voir">Voir le barème</span><span class="carte-retourner-retour">Retour</span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <ol class="etapes-courtes">
                    <li>Retards et absences sont relevés à l'appel ; une absence se justifie en ligne dans les 24 heures.</li>
                    <li>Un écart de conduite fait l'objet d'un signalement ; l'étudiant est entendu et peut répondre avant toute décision.</li>
                    <li>Les engagements rapportent des points, plafonnés par domaine ; le solde du semestre devient la note de Formation Humaine.</li>
                </ol>
                <div class="actions">
                    <a class="lien-second" href="vie-etudiante">Comprendre la Formation Humaine</a>
                    <a class="lien-second" href="<?= htmlspecialchars($lienEspace) ?>">Mon espace</a>
                </div>
            </div>
        </section>

        <section class="bloc bloc-ecole bande-sable">
            <div class="bloc-titre">
                <h2>Héritière du CEFOD, tournée vers l'entreprise.</h2>
            </div>
            <div class="bloc-corps">
                <div>
                    <h3>Histoire</h3>
                    <p><?= htmlspecialchars($contenu['histoire']) ?></p>
                </div>
                <div>
                    <h3>Pédagogie</h3>
                    <p><?= htmlspecialchars($contenu['pedagogie']) ?></p>
                </div>
            </div>
            <figure class="bloc-photo cadre-photo">
                <?= Composant::photo($photos['promotion'], '(min-width: 880px) 1120px, 100vw') ?>
                <figcaption><?= htmlspecialchars($photos['promotion']['legende']) ?></figcaption>
            </figure>
        </section>

        <section class="bloc bloc-formations">
            <div class="bloc-titre">
                <h2>Huit licences, cinq masters, et la formation continue.</h2>
            </div>
            <div class="bloc-corps colonnes-2">
                <div>
                    <h3>Licences</h3>
                    <ul class="tableau-cours tableau-cours-compact">
                        <?php foreach ($f['licences'] as $l): ?><li><?= htmlspecialchars($l) ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h3>Masters et MBA</h3>
                    <ul class="tableau-cours tableau-cours-compact">
                        <?php foreach ($f['masters'] as $m): ?><li><?= htmlspecialchars($m) ?></li><?php endforeach; ?>
                    </ul>
                    <p class="note">La formation continue s'adresse aux professionnels en activité.</p>
                    <a class="lien-second" href="formations">Toutes les formations</a>
                </div>
            </div>
        </section>

        <section class="bloc bloc-vie">
            <div class="bloc-titre">
                <h2>Semaine de l'Étudiant, clubs, remise des diplômes.</h2>
                <a class="lien-second" href="vie-etudiante">La vie étudiante au CBS</a>
            </div>
            <div class="bloc-corps">
                <div class="photos-trio">
                    <?php foreach (['semaine1', 'escalier', 'semaine2'] as $cle): ?>
                        <figure class="cadre-photo">
                            <?= Composant::photo($photos[$cle], '(min-width: 880px) 360px, 100vw') ?>
                            <figcaption><?= htmlspecialchars($photos[$cle]['legende']) ?></figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="bloc bloc-contact bande-sable">
            <div class="bloc-titre">
                <h2>Nous trouver</h2>
            </div>
            <div class="bloc-corps coordonnees">
                <p><?= htmlspecialchars(implode(', ', $contact['adresse'])) ?></p>
                <p><a href="tel:<?= htmlspecialchars($contact['telephones'][0][1]) ?>"><?= htmlspecialchars($contact['telephones'][0][0]) ?></a></p>
                <p><a href="mailto:<?= htmlspecialchars($contact['email']) ?>"><?= htmlspecialchars($contact['email']) ?></a></p>
                <p><?= htmlspecialchars($contact['horaires']) ?></p>
                <a class="bouton bouton-contour" href="https://wa.me/<?= htmlspecialchars($contact['whatsapp']) ?>" rel="external">Écrire sur WhatsApp</a>
            </div>
        </section>
