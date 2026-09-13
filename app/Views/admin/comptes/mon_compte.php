<div class="dashboard-header">
    <div>
        <h2>Mon compte</h2>
        <p>Vos informations, votre photo et votre mot de passe. Le rôle et l'email sont gérés par l'administration.</p>
    </div>
</div>

<div class="grille-2">
    <div class="colonne-profil">
        <div class="dashboard-card carte-identite">
            <div class="identite">
                <span class="avatar avatar-grand"><?php if ($compte['PHOTO']): ?><img src="<?= htmlspecialchars($compte['PHOTO']) ?>" alt="Photo de profil"><?php else: ?><?= Icone::svg('personne', 40) ?><?php endif; ?></span>
                <h3 class="identite-nom"><?= htmlspecialchars($compte['PRENOM'] . ' ' . $compte['NOM']) ?></h3>
                <p><?= htmlspecialchars(Permissions::libelleRole($compte['CODE_ROLE'])) ?></p>
            </div>
            <div class="details details-identite">
                <div class="detail"><span class="cle">Email</span><span class="val"><?= htmlspecialchars($compte['EMAIL']) ?></span></div>
                <div class="detail"><span class="cle">Téléphone</span><span class="val"><?= htmlspecialchars($compte['TELEPHONE'] ?: 'Non renseigné') ?></span></div>
                <div class="detail"><span class="cle">Compte créé le</span><span class="val"><?= Format::date($compte['DATE_CREATION']) ?></span></div>
            </div>
        </div>

        <div class="dashboard-card">
            <h3>Photo de profil</h3>
            <form method="post" action="index.php?action=admin_mon_compte_photo" enctype="multipart/form-data" class="formulaire">
                <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                <div class="champ"><?= Composant::champFichier('photo', 'photo', '.jpg,.jpeg,.png,.webp', 'jpg, png ou webp, 10 Mo max') ?></div>
                <div class="actions">
                    <button type="submit" class="btn btn-principal"><?= Icone::svg('televerser', 16) ?> Mettre à jour</button>
                    <?php if ($compte['PHOTO']): ?><button type="submit" name="supprimer" value="1" class="btn btn-fantome"><?= Icone::svg('fermer', 16) ?> Retirer la photo</button><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="dashboard-card">
            <h3>Coordonnées</h3>
            <form method="post" action="index.php?action=admin_mon_compte_coordonnees" class="formulaire">
                <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                <div class="champ"><label for="telephone">Téléphone</label><input type="text" name="telephone" id="telephone" required maxlength="100" value="<?= htmlspecialchars($compte['TELEPHONE'] ?? '') ?>"></div>
                <div class="actions"><button type="submit" class="btn btn-secondaire"><?= Icone::svg('valide', 16) ?> Enregistrer</button></div>
            </form>
        </div>
    </div>

    <div class="colonne-profil">
        <div class="dashboard-card">
            <h3>Mot de passe</h3>
            <form method="post" action="index.php?action=admin_mon_compte_mot_de_passe" class="formulaire">
                <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                <div class="champ"><label for="actuel">Mot de passe actuel</label><input type="password" name="actuel" id="actuel" required autocomplete="current-password"></div>
                <div class="champ"><label for="nouveau">Nouveau mot de passe</label><input type="password" name="nouveau" id="nouveau" required minlength="8" autocomplete="new-password"><div class="aide">Au moins 8 caractères, une majuscule et un chiffre.</div></div>
                <div class="champ"><label for="confirmation">Confirmation</label><input type="password" name="confirmation" id="confirmation" required autocomplete="new-password"></div>
                <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('cle', 16) ?> Changer le mot de passe</button></div>
            </form>
        </div>

        <div class="dashboard-card">
            <h3>Ce que votre rôle permet</h3>
            <ul class="liste-permissions">
                <?php foreach ($permissions as $p): ?><li><?= Icone::svg('valide', 14) ?> <?= htmlspecialchars($libelles[$p] ?? $p) ?></li><?php endforeach; ?>
            </ul>
            <?php if (!empty($clubs)): ?>
                <div class="aide" style="margin-top: 12px;">Vous animez : <?= htmlspecialchars(implode(', ', array_map(fn($c) => $c['NOM_CLUB'], $clubs))) ?>.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
