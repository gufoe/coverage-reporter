<?php
/**
 * Directory listing template
 *
 * @var array<int, array{object: object, url: string, summary: CoverageReporter\CoverageSummary}> $items Array of items in the directory with coverage information (each item['summary'] is a CoverageReporter\CoverageSummary)
 * @var string $currentPath The current directory path
 * @var CoverageReporter\CoverageSummary $summary Coverage summary statistics
 * @var string $title
 * @var string $cssPath
 * @var array $breadcrumbs
 */
$summaryType = 'directory';
ob_start();
include __DIR__ . '/summary.php';
?>
<style>
.directory-listing table {
    border-collapse: collapse;
    width: 100%;
    font-size: 0.95em;
}
.directory-listing th, .directory-listing td {
    padding: 4px 8px;
    line-height: 1.2;
}
.directory-listing tr {
    height: 28px;
}
.directory-listing th {
    background: #f8f8f8;
    font-weight: 600;
}
.directory-listing td.filename {
    white-space: nowrap;
    max-width: 320px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.percentage-bar {
    height: 12px;
    min-width: 60px;
}
.percentage {
    font-size: 0.95em;
    margin-left: 6px;
}
</style>
<div class="directory-listing">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Coverage</th>
                <th>Lines</th>
                <th>File Coverage</th>
                <th>Files</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td class="filename">
                        <a href="<?= htmlspecialchars($item['url']) ?>">
                            <?= htmlspecialchars($item['object']->name) ?><?= isset($item['object']->children) && $item['object']->children !== null ? '/' : '' ?>
                        </a>
                    </td>
                    <td>
                        <div class="percentage-bar <?= $item['summary']->coverage < 50 ? 'danger' : ($item['summary']->coverage < 80 ? 'warning' : '') ?>">
                            <div class="fill" style="width: <?= $item['summary']->coverage ?>%"></div>
                        </div>
                        <span class="percentage"><?= $item['summary']->total >= 0 ? number_format($item['summary']->coverage, 1) . '%' : 'N/A' ?></span>
                    </td>
                    <td><?= $item['summary']->total >= 0 ? $item['summary']->total : 'N/A' ?></td>
                    <td>
                        <?php if (isset($item['object']->children)): ?>
                            <div class="percentage-bar <?= $item['summary']->fileCoverage < 50 ? 'danger' : ($item['summary']->fileCoverage < 80 ? 'warning' : '') ?>">
                                <div class="fill" style="width: <?= $item['summary']->fileCoverage ?>%"></div>
                            </div>
                            <span class="percentage"><?= $item['summary']->files > 0 ? number_format($item['summary']->fileCoverage, 1) . '%' : 'N/A' ?></span>
                        <?php else: ?>
                            <!-- blank for files -->
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($item['object']->children)): ?>
                            <?= $item['summary']->files ?>
                        <?php else: ?>
                            <!-- blank for files -->
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
echo $content;
