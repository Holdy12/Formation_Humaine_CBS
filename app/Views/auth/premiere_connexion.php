<?php require __DIR__ . '/cadre_debut.php'; ?>
                <h2>Bienvenue<?= $prenom !== '' ? ' ' . htmlspecialchars($prenom) : '' ?></h2>
                <p class="auth-sous-titre">Avant de continuer, choisissez un mot de passe personnel. Il remplace le mot de passe temporaire qui vous a été remis.</p>

                <?php if ($message): ?>
                    <div class="alerte alerte-erreur" role="alert"><?= Icone::svg('erreur', 16) ?><span><?= htmlspecialchars($message) ?></span></div>
                <?php endif; ?>

                <form method="post" action="index.php?action=premiere_connexion" class="formulaire auth-formulaire">
                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                    <div class="champ">
                        <label for="nouveau">Nouveau mot de passe</label>
                        <div class="champ-icone">
                            <?= Icone::svg('cadenas', 18) ?>
                            <input type="password" name="nouveau" id="nouveau" required minlength="8" autocomplete="new-password" placeholder="8 caractères minimum">
                            <button type="button" class="voir-mdp" data-voir-mdp="nouveau" aria-label="Afficher le mot de passe" aria-pressed="false">
                                <span class="icone-oeil"><?= Icone::svg('oeil', 18) ?></span><span class="icone-oeil-barre" hidden><?= Icone::svg('oeil-barre', 18) ?></span>
                            </button>
                        </div>
                    </div>
                    <div class="champ">
                        <label for="confirmation">Confirmation</label>
                        <div class="champ-icone">
                            <?= Icone::svg('cadenas', 18) ?>
                            <input type="password" name="confirmation" id="confirmation" required minlength="8" autocomplete="new-password" placeholder="Le même mot de passe">
                        </div>
                    </div>
                    <div class="aide">Choisissez un mot de passe que vous n'utilisez nulle part ailleurs et ne le partagez pas.</div>
                    <button type="submit" class="btn btn-principal btn-large">Enregistrer et continuer</button>
                    <a href="index.php?action=logout" class="auth-lien">Se déconnecter</a>
                </form>
<?php require __DIR__ . '/cadre_fin.php'; ?>
