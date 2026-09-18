<?php
// app/Views/admin/roles/creer.php
?>

<div class="dashboard-header">
    <div>
        <h2>Créer un nouveau rôle</h2>
        <p>Ajoutez un nouveau profil de rôle pour le personnel.</p>
    </div>
    <a href="index.php?action=admin_roles" class="btn btn-fantome">
        <?= Icone::svg('retour', 16) ?> Retour à la liste
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="alerte <?= $succes ? 'alerte-succes' : 'alerte-erreur' ?>" role="<?= $succes ? 'status' : 'alert' ?>">
        <?= Icone::svg($succes ? 'valide' : 'erreur', 16) ?>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<div class="dashboard-card">
    <form method="post" action="index.php?action=admin_roles_creer" class="formulaire">
        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton ?? '') ?>">

        <div class="champ">
            <label for="libelle">Libellé du rôle</label>
            <div class="champ-icone">
                <?= Icone::svg('cadenas', 18) ?>
                <input type="text" name="libelle" id="libelle" required maxlength="50" placeholder="Ex: Secrétaire, Surveillant..." autofocus value="<?= htmlspecialchars($_POST['libelle'] ?? '') ?>">
            </div>
        </div>

        <div class="actions-formulaire">
            <button type="submit" class="btn btn-principal">
                <?= Icone::svg('ajouter', 16) ?> Enregistrer le rôle
            </button>
        </div>
    </form>
</div>