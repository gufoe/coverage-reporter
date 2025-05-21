<?php

namespace CoverageReporter;

use CoverageReporter\Exceptions\CoverageExceptionFileOutsideDirectory;
use CoverageReporter\ReportDirectory;
use CoverageReporter\PathUtils;

class ReportBuilder
{

    /** @var ReportDirectory The root directory */
    private ReportDirectory $root;

    public function __construct(string $root)
    {
        $this->root = new ReportDirectory($root);
    }

    function getRoot(): ReportDirectory
    {
        return $this->root;
    }

    function includeAll(): self
    {
        $this->root->autoFill();
        return $this;
    }

    function includeFile(string $file): self
    {
        $this->root->addFile($file);
        return $this;
    }

    function includeDirectory(string $directory): self
    {
        $dir = $this->root->addDirectory($directory);
        $dir->autoFill();
        return $this;
    }

    /**
     * @param array<string, array<int, int>> $data
     */
    public function addCoverageData(array $data, bool $add_missing = false): self
    {
        foreach ($data as $path => $coverage) {
            $file = $this->root->getFile($path);
            if (!$file) {
                if ($add_missing) {
                    try {
                        $file = $this->root->addFile($path);
                    } catch (CoverageExceptionFileOutsideDirectory $e) {
                        continue;
                    }
                } else {
                    continue;
                }
            }
            $file->addCoverageData($coverage);
        }
        return $this;
    }

    /**
     * Build a HTML report
     * @param string $folder The folder to build the report in
     */
    public function buildHtmlReport(string $folder): void
    {
        $reportDir = rtrim($folder, '/');
        $assetsDir = $reportDir . '/assets/css';
        if (!is_dir($assetsDir)) {
            mkdir($assetsDir, 0777, true);
        }

        // Copy CSS file
        copy(__DIR__ . '/Assets/css/coverage.css', $assetsDir . '/coverage.css');

        $this->generateDirectoryPages($reportDir);
        $this->generateFilePages($reportDir);
        $this->generateIndexPage($reportDir);

    }

    private function generateDirectoryPages(string $reportDir): void
    {
        $this->generateDirectoryPage($this->root, $reportDir);
    }

    private function generateDirectoryPage(ReportDirectory $dir, string $reportDir): void
    {
        $relativeDir = PathUtils::directoryIndex($dir->getPath(), $this->root->getPath());
        // $outputDir is always the directory path (never ending with index.html)
        $outputDir = $reportDir . ($relativeDir === '' ? '' : '/' . dirname($relativeDir));
        $this->ensureDirectory($outputDir);
        $currentOutputPath = $outputDir . '/index.html';

        $cssPath = PathUtils::cssPath($dir->getPath(), $this->root->getPath(), false);

        $dirNode = $dir->toNodeData();
        $items = array_map(function(ReportNodeData $child) use ($outputDir, $currentOutputPath) {
            $targetPath = isset($child->children) && $child->children !== null
                ? $outputDir . '/' . $child->name . '/index.html'
                : $outputDir . '/' . $child->name . '.html';
            return [
                'object' => $child,
                'url' => PathUtils::relativeLink($currentOutputPath, $targetPath),
                'summary' => $child->summary
            ];
        }, $dirNode->children ?? []);

        $content = $this->renderTemplate('directory', [
            'items' => $items,
            'currentPath' => $relativeDir,
            'summary' => $dirNode->summary
        ]);

        $breadcrumbs = PathUtils::breadcrumbs($dir->getPath(), $this->root->getPath(), false);
        foreach ($breadcrumbs as $label => &$url) {
            if ($url !== null) {
                $url = PathUtils::relativeLink($currentOutputPath, $outputDir . '/' . $url);
            }
        }
        $html = $this->renderTemplate('base', [
            'title' => $relativeDir ?: 'Root',
            'content' => $content,
            'breadcrumbs' => $breadcrumbs,
            'cssPath' => $cssPath
        ]);

        file_put_contents($currentOutputPath, $html);

        foreach ($dir->directories as $subdir) {
            $this->generateDirectoryPage($subdir, $reportDir);
        }
    }

    private function generateFilePages(string $reportDir): void
    {
        $this->generateFilePagesRecursive($this->root, $reportDir);
    }

    private function generateFilePagesRecursive(ReportDirectory $dir, string $reportDir): void
    {
        foreach ($dir->files as $file) {
            $filePath = $reportDir . '/' . PathUtils::fileHtml($file->path, $this->root->getPath());
            $this->ensureDirectory(dirname($filePath));

            $cssPath = PathUtils::cssPath($file->path, $this->root->getPath(), true);

            $content = $this->renderTemplate('file', [
                'filename' => PathUtils::basename($file->path),
                'lines' => $file->getLineCoverage(),
                'summary' => $file->getSummary(),
                'coverageData' => $file->getCoverageData()
            ]);

            $currentOutputPath = $filePath;
            $breadcrumbs = PathUtils::breadcrumbs($file->path, $this->root->getPath(), true);
            foreach ($breadcrumbs as $label => &$url) {
                if ($url !== null) {
                    $url = PathUtils::relativeLink($currentOutputPath, dirname($filePath) . '/' . $url);
                }
            }
            $html = $this->renderTemplate('base', [
                'title' => PathUtils::basename($file->path),
                'content' => $content,
                'breadcrumbs' => $breadcrumbs,
                'cssPath' => $cssPath
            ]);

            file_put_contents($filePath, $html);
        }

        foreach ($dir->directories as $subdir) {
            $this->generateFilePagesRecursive($subdir, $reportDir);
        }
    }

    private function generateIndexPage(string $reportDir): void
    {
        $indexPath = $reportDir . '/index.html';
        if (!file_exists($indexPath)) {
            $dirNode = $this->root->toNodeData();
            $currentOutputPath = $indexPath;
            $items = array_map(function($child) use ($reportDir, $currentOutputPath) {
                $targetPath = isset($child->children) && $child->children !== null
                    ? $reportDir . '/' . $child->name . '/index.html'
                    : $reportDir . '/' . $child->name . '.html';
                return [
                    'object' => $child,
                    'url' => PathUtils::relativeLink($currentOutputPath, $targetPath),
                    'summary' => $child->summary
                ];
            }, $dirNode->children ?? []);

            $cssPath = PathUtils::cssPath($this->root->getPath(), $this->root->getPath(), false);

            $content = $this->renderTemplate('directory', [
                'items' => $items,
                'currentPath' => '',
                'summary' => $dirNode->summary
            ]);

            $breadcrumbs = PathUtils::breadcrumbs($this->root->getPath(), $this->root->getPath(), false);
            foreach ($breadcrumbs as $label => &$url) {
                if ($url !== null) {
                    $url = PathUtils::relativeLink($currentOutputPath, $reportDir . '/' . $url);
                }
            }

            $html = $this->renderTemplate('base', [
                'title' => 'Root',
                'content' => $content,
                'breadcrumbs' => $breadcrumbs,
                'cssPath' => $cssPath
            ]);

            file_put_contents($indexPath, $html);
        }
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderTemplate(string $template, array $variables): string
    {
        extract($variables);
        ob_start();
        include __DIR__ . '/Templates/Html/' . $template . '.php';
        return (string)ob_get_clean();
    }

    /**
     * Build a JSON report
     * @return ReportNodeData The JSON report
     */
    public function buildJsonReport(): ReportNodeData
    {
        if ($this->root->isEmpty()) {
            $this->root->autoFill();
        }
        return $this->root->toNodeData();
    }

    /**
     * Ensure a directory exists, creating it if necessary
     */
    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}
