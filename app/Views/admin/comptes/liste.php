<?php $temporaire = $_SESSION['mot_de_passe_temporaire'] ?? null; unset($_SESSION['mot_de_passe_temporaire']); ?>
<div class="dashboard-header">
    <div>
        <h2>Comptes du personnel</h2>
        <p>Chaque compte reçoit un mot de passe temporaire à changer à la première connexion. Le rôle détermine les permissions.</p>
    </div>
</div>

<?php if ($temporaire): ?>
    <div class="dashboard-card carte-mdp">
        <h3><?= Icone::svg('cle', 18) ?> Mot de passe temporaire de <?= htmlspecialchars($temporaire['nom']) ?></h3>
        <div class="details">
            <div class="detail"><span class="cle">Identifiant</span><span class="val"><?= htmlspecialchars($temporaire['identifiant']) ?></span></div>
            <div class="detail"><span class="cle">Mot de passe</span><span class="val"><code class="mdp"><?= htmlspecialchars($temporaire['mdp']) ?></code></span></div>
        </div>
        <div class="aide" style="margin-top: 10px;">Il ne sera plus affiché : transmettez-le maintenant. Un nouveau mot de passe sera exigé à la première connexion.</div>
    </div>
<?php endif; ?>

<div class="dashboard-card">
    <h3>Nouveau compte</h3>
    <form method="post" action="index.php?action=admin_compte_enregistrer" class="formulaire formulaire-large">
        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
        <div class="grille-champs">
            <div class="champ"><label for="nom">Nom</label><input type="text" name="nom" id="nom" required maxlength="50"></div>
            <div class="champ"><label for="prenom">Prénom</label><input type="text" name="prenom" id="prenom" required maxlength="50"></div>
            <div class="champ"><label for="email">Email</label><input type="email" name="email" id="email" required maxlength="100"></div>
            <div class="champ"><label for="telephone">Téléphone</label><input type="text" name="telephone" id="telephone" required maxlength="100"></div>
            <div class="champ">
                <label for="role">Rôle</label>
                <select name="role" id="role" required>
                    <?php foreach ($roles as $r): ?><option value="<?= (int)$r['ID_ROLE'] ?>"><?= htmlspecialchars($r['LIBELLE_ROLE']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="champ">
                <label for="sexe">Sexe</label>
                <select name="sexe" id="sexe"><option value="M">Masculin</option><option value="F">Féminin</option></select>
            </div>
        </div>
        <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('plus', 16) ?> Créer le compte</button></div>
    </form>
</div>

<div class="dashboard-card">
    <form method="get" action="index.php" class="barre-filtres">
        <input type="hidden" name="action" value="admin_comptes">
        <input type="search" name="q" aria-label="Rechercher" value="<?= htmlspecialchars($q) ?>" placeholder="Nom, prénom ou email" class="filtre-recherche">
        <div class="barre-filtres-actions"><button type="submit" class="btn btn-sombre"><?= Icone::svg('filtre', 15) ?> Filtrer</button></div>
    </form>
    <?php if (empty($comptes)): ?>
        <?= Composant::etatVide('personne', 'Aucun compte', 'Aucun compte du personnel ne correspond à la recherche.') ?>
    <?php else: ?>
        <div class="liste-comptes">
        <?php foreach ($comptes as $c): ?>
            <details class="compte<?= $c['STATUT_COMPTE'] === 'ACTIF' ? '' : ' inactif' ?>">
                <summary>
                    <span class="avatar avatar-petit"><?php if ($c['PHOTO']): ?><img src="<?= htmlspecialchars($c['PHOTO']) ?>" alt=""><?php else: ?><?= Icone::svg('personne', 16) ?><?php endif; ?></span>
                    <span class="compte-nom"><strong><?= htmlspecialchars($c['NOM'] . ' ' . $c['PRENOM']) ?></strong><small><?= htmlspecialchars($c['EMAIL']) ?></small></span>
                    <span class="badge badge-bleu"><?= htmlspecialchars($c['LIBELLE_ROLE']) ?></span>
                    <span class="badge <?= $c['STATUT_COMPTE'] === 'ACTIF' ? 'badge-vert' : 'badge-gris' ?>"><?= $c['STATUT_COMPTE'] === 'ACTIF' ? 'Actif' : 'Inactif' ?></span>
                    <?php if ($c['DOIT_CHANGER_MDP']): ?><span class="badge badge-orange">Mot de passe à changer</span><?php endif; ?>
                    <small class="compte-connexion"><?= $c['DERNIERE_CONNEXION'] ? 'Vu le ' . Format::dateHeure($c['DERNIERE_CONNEXION']) : 'Jamais connecté' ?></small>
                </summary>
                <div class="compte-detail">
                    <form method="post" action="index.php?action=admin_compte_enregistrer" class="formulaire" style="max-width: none;">
                        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                        <input type="hidden" name="id" value="<?= (int)$c['ID_PERSONNE'] ?>">
                        <div class="grille-champs">
                            <div class="champ"><label for="c<?= (int)$c['ID_PERSONNE'] ?>-nom">Nom</label><input type="text" name="nom" id="c<?= (int)$c['ID_PERSONNE'] ?>-nom" value="<?= htmlspecialchars($c['NOM']) ?>" required maxlength="50"></div>
                            <div class="champ"><label for="c<?= (int)$c['ID_PERSONNE'] ?>-prenom">Prénom</label><input type="text" name="prenom" id="c<?= (int)$c['ID_PERSONNE'] ?>-prenom" value="<?= htmlspecialchars($c['PRENOM']) ?>" required maxlength="50"></div>
                            <div class="champ"><label for="c<?= (int)$c['ID_PERSONNE'] ?>-email">Email</label><input type="email" name="email" id="c<?= (int)$c['ID_PERSONNE'] ?>-email" value="<?= htmlspecialchars($c['EMAIL']) ?>" required maxlength="100"></div>
                            <div class="champ"><label for="c<?= (int)$c['ID_PERSONNE'] ?>-telephone">Téléphone</label><input type="text" name="telephone" id="c<?= (int)$c['ID_PERSONNE'] ?>-telephone" value="<?= htmlspecialchars($c['TELEPHONE'] ?? '') ?>" required maxlength="100"></div>
                            <div class="champ">
                                <label for="c<?= (int)$c['ID_PERSONNE'] ?>-role">Rôle</label>
                                <select name="role" id="c<?= (int)$c['ID_PERSONNE'] ?>-role"<?= (int)$c['ID_PERSONNE'] === Auth::idPersonne() ? ' disabled' : '' ?>>
                                    <?php foreach ($roles as $r): ?><option value="<?= (int)$r['ID_ROLE'] ?>"<?= (int)$r['ID_ROLE'] === (int)$c['ID_ROLE'] ? ' selected' : '' ?>><?= htmlspecialchars($r['LIBELLE_ROLE']) ?></option><?php endforeach; ?>
                                </select>
                                <?php if ((int)$c['ID_PERSONNE'] === Auth::idPersonne()): ?><input type="hidden" name="role" value="<?= (int)$c['ID_ROLE'] ?>"><?php endif; ?>
                            </div>
                            <div class="champ">
                                <label for="c<?= (int)$c['ID_PERSONNE'] ?>-sexe">Sexe</label>
                                <select name="sexe" id="c<?= (int)$c['ID_PERSONNE'] ?>-sexe"><option value="M"<?= $c['SEXE'] === 'M' ? ' selected' : '' ?>>Masculin</option><option value="F"<?= $c['SEXE'] === 'F' ? ' selected' : '' ?>>Féminin</option></select>
                            </div>
                        </div>
                        <div class="actions"><button type="submit" class="btn btn-secondaire btn-petit"><?= Icone::svg('valide', 14) ?> Enregistrer</button></div>
                    </form>
                    <?php if ((int)$c['ID_PERSONNE'] !== Auth::idPersonne()): ?>
                    <div class="actions compte-actions">
                        <form method="post" action="index.php?action=admin_compte_reinitialiser" onsubmit="return confirm('Réinitialiser le mot de passe de ce compte ?');">
                            <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>"><input type="hidden" name="id" value="<?= (int)$c['ID_PERSONNE'] ?>">
                            <button type="submit" class="btn btn-secondaire btn-petit"><?= Icone::svg('cle', 14) ?> Réinitialiser le mot de passe</button>
                        </form>
                        <form method="post" action="index.php?action=admin_compte_statut" onsubmit="return confirm('<?= $c['STATUT_COMPTE'] === 'ACTIF' ? 'Désactiver ce compte ?' : 'Réactiver ce compte ?' ?>');">
                            <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>"><input type="hidden" name="id" value="<?= (int)$c['ID_PERSONNE'] ?>">
                            <button type="submit" class="btn btn-fantome btn-petit<?= $c['STATUT_COMPTE'] === 'ACTIF' ? ' btn-danger' : '' ?>"><?= Icone::svg($c['STATUT_COMPTE'] === 'ACTIF' ? 'fermer' : 'recharger', 14) ?> <?= $c['STATUT_COMPTE'] === 'ACTIF' ? 'Désactiver' : 'Réactiver' ?></button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </details>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<details class="dashboard-card carte-repliable">
    <summary><h3>Permissions par rôle</h3><span class="aide">Référence : ce que chaque rôle peut faire.</span></summary>
    <div class="defilement">
        <table class="activity-table table-permissions">
            <thead>
                <tr><th>Permission</th><?php foreach ($roles as $r): ?><th><?= htmlspecialchars($r['LIBELLE_ROLE']) ?></th><?php endforeach; ?></tr>
            </thead>
            <tbody>
            <?php foreach ($permissions as $code => $rolesAutorises): ?>
                <tr>
                    <td data-label="Permission"><?= htmlspecialchars($libelles[$code] ?? $code) ?></td>
                    <?php foreach ($roles as $r): ?>
                        <td data-label="<?= htmlspecialchars($r['LIBELLE_ROLE']) ?>" class="cellule-permission"><?= in_array($r['CODE_ROLE'], $rolesAutorises, true) ? '<span class="permission-oui">' . Icone::svg('valide', 15) . '</span>' : '<span class="permission-non">–</span>' ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="aide" style="margin-top: 10px;">Toute personne désignée responsable d'un club peut en faire l'appel et en gérer les membres, quel que soit son rôle.</div>
</details>
