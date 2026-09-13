<?php // attend $pagination (page, pages, total, base) ?>
<?php if (($pagination['pages'] ?? 1) > 1): ?>
<nav class="pagination" aria-label="Pagination">
    <span class="pagination-total"><?= (int)$pagination['total'] ?> résultat<?= $pagination['total'] > 1 ? 's' : '' ?></span>
    <div class="pagination-liens">
        <?php if ($pagination['page'] > 1): ?><a href="<?= htmlspecialchars($pagination['base']) ?>&page=<?= $pagination['page'] - 1 ?>" class="btn btn-secondaire btn-petit">Précédent</a><?php endif; ?>
        <span class="pagination-position">Page <?= (int)$pagination['page'] ?> sur <?= (int)$pagination['pages'] ?></span>
        <?php if ($pagination['page'] < $pagination['pages']): ?><a href="<?= htmlspecialchars($pagination['base']) ?>&page=<?= $pagination['page'] + 1 ?>" class="btn btn-secondaire btn-petit">Suivant</a><?php endif; ?>
    </div>
</nav>
<?php endif; ?>
