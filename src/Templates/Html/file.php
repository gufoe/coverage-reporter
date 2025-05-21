<?php
/**
 * File view template
 *
 * @var string $filename The name of the file
 * @var array<int, array{content: string, status: string}> $lines Array of lines with coverage information
 * @var CoverageReporter\CoverageSummary $summary Coverage summary statistics
 * @var string $title
 * @var string $cssPath
 * @var array<string, string|null> $breadcrumbs
 * @var array<int, int> $coverageData Raw coverage data from Xdebug
 */
$summaryType = 'file';
ob_start();
include __DIR__ . '/summary.php';
echo "<!-- COVERAGE_DEBUG: ".json_encode([
    'lines' => $summary->total,
    'executed_lines' => $summary->executed,
    'files' => 1,
    'executed_files' => $summary->executed > 0 ? 1 : 0,
    'line_coverage' => $summary->coverage,
    'file_coverage' => $summary->executed > 0 ? 100 : 0,
    'type' => 'file'
])." -->\n";
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
        </div>
    <?php endforeach; ?>
</div>
<?php
$content = ob_get_clean();
echo $content;
