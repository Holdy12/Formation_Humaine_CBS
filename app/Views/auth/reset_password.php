<?php require __DIR__ . '/cadre_debut.php'; ?>
                <h2>Nouveau mot de passe</h2>
                <p class="auth-sous-titre">Veuillez choisir un nouveau mot de passe sécurisé.</p>

                <?php if (!empty($message)): ?>
                    <div class="alerte <?= $succes ? 'alerte-succes' : 'alerte-erreur' ?>" role="<?= $succes ? 'status' : 'alert' ?>">
                        <?= Icone::svg($succes ? 'valide' : 'erreur', 16) ?>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (empty($invalide) || !$invalide): ?>
                <form method="post" action="index.php?action=reset_password" class="formulaire auth-formulaire" novalidate>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
                    
                    <div class="champ">
                        <label for="password">Nouveau mot de passe</label>
                        <div class="champ-icone">
                            <?= Icone::svg('cadenas', 18) ?>
                            <input type="password" name="password" id="password" required placeholder="8 caractères minimum">
                        </div>
                    </div>

                    <div class="champ">
                        <label for="confirmation">Confirmer le mot de passe</label>
                        <div class="champ-icone">
                            <?= Icone::svg('cadenas', 18) ?>
                            <input type="password" name="confirmation" id="confirmation" required placeholder="Répétez le mot de passe">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-principal btn-large">Mettre à jour le mot de passe</button>
                </form>
                <?php endif; ?>

                <a href="index.php?action=login" class="auth-lien">Retour à la connexion</a>
<?php require __DIR__ . '/cadre_fin.php'; ?>