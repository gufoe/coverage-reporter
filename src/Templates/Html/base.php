<?php
/**
 * Base template for coverage reports
 *
 * @var string $title The page title
 * @var string $content The main content (should include summary)
 * @var array $breadcrumbs The breadcrumb trail
 * @var string $cssPath The path to the CSS file
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Code Coverage Report</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath) ?>">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Code Coverage Report</h1>
            <?php if (!empty($breadcrumbs)): ?>
                <div class="breadcrumb">
                    <?php $last = array_key_last($breadcrumbs); $first = true; ?>
                    <?php foreach ($breadcrumbs as $label => $url): ?>
                        <?php if (!$first): ?> / <?php endif; ?>
                        <?php if ($url): ?>
                            <a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($label) ?>
                        <?php endif; ?>
                        <?php $first = false; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?= $content ?>
    </div>
</body>
</html>
