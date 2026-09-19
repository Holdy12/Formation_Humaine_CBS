<?php // app/Views/site/contact.php — attend $contenu, $connecte, $lienEspace, $formulaire
$c = $contenu['contact'];
$photos = $contenu['photos'];
$v = $formulaire['valeurs'];
$e = $formulaire['erreurs'];
$avis = [
    'erreurs'      => ['alerte', "Quelques champs sont à revoir avant l'envoi."],
    'expire'       => ['alerte', "Le formulaire a expiré. Vérifiez votre message ci-dessous et envoyez-le à nouveau."],
    'indisponible' => ['alerte', "Votre message n'est pas parti : l'envoi depuis le site n'est pas encore en service. Écrivez-nous à " . $c['email'] . " ou sur WhatsApp ; votre texte est conservé ci-dessous."],
    'recu'         => ['statut', "Message reçu. Nous vous répondrons à l'adresse indiquée."],
][(string)$formulaire['etat']] ?? null;
// Attributs d'un champ : valeur conservée, état d'erreur relié au message.
$attributs = function (string $nom) use ($v, $e): string {
    $a = ' id="' . $nom . '" name="' . $nom . '"';
    if (isset($e[$nom])) {
        $a .= ' aria-invalid="true" aria-describedby="' . $nom . '-erreur"';
    }
    return $a;
};
$erreur = fn(string $nom): string => isset($e[$nom]) ? '<p class="champ-erreur" id="' . $nom . '-erreur">' . htmlspecialchars($e[$nom]) . '</p>' : '';
?>
        <section class="page-tete">
            <p class="surtitre">Contact</p>
            <h1>Nous trouver, nous écrire.</h1>
        </section>

        <section class="bloc bloc-coordonnees">
            <div class="bloc-corps colonnes-2 colonnes-contact">
                <div class="carte-contact">
                    <p class="carte-adresse"><?= str_replace('-', "\u{2011}", htmlspecialchars($c['adresse'][0])) ?><br><?= htmlspecialchars($c['adresse'][1]) ?></p>
                    <p class="carte-tel">
                        <?php foreach ($c['telephones'] as $i => [$affiche, $lien]): ?>
                            <span><a href="tel:<?= htmlspecialchars($lien) ?>"><?= htmlspecialchars($affiche) ?></a><?= $i === 1 ? '<small>standard</small>' : '' ?></span>
                        <?php endforeach; ?>
                    </p>
                    <p><a href="<?= htmlspecialchars($c['maps']) ?>" rel="external">Voir sur Google Maps</a></p>
                <dl class="coordonnees-liste">
                    <div>
                        <dt>E-mail</dt>
                        <dd><a href="mailto:<?= htmlspecialchars($c['email']) ?>"><?= htmlspecialchars($c['email']) ?></a></dd>
                    </div>
                    <div>
                        <dt>Horaires</dt>
                        <dd><?= htmlspecialchars($c['horaires']) ?></dd>
                    </div>
                    <div>
                        <dt>Facebook</dt>
                        <dd><a href="<?= htmlspecialchars($c['facebook'][1]) ?>" rel="external"><?= htmlspecialchars($c['facebook'][0]) ?></a></dd>
                    </div>
                    <div class="coordonnees-whatsapp">
                        <dd><a class="bouton bouton-contour" href="https://wa.me/<?= htmlspecialchars($c['whatsapp']) ?>" rel="external">Écrire sur WhatsApp</a></dd>
                    </div>
                </dl>
                </div>
                <figure class="cadre-photo">
                    <?= Composant::photo($photos['campus'], '(min-width: 880px) 520px, 100vw') ?>
                    <figcaption><?= htmlspecialchars($photos['campus']['legende']) ?></figcaption>
                </figure>
            </div>
        </section>

        <section class="bloc bloc-formulaire" id="formulaire">
            <div class="bloc-titre">
                <h2>Écrire un message</h2>
                <p class="note">Une question sur l'admission, les formations ou la Formation Humaine : écrivez-nous, nous répondons par e-mail.</p>
            </div>
            <div class="bloc-corps">
                <?php if ($avis): ?>
                    <p class="avis avis-<?= $avis[0] ?>" role="<?= $avis[0] === 'alerte' ? 'alert' : 'status' ?>"><?= htmlspecialchars($avis[1]) ?></p>
                <?php endif; ?>
                <form class="formulaire" method="post" action="contact#formulaire" novalidate data-formulaire>
                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($formulaire['jeton']) ?>">
                    <div class="champ-piege" aria-hidden="true">
                        <label for="site_web">Ne pas remplir</label>
                        <input type="text" id="site_web" name="site_web" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="champs-2">
                        <div class="champ">
                            <label for="nom">Nom et prénom</label>
                            <span class="champ-ligne"><input type="text"<?= $attributs('nom') ?> autocomplete="name" maxlength="100" required value="<?= htmlspecialchars($v['nom'] ?? '') ?>"></span>
                            <?= $erreur('nom') ?>
                        </div>
                        <div class="champ">
                            <label for="email">E-mail</label>
                            <span class="champ-ligne"><input type="email"<?= $attributs('email') ?> autocomplete="email" inputmode="email" maxlength="150" required value="<?= htmlspecialchars($v['email'] ?? '') ?>"></span>
                            <?= $erreur('email') ?>
                        </div>
                    </div>
                    <div class="champ">
                        <label for="telephone">Téléphone <small>facultatif</small></label>
                        <span class="champ-ligne"><input type="tel"<?= $attributs('telephone') ?> autocomplete="tel" inputmode="tel" maxlength="30" value="<?= htmlspecialchars($v['telephone'] ?? '') ?>"></span>
                    </div>
                    <fieldset class="champ champ-objet"<?= isset($e['objet']) ? ' aria-describedby="objet-erreur"' : '' ?>>
                        <legend>Objet</legend>
                        <div class="puces-choix">
                            <?php foreach ($c['objets'] as $cle => $libelle): ?>
                                <label class="puce-choix"><input type="radio" name="objet" value="<?= htmlspecialchars($cle) ?>"<?= ($v['objet'] ?? '') === $cle ? ' checked' : '' ?>><span><?= htmlspecialchars($libelle) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                        <?= $erreur('objet') ?>
                    </fieldset>
                    <div class="champ">
                        <label for="message">Message</label>
                        <span class="champ-ligne"><textarea<?= $attributs('message') ?> rows="6" maxlength="2000" required data-compteur><?= htmlspecialchars($v['message'] ?? '') ?></textarea></span>
                        <?= $erreur('message') ?>
                        <p class="champ-compteur" aria-live="polite"><span data-compteur-valeur><?= mb_strlen($v['message'] ?? '') ?></span> / 2000</p>
                    </div>
                    <div class="actions">
                        <button type="submit" class="bouton">Envoyer le message</button>
                        <p class="note">Vos coordonnées ne servent qu'à vous répondre.</p>
                    </div>
                </form>
            </div>
        </section>

        <section class="bloc bloc-appel bande-sable">
            <div class="bloc-titre">
                <h2>Étudiants et personnel</h2>
            </div>
            <div class="bloc-corps">
                <p>Votre espace est ici : solde de points, présences, signalements et résultats pour les étudiants ; appel, instruction et registre pour l'équipe.</p>
                <div class="actions">
                    <a class="lien-second" href="<?= htmlspecialchars($lienEspace) ?>"><?= $connecte ? 'Ouvrir mon espace' : 'Se connecter' ?></a>
                </div>
            </div>
        </section>
