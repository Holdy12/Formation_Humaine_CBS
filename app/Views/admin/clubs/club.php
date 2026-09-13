<div class="dashboard-header">
    <div>
        <h2><?= htmlspecialchars($club['NOM_CLUB']) ?></h2>
        <p><?= $club['RESP_NOM'] ? 'Animé par ' . htmlspecialchars($club['RESP_PRENOM'] . ' ' . $club['RESP_NOM']) : 'Responsable à désigner' ?>, <?= (int)$club['NB_MEMBRES'] ?> membre<?= (int)$club['NB_MEMBRES'] > 1 ? 's' : '' ?> actif<?= (int)$club['NB_MEMBRES'] > 1 ? 's' : '' ?></p>
    </div>
    <div class="actions">
        <a href="index.php?action=admin_clubs" class="btn btn-secondaire"><?= Icone::svg('retour', 16) ?> Clubs</a>
        <a href="index.php?action=admin_appel&club=<?= (int)$club['ID_CLUB'] ?>" class="btn btn-principal"><?= Icone::svg('appel', 16) ?> Faire l'appel</a>
    </div>
</div>

<div class="grille-2">
    <div class="colonne-profil">
        <div class="dashboard-card">
            <h3>Membres</h3>
            <?php if (empty($membres)): ?>
                <?= Composant::etatVide('groupe', 'Aucun membre', "Ajoutez des étudiants avec le formulaire ci-dessous.") ?>
            <?php else: ?>
                <div class="defilement">
                    <table class="activity-table">
                        <thead><tr><th>Étudiant</th><th>Promotion</th><th>Compte</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($membres as $m): ?>
                            <tr>
                                <td data-label="Étudiant" class="cellule-large"><a href="index.php?action=admin_etudiant&id=<?= (int)$m['ID_PERSONNE'] ?>"><?= htmlspecialchars($m['NOM'] . ' ' . $m['PRENOM']) ?></a><br><small><?= htmlspecialchars($m['MATRICULE']) ?></small></td>
                                <td data-label="Promotion"><?= htmlspecialchars($m['CODE_PROMO']) ?></td>
                                <td data-label="Compte"><span class="badge <?= $m['STATUT_COMPTE'] === 'ACTIF' ? 'badge-vert' : 'badge-gris' ?>"><?= $m['STATUT_COMPTE'] === 'ACTIF' ? 'Actif' : 'Inactif' ?></span></td>
                                <td>
                                    <form method="post" action="index.php?action=admin_club_membre" onsubmit="return confirm('Retirer cet étudiant du club ?');">
                                        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                                        <input type="hidden" name="club" value="<?= (int)$club['ID_CLUB'] ?>">
                                        <input type="hidden" name="etudiant" value="<?= (int)$m['ID_PERSONNE'] ?>">
                                        <input type="hidden" name="op" value="retirer">
                                        <button type="submit" class="btn btn-fantome btn-petit"><?= Icone::svg('fermer', 14) ?> Retirer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card">
            <h3>Ajouter un membre</h3>
            <?php if (empty($candidats)): ?>
                <?= Composant::etatVide('personne', 'Aucun étudiant disponible', "Tous les étudiants actifs appartiennent déjà à un club.") ?>
            <?php else: ?>
                <form method="post" action="index.php?action=admin_club_membre" class="formulaire">
                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                    <input type="hidden" name="club" value="<?= (int)$club['ID_CLUB'] ?>">
                    <div class="champ">
                        <label for="recherche-etudiant">Étudiant sans club</label>
                        <input type="search" id="recherche-etudiant" class="recherche" placeholder="Nom, prénom ou matricule" autocomplete="off" data-selecteur="selecteur-candidats">
                        <div class="selecteur" id="selecteur-candidats">
                            <?php foreach ($candidats as $c): ?>
                                <label class="selecteur-option" data-texte="<?= htmlspecialchars(mb_strtolower($c['NOM'] . ' ' . $c['PRENOM'] . ' ' . $c['MATRICULE'])) ?>">
                                    <input type="radio" name="etudiant" value="<?= (int)$c['ID_PERSONNE'] ?>" required>
                                    <span class="avatar avatar-petit"><?= Icone::svg('personne', 16) ?></span>
                                    <span class="selecteur-nom"><strong><?= htmlspecialchars($c['NOM'] . ' ' . $c['PRENOM']) ?></strong><small><?= htmlspecialchars($c['MATRICULE'] . ', ' . $c['CODE_PROMO']) ?></small></span>
                                    <span class="selecteur-coche"><?= Icone::svg('valide', 18) ?></span>
                                </label>
                            <?php endforeach; ?>
                            <div class="selecteur-vide" hidden>Aucun étudiant ne correspond à la recherche.</div>
                        </div>
                    </div>
                    <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('plus', 16) ?> Ajouter au club</button></div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="colonne-profil">
        <?php if ($peutGerer): ?>
        <div class="dashboard-card">
            <h3>Paramètres du club</h3>
            <form method="post" action="index.php?action=admin_club_enregistrer" class="formulaire">
                <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                <input type="hidden" name="id" value="<?= (int)$club['ID_CLUB'] ?>">
                <div class="champ"><label for="nom">Nom</label><input type="text" name="nom" id="nom" required maxlength="100" value="<?= htmlspecialchars($club['NOM_CLUB']) ?>"></div>
                <div class="champ">
                    <label for="responsable">Responsable</label>
                    <select name="responsable" id="responsable">
                        <option value="">À désigner</option>
                        <?php foreach ($responsables as $r): ?><option value="<?= (int)$r['ID_PERSONNE'] ?>"<?= (int)$club['ID_RESPONSABLE'] === (int)$r['ID_PERSONNE'] ? ' selected' : '' ?>><?= htmlspecialchars($r['NOM'] . ' ' . $r['PRENOM'] . ', ' . $r['LIBELLE_ROLE']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="champ"><label for="description">Présentation</label><textarea name="description" id="description"><?= htmlspecialchars($club['DESCRIPTION'] ?? '') ?></textarea></div>
                <div class="actions"><button type="submit" class="btn btn-secondaire"><?= Icone::svg('valide', 16) ?> Enregistrer</button></div>
            </form>
        </div>
        <?php elseif ($club['DESCRIPTION']): ?>
        <div class="dashboard-card">
            <h3>Présentation</h3>
            <div class="bloc-texte"><?= htmlspecialchars($club['DESCRIPTION']) ?></div>
        </div>
        <?php endif; ?>

        <div class="dashboard-card">
            <h3>Dernières séances</h3>
            <?php if (empty($seances)): ?>
                <?= Composant::etatVide('calendrier', 'Aucune séance', "Les séances du club apparaîtront ici après le premier appel.") ?>
            <?php else: ?>
                <div class="details">
                    <?php foreach ($seances as $s): ?>
                        <div class="detail"><span class="cle"><?= Format::date($s['DATE_SEANCE']) ?></span><span class="val"><?= htmlspecialchars($s['TITRE_SEANCE']) ?><br><small><?= $s['APPEL_FAIT'] ? (int)$s['PRESENTS'] . ' relevé(s)' : 'Appel à faire' ?></small></span></div>
                    <?php endforeach; ?>
                </div>
                <div class="aide" style="margin-top: 10px;"><a href="index.php?action=admin_seances&club=<?= (int)$club['ID_CLUB'] ?>">Toutes les séances du club</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>
