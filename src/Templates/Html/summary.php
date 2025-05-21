<?php
/**
 * Summary partial for both file and directory views
 *
 * @var string $summaryType Either 'file' or 'directory'
 * @var CoverageReporter\CoverageSummary $summary
 */
?>
<div class="summary">
    <div class="summary-item">
        <h3>Line Coverage</h3>
        <div class="percentage"><?= $summary->total >= 0 ? number_format($summary->coverage, 1) . '%' : 'N/A' ?></div>
        <div class="percentage-bar <?= $summary->total >= 0 ? ($summary->coverage < 50 ? 'danger' : ($summary->coverage < 80 ? 'warning' : '')) : '' ?>">
            <div class="fill" style="width: <?= $summary->coverage ?>%"></div>
        </div>
    </div>
    <div class="summary-item">
        <h3>File Coverage</h3>
        <div class="percentage"><?= number_format($summary->fileCoverage, 1) ?>%</div>
        <div class="percentage-bar <?= $summary->fileCoverage < 50 ? 'danger' : ($summary->fileCoverage < 80 ? 'warning' : '') ?>">
            <div class="fill" style="width: <?= $summary->fileCoverage ?>%"></div>
        </div>
    </div>
    <?php if ($summaryType === 'file'): ?>
        <div class="summary-item">
            <h3>Total Lines</h3>
            <div class="percentage"><?= $summary->total >= 0 ? $summary->total : 'N/A' ?></div>
        </div>
        <div class="summary-item">
            <h3>Executed Lines</h3>
            <div class="percentage"><?= $summary->total >= 0 ? $summary->executed : 'N/A' ?></div>
        </div>
    <?php elseif ($summaryType === 'directory'): ?>
        <div class="summary-item">
            <h3>Files</h3>
            <div class="percentage"><?= $summary->files ?></div>
        </div>
        <div class="summary-item">
            <h3>Lines</h3>
            <div class="percentage"><?= $summary->lines >= 0 ? $summary->lines : 'N/A' ?></div>
        </div>
    <?php endif; ?>
</div>
