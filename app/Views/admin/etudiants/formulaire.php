<?php $modification = $etudiant !== null; ?>
<div class="dashboard-header">
    <div>
        <h2><?= $modification ? 'Modifier ' . htmlspecialchars($etudiant['PRENOM'] . ' ' . $etudiant['NOM']) : 'Nouvel étudiant' ?></h2>
        <p><?= $modification ? 'Identité, inscription et photo. Le matricule ne change pas.' : "Le matricule et un mot de passe temporaire sont générés à l'enregistrement." ?></p>
    </div>
    <a href="<?= $modification ? 'index.php?action=admin_etudiant&id=' . (int)$etudiant['ID_PERSONNE'] : 'index.php?action=admin_etudiants' ?>" class="btn btn-secondaire"><?= Icone::svg('retour', 16) ?> Annuler</a>
</div>

<form method="post" action="index.php?action=<?= $modification ? 'admin_etudiant_modifier&id=' . (int)$etudiant['ID_PERSONNE'] : 'admin_etudiant_nouveau' ?>" enctype="multipart/form-data">
    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
    <div class="grille-2">
        <div class="dashboard-card">
            <h3>Identité</h3>
            <div class="formulaire">
                <div class="champ-ligne">
                    <div class="champ"><label for="nom">Nom</label><input type="text" name="nom" id="nom" required maxlength="50" value="<?= htmlspecialchars($saisie['nom'] ?? '') ?>"></div>
                    <div class="champ"><label for="prenom">Prénom</label><input type="text" name="prenom" id="prenom" required maxlength="50" value="<?= htmlspecialchars($saisie['prenom'] ?? '') ?>"></div>
                </div>
                <div class="champ-ligne">
                    <div class="champ"><label for="sexe">Sexe</label>
                        <select name="sexe" id="sexe" required>
                            <option value="M"<?= ($saisie['sexe'] ?? 'M') === 'M' ? ' selected' : '' ?>>Masculin</option>
                            <option value="F"<?= ($saisie['sexe'] ?? '') === 'F' ? ' selected' : '' ?>>Féminin</option>
                        </select>
                    </div>
                    <div class="champ"><label for="date_naissance">Date de naissance</label><input type="date" name="date_naissance" id="date_naissance" required max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($saisie['date_naissance'] ?? '') ?>"></div>
                </div>
                <div class="champ"><label for="email">Email</label><input type="email" name="email" id="email" required maxlength="100" value="<?= htmlspecialchars($saisie['email'] ?? '') ?>"></div>
                <div class="champ-ligne">
                    <div class="champ"><label for="telephone">Téléphone</label><input type="text" name="telephone" id="telephone" required maxlength="100" value="<?= htmlspecialchars($saisie['telephone'] ?? '') ?>"></div>
                    <div class="champ"><label for="adresse">Adresse</label><input type="text" name="adresse" id="adresse" maxlength="100" value="<?= htmlspecialchars($saisie['adresse'] ?? '') ?>" placeholder="Quartier, ville"></div>
                </div>
            </div>
        </div>

        <div class="colonne-profil">
            <div class="dashboard-card">
                <h3>Inscription</h3>
                <div class="formulaire">
                    <div class="champ"><label for="id_promo">Promotion</label>
                        <select name="id_promo" id="id_promo" required>
                            <option value="">Choisir une promotion</option>
                            <?php foreach ($promotions as $p): ?><option value="<?= (int)$p['ID_PROMO'] ?>"<?= (int)($saisie['id_promo'] ?? 0) === (int)$p['ID_PROMO'] ? ' selected' : '' ?>><?= htmlspecialchars($p['CODE_PROMO'] . ' (' . $p['LIBELLE_NIVEAU'] . ' ' . $p['NOM_FILIERE'] . ', ' . $p['LIBELLE_ANNEE'] . ')') ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="champ"><label for="id_club">Club</label>
                        <select name="id_club" id="id_club">
                            <option value="">Aucun club</option>
                            <?php foreach ($clubs as $c): ?><option value="<?= (int)$c['ID_CLUB'] ?>"<?= (int)($saisie['id_club'] ?? 0) === (int)$c['ID_CLUB'] ? ' selected' : '' ?>><?= htmlspecialchars($c['NOM_CLUB']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <label class="case"><input type="checkbox" name="delegue" value="1"<?= !empty($saisie['delegue']) ? ' checked' : '' ?>><span>Délégué de promotion<small>Peut faire l'appel et signaler un comportement pour sa promotion.</small></span></label>
                </div>
            </div>
            <div class="dashboard-card">
                <h3>Photo</h3>
                <div class="formulaire">
                    <?php if ($modification && $etudiant['PHOTO']): ?><div class="avatar avatar-profil" style="margin: 0 0 6px;"><img src="<?= htmlspecialchars($etudiant['PHOTO']) ?>" alt=""></div><?php endif; ?>
                    <div class="champ"><?= Composant::champFichier('photo', 'photo', '.jpg,.jpeg,.png,.webp', 'jpg, png ou webp, 10 Mo max' . ($modification ? ', remplace la photo actuelle' : '')) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="actions" style="margin: -10px 0 30px;">
        <button type="submit" class="btn btn-principal"><?= Icone::svg('valide', 16) ?> <?= $modification ? 'Enregistrer les modifications' : "Créer l'étudiant" ?></button>
    </div>
</form>
