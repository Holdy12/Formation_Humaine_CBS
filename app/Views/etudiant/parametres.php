<div class="dashboard-header">
    <div>
        <h2>Paramètres</h2>
        <p>Vos coordonnées et votre mot de passe. Votre identité, votre cursus et votre photo sont sur votre profil.</p>
    </div>
    <a href="index.php?action=etudiant_profil" class="btn btn-secondaire"><?= Icone::svg('personne', 16) ?> Voir mon profil</a>
</div>

<div class="grille-2">
    <div class="dashboard-card">
        <h3>Coordonnées</h3>
        <form method="post" action="index.php?action=etudiant_coordonnees" class="formulaire">
            <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
            <div class="champ">
                <label for="telephone">Téléphone</label>
                <input type="text" name="telephone" id="telephone" required maxlength="100" autocomplete="tel" value="<?= htmlspecialchars($etudiant['TELEPHONE']) ?>">
            </div>
            <div class="champ">
                <label for="adresse">Adresse</label>
                <input type="text" name="adresse" id="adresse" maxlength="100" autocomplete="street-address" value="<?= htmlspecialchars($etudiant['ADRESSE'] ?? '') ?>" placeholder="Quartier, ville">
            </div>
            <div class="actions"><button type="submit" class="btn btn-principal">Enregistrer les coordonnées</button></div>
        </form>
    </div>

    <div class="dashboard-card">
        <h3>Mot de passe</h3>
        <form method="post" action="index.php?action=etudiant_mot_de_passe" class="formulaire">
            <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
            <div class="champ">
                <label for="actuel">Mot de passe actuel</label>
                <input type="password" name="actuel" id="actuel" required autocomplete="current-password">
            </div>
            <div class="champ-ligne">
                <div class="champ">
                    <label for="nouveau">Nouveau mot de passe</label>
                    <input type="password" name="nouveau" id="nouveau" required minlength="8" autocomplete="new-password">
                </div>
                <div class="champ">
                    <label for="confirmation">Confirmation</label>
                    <input type="password" name="confirmation" id="confirmation" required minlength="8" autocomplete="new-password">
                </div>
            </div>
            <div class="aide">8 caractères minimum. Choisissez un mot de passe que vous n'utilisez nulle part ailleurs.</div>
            <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('cadenas', 14) ?> Changer le mot de passe</button></div>
        </form>
    </div>
</div>
