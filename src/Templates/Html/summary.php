<?php

/**
 * Summary partial for both file and directory views
 *
 * @var string $summaryType Either 'file' or 'directory'
 * @var CoverageReporter\CoverageSummary $summary
 */
?>
<!-- COVERAGE_DEBUG: <?= json_encode([
                            'lines' => $summary->total,
                            'executed_lines' => $summary->executed,
                            'files' => $summary->files,
                            'executed_files' => $summary->fileCoverage > 0 ? round($summary->files * $summary->fileCoverage / 100) : 0,
                            'line_coverage' => $summary->coverage,
                            'file_coverage' => $summary->fileCoverage,
                            'type' => $summaryType
                        ]) ?> -->
<div class="summary">
    <?php if ($summaryType === 'directory'): ?>
        <div class="summary-item">
            <h3>Lines</h3>
            <div class="percentage-group">
                <span class="main-stat <?= $summary->total === null ? 'na' : '' ?>">
                    <?= $summary->total !== null ? ($summary->executed . ' / ' . $summary->total) : 'N/A' ?>
                </span>
                <span class="main-percent <?= $summary->total === null ? 'na' : '' ?>">
                    <?= $summary->total !== null ? number_format($summary->coverage ?? 0, 1) . '%' : 'N/A' ?>
                </span>
            </div>
            <div class="percentage-bar <?= $summary->total !== null ? ($summary->coverage < 50 ? 'danger' : ($summary->coverage < 80 ? 'warning' : '')) : '' ?>">
                <div class="fill" style="width: <?= $summary->coverage ?? 0 ?>%"></div>
            </div>
        </div>
        <div class="summary-item">
            <h3>Files</h3>
            <div class="percentage-group">
                <span class="main-stat <?= $summary->files === null ? 'na' : '' ?>">
                    <?= $summary->files !== null ? (($summary->fileCoverage > 0 ? round($summary->files * $summary->fileCoverage / 100) : 0) . ' / ' . $summary->files) : 'N/A' ?>
                </span>
                <span class="main-percent <?= $summary->files === null ? 'na' : '' ?>">
                    <?= $summary->files !== null ? number_format($summary->fileCoverage ?? 0, 1) . '%' : 'N/A' ?>
                </span>
            </div>
            <div class="percentage-bar <?= $summary->fileCoverage !== null ? ($summary->fileCoverage < 50 ? 'danger' : ($summary->fileCoverage < 80 ? 'warning' : '')) : '' ?>">
                <div class="fill" style="width: <?= $summary->fileCoverage ?? 0 ?>%"></div>
            </div>
        </div>
    <?php elseif ($summaryType === 'file'): ?>
        <div class="summary-item">
            <h3>Total Lines</h3>
            <div class="percentage <?= $summary->total === null ? 'na' : '' ?>"><?= $summary->total !== null ? $summary->total : 'N/A' ?></div>
        </div>
        <div class="summary-item">
            <h3>Executed Lines</h3>
            <div class="percentage <?= $summary->total === null ? 'na' : '' ?>"><?= $summary->total !== null ? $summary->executed : 'N/A' ?></div>
        </div>
    <?php endif; ?>
</div>
