<div class="dashboard-header">
    <div>
        <h2>Importer des étudiants</h2>
        <p>Un fichier CSV, une ligne par étudiant. Le fichier est contrôlé avant toute création : si une ligne est en erreur, rien n'est enregistré.</p>
    </div>
    <div class="actions">
        <a href="index.php?action=admin_etudiants_import&modele=1" class="btn btn-secondaire"><?= Icone::svg('document', 16) ?> Télécharger le modèle</a>
        <a href="index.php?action=admin_etudiants" class="btn btn-secondaire"><?= Icone::svg('retour', 16) ?> Liste</a>
    </div>
</div>

<div class="grille-2">
    <div class="dashboard-card">
        <h3>Fichier</h3>
        <form method="post" action="index.php?action=admin_etudiants_import" enctype="multipart/form-data" class="formulaire">
            <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
            <div class="champ"><?= Composant::champFichier('fichier', 'fichier', '.csv,.txt', 'CSV, séparateur point-virgule ou virgule, encodage UTF-8', false, true) ?></div>
            <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('oeil', 16) ?> Contrôler le fichier</button></div>
        </form>
    </div>
    <div class="dashboard-card">
        <h3>Colonnes attendues</h3>
        <div class="defilement"><table class="activity-table">
            <thead><tr><th>Colonne</th><th>Contenu</th></tr></thead>
            <tbody>
                <tr><td data-label="Colonne"><code>nom</code>, <code>prenom</code></td><td data-label="Contenu">obligatoires</td></tr>
                <tr><td data-label="Colonne"><code>email</code></td><td data-label="Contenu">obligatoire, unique</td></tr>
                <tr><td data-label="Colonne"><code>telephone</code></td><td data-label="Contenu">obligatoire</td></tr>
                <tr><td data-label="Colonne"><code>sexe</code></td><td data-label="Contenu">M ou F</td></tr>
                <tr><td data-label="Colonne"><code>date_naissance</code></td><td data-label="Contenu">AAAA-MM-JJ ou JJ/MM/AAAA</td></tr>
                <tr><td data-label="Colonne"><code>adresse</code></td><td data-label="Contenu">facultative</td></tr>
                <tr><td data-label="Colonne"><code>code_promo</code></td><td data-label="Contenu">code d'une promotion existante, ex. L1-GI-2026</td></tr>
            </tbody>
        </table></div>
    </div>
</div>

<?php if ($apercu !== null): ?>
<div class="dashboard-card">
    <div class="entete-carte">
        <h3>Aperçu : <?= count($apercu['lignes']) ?> ligne<?= count($apercu['lignes']) > 1 ? 's' : '' ?>, <?= count($apercu['erreurs']) ?> en erreur</h3>
        <?php if (empty($apercu['erreurs'])): ?>
        <form method="post" action="index.php?action=admin_etudiants_import">
            <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>"><input type="hidden" name="confirmer" value="1">
            <button type="submit" class="btn btn-principal"><?= Icone::svg('valide', 16) ?> Créer les <?= count($apercu['lignes']) ?> comptes</button>
        </form>
        <?php endif; ?>
    </div>
    <?php if (!empty($apercu['erreurs'])): ?>
        <div class="alerte alerte-erreur"><?= Icone::svg('erreur', 16) ?><span>Corrigez les lignes en erreur puis contrôlez à nouveau le fichier. Aucun compte n'a été créé.</span></div>
    <?php endif; ?>
    <div class="defilement"><table class="activity-table">
        <thead><tr><th>Ligne</th><th>Étudiant</th><th>Email</th><th>Promotion</th><th>Contrôle</th></tr></thead>
        <tbody>
        <?php foreach ($apercu['lignes'] as $l): $erreur = $apercu['erreurs'][$l['numero']] ?? null; ?>
            <tr>
                <td data-label="Ligne"><?= (int)$l['numero'] ?></td>
                <td data-label="Étudiant"><?= htmlspecialchars($l['nom'] . ' ' . $l['prenom']) ?></td>
                <td data-label="Email"><?= htmlspecialchars($l['email']) ?></td>
                <td data-label="Promotion"><?= htmlspecialchars($l['code_promo']) ?></td>
                <td data-label="Contrôle"><?= $erreur ? '<span class="badge badge-rouge">' . htmlspecialchars($erreur) . '</span>' : '<span class="badge badge-vert">Prêt</span>' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>
