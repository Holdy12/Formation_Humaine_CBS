<?php require __DIR__ . '/cadre_debut.php'; ?>
                <h2>Connexion</h2>
                <p class="auth-sous-titre">Entrez votre matricule ou votre adresse email et votre mot de passe.</p>

                <?php if ($message): ?>
                    <div class="alerte <?= $succes ? 'alerte-succes' : 'alerte-erreur' ?>" role="<?= $succes ? 'status' : 'alert' ?>"><?= Icone::svg($succes ? 'valide' : 'erreur', 16) ?><span><?= htmlspecialchars($message) ?></span></div>
                <?php endif; ?>

                <form method="post" action="index.php?action=login" class="formulaire auth-formulaire" novalidate>
                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                    <div class="champ">
                        <label for="identifiant">Identifiant</label>
                        <div class="champ-icone">
                            <?= Icone::svg('personne', 18) ?>
                            <input type="text" name="identifiant" id="identifiant" required autocomplete="username"<?= $identifiant === '' ? ' autofocus' : '' ?> placeholder="Matricule ou adresse email" value="<?= htmlspecialchars($identifiant) ?>">
                        </div>
                    </div>
                    <div class="champ">
                        <label for="password">Mot de passe</label>
                        <div class="champ-icone">
                            <?= Icone::svg('cadenas', 18) ?>
                            <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Votre mot de passe"<?= $identifiant !== '' ? ' autofocus' : '' ?>>
                            <button type="button" class="voir-mdp" data-voir-mdp="password" aria-label="Afficher le mot de passe" aria-pressed="false">
                                <span class="icone-oeil"><?= Icone::svg('oeil', 18) ?></span><span class="icone-oeil-barre" hidden><?= Icone::svg('oeil-barre', 18) ?></span>
                            </button>
                        </div>
                    </div>
                    <label class="case auth-case">
                        <input type="checkbox" name="rester" value="1">
                        <span>Rester connecté sur cet appareil <small>pendant 10 jours, à éviter sur un ordinateur partagé</small></span>
                    </label>
                    <button type="submit" class="btn btn-principal btn-large">Se connecter</button>
                    <a href="index.php?action=mot_de_passe_oublie" class="auth-lien">Mot de passe oublié ?</a>
                    <a href="./" class="auth-lien auth-lien-site">Retour au site</a>
                </form>
<?php require __DIR__ . '/cadre_fin.php'; ?>
