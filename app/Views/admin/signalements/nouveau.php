<div class="dashboard-header">
    <div>
        <h2>Nouveau signalement</h2>
        <p>Le dossier est transmis au chargé de discipline. Pour un fait qui concerne plusieurs étudiants, sélectionnez-les tous : un dossier est ouvert pour chacun.</p>
    </div>
    <a href="index.php?action=admin_signalements" class="btn btn-secondaire"><?= Icone::svg('retour', 16) ?> Annuler</a>
</div>

<form method="post" action="index.php?action=admin_signalement_nouveau" enctype="multipart/form-data">
    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
    <div class="grille-2">
        <div class="dashboard-card">
            <h3>Étudiants concernés</h3>
            <div class="formulaire">
                <div class="champ">
                    <label for="promo">Promotion</label>
                    <select name="promo" id="promo" onchange="this.form.method='get'; this.form.action='index.php'; this.form.submit();">
                        <?php foreach ($promotions as $p): ?><option value="<?= (int)$p['ID_PROMO'] ?>"<?= (int)($saisie['promo'] ?? 0) === (int)$p['ID_PROMO'] ? ' selected' : '' ?>><?= htmlspecialchars($p['CODE_PROMO'] . ', ' . $p['LIBELLE_NIVEAU'] . ' ' . $p['NOM_FILIERE']) ?></option><?php endforeach; ?>
                    </select>
                    <input type="hidden" name="action" value="admin_signalement_nouveau">
                </div>
                <div class="champ">
                    <label for="recherche-etudiant">Rechercher un étudiant</label>
                    <input type="search" id="recherche-etudiant" class="recherche" placeholder="Nom, prénom ou matricule" autocomplete="off" data-selecteur="selecteur-etudiants">
                    <div class="selecteur" id="selecteur-etudiants">
                        <?php foreach ($etudiants as $e): ?>
                            <?php $choisi = in_array((int)$e['ID_PERSONNE'], $saisie['etudiants'] ?? [], true); ?>
                            <label class="selecteur-option<?= $choisi ? ' choisi' : '' ?>" data-texte="<?= htmlspecialchars(mb_strtolower($e['NOM'] . ' ' . $e['PRENOM'] . ' ' . $e['MATRICULE'])) ?>">
                                <input type="checkbox" name="etudiants[]" value="<?= (int)$e['ID_PERSONNE'] ?>"<?= $choisi ? ' checked' : '' ?>>
                                <span class="avatar avatar-petit"><?= Icone::svg('personne', 16) ?></span>
                                <span class="selecteur-nom"><strong><?= htmlspecialchars($e['NOM'] . ' ' . $e['PRENOM']) ?></strong><small><?= htmlspecialchars($e['MATRICULE']) ?></small></span>
                                <span class="selecteur-coche"><?= Icone::svg('valide', 18) ?></span>
                            </label>
                        <?php endforeach; ?>
                        <div class="selecteur-vide" hidden>Aucun étudiant de cette promotion ne correspond à la recherche.</div>
                    </div>
                    <div class="aide"><?= count($etudiants) ?> étudiant<?= count($etudiants) > 1 ? 's' : '' ?> dans cette promotion. Cochez chaque étudiant concerné.</div>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <h3>Les faits</h3>
            <div class="formulaire">
                <div class="champ">
                    <label for="critere">Critère</label>
                    <select name="critere" id="critere" required>
                        <option value="">Choisir un critère</option>
                        <?php foreach ($criteres as $domaine => $liste): ?>
                            <optgroup label="<?= htmlspecialchars($domaine) ?>">
                                <?php foreach ($liste as $cr): ?>
                                    <option value="<?= (int)$cr['ID_CRITERE'] ?>"<?= (int)($saisie['critere'] ?? 0) === (int)$cr['ID_CRITERE'] ? ' selected' : '' ?>><?= htmlspecialchars($cr['LIBELLE_CRITERE']) ?> (<?= Format::points((float)$cr['VALEUR_POINTS'], true) ?>)</option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="champ">
                    <label for="titre">Objet</label>
                    <input type="text" name="titre" id="titre" required maxlength="100" value="<?= htmlspecialchars($saisie['titre'] ?? '') ?>" placeholder="Ex : Retard répété en cours">
                </div>
                <div class="champ-ligne">
                    <div class="champ">
                        <label for="date_faits">Date et heure des faits</label>
                        <input type="datetime-local" name="date_faits" id="date_faits" required max="<?= date('Y-m-d\TH:i') ?>" value="<?= htmlspecialchars($saisie['date_faits'] ?? date('Y-m-d\TH:i')) ?>">
                    </div>
                    <div class="champ">
                        <label for="lieu">Lieu</label>
                        <input type="text" name="lieu" id="lieu" required maxlength="255" value="<?= htmlspecialchars($saisie['lieu'] ?? '') ?>" placeholder="Ex : Salle B2">
                    </div>
                </div>
                <div class="champ">
                    <label for="description">Description des faits</label>
                    <textarea name="description" id="description" required placeholder="Ce qui s'est passé, dans l'ordre, avec les personnes présentes"><?= htmlspecialchars($saisie['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="grille-2">
        <div class="dashboard-card">
            <h3>Témoins</h3>
            <p class="aide" style="margin-bottom: 14px;">Facultatif. Les témoins peuvent être entendus pendant l'instruction.</p>
            <div class="formulaire">
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="champ-ligne temoin-ligne">
                        <div class="champ"><label for="temoin_nom_<?= $i ?>">Nom</label><input type="text" name="temoin_nom[]" id="temoin_nom_<?= $i ?>" maxlength="50"></div>
                        <div class="champ"><label for="temoin_prenom_<?= $i ?>">Prénom</label><input type="text" name="temoin_prenom[]" id="temoin_prenom_<?= $i ?>" maxlength="50"></div>
                        <div class="champ"><label for="temoin_contact_<?= $i ?>">Contact</label><input type="text" name="temoin_contact[]" id="temoin_contact_<?= $i ?>" maxlength="50" placeholder="Téléphone ou email"></div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="dashboard-card">
            <h3>Preuves</h3>
            <div class="formulaire">
                <div class="champ">
                    <?= Composant::champFichier('preuves[]', 'preuves', '.pdf,.jpg,.jpeg,.png,.webp,.mp4', 'Photos, vidéos ou documents : pdf, jpg, png, webp, mp4, 10 Mo par fichier', true) ?>
                </div>
                <div class="aide">Les preuves ne sont consultables que par vous et par les personnes chargées de l'instruction.</div>
            </div>
        </div>
    </div>

    <div class="actions" style="margin: -10px 0 30px;">
        <button type="submit" class="btn btn-principal"><?= Icone::svg('envoyer', 16) ?> Transmettre le signalement</button>
    </div>
</form>
