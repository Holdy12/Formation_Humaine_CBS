<?php require __DIR__ . '/cadre_debut.php'; ?>
                <h2>Mot de passe oublié</h2>
                <p class="auth-sous-titre">La réinitialisation se fait avec l'aide de l'administration.</p>

                <ol class="auth-etapes">
                    <li><strong>Contactez l'administration</strong> de la Formation Humaine, en indiquant votre matricule ou votre adresse email.</li>
                    <li><strong>Un mot de passe temporaire</strong> vous est remis après vérification de votre identité.</li>
                    <li><strong>À votre prochaine connexion</strong>, l'application vous demande de choisir un nouveau mot de passe.</li>
                </ol>

                <a href="index.php?action=login" class="btn btn-principal btn-large"><?= Icone::svg('retour', 16) ?> Retour à la connexion</a>
<?php require __DIR__ . '/cadre_fin.php'; ?>
