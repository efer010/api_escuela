<?php
declare(strict_types=1);

$page = (int) $result['page'];
$lastPage = (int) $result['last_page'];
$basePath = '/' . $resource;

$pageUrl = static fn (int $target): string => url_with($basePath, current_query(['page' => $target]));

$start = max(1, $page - 2);
$end = min($lastPage, $page + 2);
?>

<div class="pagination">
    <p class="pagination__info">
        Mostrando <?= e((string) $result['from']) ?>–<?= e((string) $result['to']) ?>
        de <?= e((string) $result['total']) ?> registros
    </p>

    <?php if ($lastPage > 1): ?>
        <nav class="pagination__links" aria-label="Paginación">
            <?php if ($page > 1): ?>
                <a class="page-link" href="<?= e($pageUrl($page - 1)) ?>" aria-label="Anterior">←</a>
            <?php else: ?>
                <span class="page-link is-disabled">←</span>
            <?php endif; ?>

            <?php if ($start > 1): ?>
                <a class="page-link" href="<?= e($pageUrl(1)) ?>">1</a>
                <?php if ($start > 2): ?><span class="page-ellipsis">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="page-link is-current" aria-current="page"><?= e((string) $i) ?></span>
                <?php else: ?>
                    <a class="page-link" href="<?= e($pageUrl($i)) ?>"><?= e((string) $i) ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end < $lastPage): ?>
                <?php if ($end < $lastPage - 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
                <a class="page-link" href="<?= e($pageUrl($lastPage)) ?>"><?= e((string) $lastPage) ?></a>
            <?php endif; ?>

            <?php if ($page < $lastPage): ?>
                <a class="page-link" href="<?= e($pageUrl($page + 1)) ?>" aria-label="Siguiente">→</a>
            <?php else: ?>
                <span class="page-link is-disabled">→</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
