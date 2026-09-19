<?php require __DIR__ . '/cadre_debut.php'; ?>
                <h2>Mot de passe oublié</h2>
                <p class="auth-sous-titre">Entrez votre adresse email pour recevoir les instructions de réinitialisation.</p>

                <?php if (!empty($message)): ?>
                    <div class="alerte <?= $succes ? 'alerte-succes' : 'alerte-erreur' ?>" role="<?= $succes ? 'status' : 'alert' ?>">
                        <?= Icone::svg($succes ? 'valide' : 'erreur', 16) ?>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php?action=mot_de_passe_oublie" class="formulaire auth-formulaire" novalidate>
                    <div class="champ">
                        <label for="email">Adresse email</label>
                        <div class="champ-icone">
                            <?= Icone::svg('personne', 18) ?>
                            <input type="email" name="email" id="email" required autocomplete="email" autofocus placeholder="votre.email@exemple.com">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-principal btn-large">Envoyer le lien</button>
                    <a href="index.php?action=login" class="auth-lien">Retour à la connexion</a>
                </form>
<?php require __DIR__ . '/cadre_fin.php'; ?>