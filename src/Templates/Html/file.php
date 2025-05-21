<?php
/**
 * File view template
 *
 * @var string $filename The name of the file
 * @var array $lines Array of lines with coverage information
 * @var array $summary Coverage summary statistics
 * @var string $title
 * @var string $cssPath
 * @var array $breadcrumbs
 */
$summaryType = 'file';
ob_start();
include __DIR__ . '/summary.php';
?>
<!--
Raw Xdebug Coverage Data:
<?= json_encode($coverageData, JSON_PRETTY_PRINT) ?>
-->
<div class="source-code">
    <?php foreach ($lines as $number => $line): ?>
        <div class="line <?= $line['status'] ?>">
            <span class="line-number"><?= $number ?></span>
            <span class="line-content"><?= htmlspecialchars($line['content']) ?></span>
            <?php if (isset($line['branches'])): ?>
                <span class="branch-info">
                    <?php foreach ($line['branches'] as $branch): ?>
                        <span class="branch <?= $branch ? 'covered' : 'uncovered' ?>"></span>
                    <?php endforeach; ?>
                </span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php
$content = ob_get_clean();
echo $content;
