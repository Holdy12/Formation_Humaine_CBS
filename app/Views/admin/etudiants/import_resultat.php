<div class="dashboard-header">
    <div>
        <h2><?= count($crees) ?> compte<?= count($crees) > 1 ? 's' : '' ?> créé<?= count($crees) > 1 ? 's' : '' ?></h2>
        <p>Les mots de passe temporaires ci-dessous ne seront plus affichés. Téléchargez-les maintenant ou notez-les avant de quitter cette page. Chaque étudiant devra choisir un nouveau mot de passe à sa première connexion.</p>
    </div>
    <div class="actions">
        <a href="index.php?action=admin_etudiants_import_resultat&csv=1" class="btn btn-principal"><?= Icone::svg('document', 16) ?> Télécharger en CSV</a>
        <a href="index.php?action=admin_etudiants" class="btn btn-secondaire"><?= Icone::svg('retour', 16) ?> Liste des étudiants</a>
    </div>
</div>

<div class="dashboard-card">
    <div class="defilement"><table class="activity-table">
        <thead><tr><th>Étudiant</th><th>Email</th><th>Matricule</th><th>Mot de passe temporaire</th></tr></thead>
        <tbody>
        <?php foreach ($crees as $c): ?>
            <tr>
                <td data-label="Étudiant"><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></td>
                <td data-label="Email"><?= htmlspecialchars($c['email']) ?></td>
                <td data-label="Matricule"><?= htmlspecialchars($c['matricule']) ?></td>
                <td data-label="Mot de passe"><code><?= htmlspecialchars($c['motDePasse']) ?></code></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
