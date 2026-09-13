<?php
$statut = !empty($etudiant['EST_DELEGUE'])
    ? Format::genre($etudiant['SEXE'], 'Délégué de promotion', 'Déléguée de promotion')
    : Format::genre($etudiant['SEXE'], 'Étudiant', 'Étudiante');
?>
<div class="dashboard-header">
    <div>
        <h2>Mon profil</h2>
        <p>Les informations que l'école connaît de vous. Votre identité et votre cursus sont gérés par l'administration ; vous pouvez mettre à jour votre photo et vos coordonnées.</p>
    </div>
</div>

<div class="grille-profil">
    <div class="dashboard-card carte-identite">
        <div class="avatar avatar-profil">
            <?php if (!empty($etudiant['PHOTO'])): ?>
                <img src="<?= htmlspecialchars($etudiant['PHOTO']) ?>" alt="Photo de profil">
            <?php else: ?>
                <?= Icone::svg('personne', 44) ?>
            <?php endif; ?>
        </div>
        <h3 class="identite-nom"><?= htmlspecialchars($etudiant['PRENOM'] . ' ' . $etudiant['NOM']) ?></h3>
        <div class="identite-statut"><?= htmlspecialchars($statut) ?></div>
        <span class="badge badge-bleu identite-matricule"><?= htmlspecialchars($etudiant['MATRICULE']) ?></span>

        <form method="post" action="index.php?action=etudiant_photo" enctype="multipart/form-data" class="formulaire formulaire-photo">
            <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
            <div class="champ">
                <label>Photo de profil</label>
                <?= Composant::champFichier('photo', 'photo', '.jpg,.jpeg,.png,.webp', 'jpg, png ou webp, 10 Mo max, une photo nette de face', false, true) ?>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-principal btn-petit"><?= Icone::svg('televerser', 14) ?> Mettre à jour la photo</button>
            </div>
        </form>
        <?php if (!empty($etudiant['PHOTO'])): ?>
            <form method="post" action="index.php?action=etudiant_photo" class="formulaire-photo-retrait">
                <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                <input type="hidden" name="retirer" value="1">
                <button type="submit" class="btn btn-secondaire btn-petit"><?= Icone::svg('fermer', 14) ?> Retirer la photo</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="colonne-profil">
        <div class="dashboard-card">
            <h3>Cursus</h3>
            <div class="details">
                <div class="detail"><span class="cle">Niveau</span><span class="val"><?= htmlspecialchars($etudiant['LIBELLE_NIVEAU']) ?></span></div>
                <div class="detail"><span class="cle">Filière</span><span class="val"><?= htmlspecialchars($etudiant['NOM_FILIERE']) ?></span></div>
                <div class="detail"><span class="cle">Promotion</span><span class="val"><?= htmlspecialchars($etudiant['CODE_PROMO']) ?></span></div>
                <div class="detail"><span class="cle">Année académique</span><span class="val"><?= htmlspecialchars($etudiant['LIBELLE_ANNEE']) ?></span></div>
                <div class="detail"><span class="cle">Club</span><span class="val"><?= $etudiant['NOM_CLUB'] ? htmlspecialchars($etudiant['NOM_CLUB']) : 'Aucun club' ?></span></div>
                <div class="detail"><span class="cle">Compte créé le</span><span class="val"><?= Format::date($etudiant['DATE_CREATION']) ?></span></div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="entete-carte">
                <h3>Coordonnées</h3>
                <a href="index.php?action=etudiant_parametres" class="btn btn-secondaire btn-petit"><?= Icone::svg('reglages', 14) ?> Modifier</a>
            </div>
            <div class="details">
                <div class="detail"><span class="cle">Email</span><span class="val"><?= htmlspecialchars($etudiant['EMAIL']) ?></span></div>
                <div class="detail"><span class="cle">Téléphone</span><span class="val"><?= htmlspecialchars($etudiant['TELEPHONE']) ?></span></div>
                <div class="detail"><span class="cle">Adresse</span><span class="val"><?= $etudiant['ADRESSE'] ? htmlspecialchars($etudiant['ADRESSE']) : 'Non renseignée' ?></span></div>
                <div class="detail"><span class="cle">Date de naissance</span><span class="val"><?= Format::date($etudiant['DATE_NAISSANCE']) ?></span></div>
            </div>
        </div>
    </div>
</div>
