<?php

namespace CoverageReporter;

use CoverageReporter\CoverageSummary;
use CoverageReporter\PathUtils;
use CoverageReporter\JsonNode;

interface ReportNode
{
    public function getSummary(array $coverageData): CoverageSummary;
    public function toNodeData(array $coverageData): ReportNodeData;
    public function getPath(): string;
}

class ReportDirectory implements ReportNode
{
    private string $path;
    /** @var array<string, ReportDirectory> Directories in this directory indexed by basename */
    public array $directories = [];
    /** @var array<string, ReportFile> PHP files in this directory indexed by basename */
    public array $files = [];

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->files) && empty($this->directories);
    }

    /**
     * @return void
     */
    public function autoFill(): void
    {
        // echo "\n\nautoFill: " . $this->path . "\n";
        foreach (glob($this->path . '/*') as $entry) {
            // echo "entry: $entry\n";
            if (is_file($entry)) {
                $this->files[basename($entry)] = new ReportFile($entry);
            } elseif (is_dir($entry)) {
                $dir = new ReportDirectory($entry);
                $dir->autoFill();
                $this->directories[basename($entry)] = $dir;
            }
        }
    }

    /**
     * @param string $path
     * @return string
     */
    private function validatePath(string $path): string
    {
        $path = realpath($path);
        if (!PathUtils::startsWith($path, $this->path)) {
            throw new \Exception("Path $path is not in directory $this->path");
        }
        return $path;
    }

    /**
     * @param string $file
     * @return ReportFile
     */
    public function addFile(string $file): ReportFile
    {
        $file = $this->validatePath($file);
        if (!is_file($file)) {
            throw new \Exception("File $file does not exist");
        }

        $file_dir = PathUtils::dirname($file);
        if ($file_dir === $this->path) {
            $ReportFile = new ReportFile($file);
            $this->files[PathUtils::basename($file)] = $ReportFile;
            return $ReportFile;
        }
        $dir = $this->addDirectory($file_dir);
        return $dir->addFile($file);
    }

    /**
     * @param string $directory
     * @return ReportDirectory
     */
    public function addDirectory(string $directory): ReportDirectory
    {
        $directory = $this->validatePath($directory);
        if (PathUtils::dirname($directory) === $this->path) {
            if (!isset($this->directories[PathUtils::basename($directory)])) {
                $coverageDir = new ReportDirectory($directory);
                $this->directories[PathUtils::basename($directory)] = $coverageDir;
            }
            return $this->directories[PathUtils::basename($directory)];
        }
        $parentDir = PathUtils::dirname($directory);
        if ($parentDir === $this->path || $parentDir === '' || $parentDir === $directory) {
            return $this;
        }
        $parent = $this->addDirectory($parentDir);
        return $parent->addDirectory($directory);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Calculate coverage percentage
     * @param int $total Total items
     * @param int $executed Executed items
     * @return float|null Coverage percentage or null if no items
     */
    private function calculateCoverage(int $total, int $executed): ?float
    {
        if ($total <= 0) return null;
        return ($executed / $total) * 100;
    }

    /**
     * Add summary data to totals
     * @param CoverageSummary $summary Summary to add
     * @param int &$totalFiles Total files counter
     * @param int &$testedFiles Tested files counter
     * @param int &$totalLines Total lines counter
     * @param int &$totalExecuted Executed lines counter
     */
    private function addSummaryData(
        CoverageSummary $summary,
        int &$totalFiles,
        int &$testedFiles,
        int &$totalLines,
        int &$totalExecuted
    ): void {
        $totalFiles += $summary->files;
        $testedFiles += $summary->fileCoverage > 0 ? round($summary->files * $summary->fileCoverage / 100) : 0;
        $totalLines += $summary->total > 0 ? $summary->total : 0;
        $totalExecuted += $summary->executed > 0 ? $summary->executed : 0;
    }

    /**
     * @param array<string, array<int, int>> $coverageData
     * @return CoverageSummary
     */
    public function getSummary(array $coverageData): CoverageSummary
    {
        $totalFiles = 0;
        $testedFiles = 0;
        $totalLines = 0;
        $totalExecuted = 0;

        // Count files in this directory
        foreach ($this->files as $file) {
            $this->addSummaryData(
                $file->getSummary($coverageData),
                $totalFiles,
                $testedFiles,
                $totalLines,
                $totalExecuted
            );
        }

        // Add files and lines from subdirectories
        foreach ($this->directories as $dir) {
            $this->addSummaryData(
                $dir->getSummary($coverageData),
                $totalFiles,
                $testedFiles,
                $totalLines,
                $totalExecuted
            );
        }

        // Calculate coverage percentages
        $coverage = $testedFiles === 0 ? null : $this->calculateCoverage($totalLines, $totalExecuted);
        $fileCoverage = $this->calculateCoverage($totalFiles, $testedFiles);

        // Reset line counts if no files are tested
        if ($testedFiles === 0) {
            $totalLines = null;
            $totalExecuted = null;
        }

        return new CoverageSummary($coverage, $totalLines, $totalExecuted, $totalFiles, $testedFiles, $fileCoverage);
    }

    /**
     * @param array<string, array<int, int>> $coverageData
     * @return ReportNodeData
     */
    public function toNodeData(array $coverageData): ReportNodeData
    {
        $children = [];
        $addChild = function(ReportNode $node) use (&$children, $coverageData) {
            $children[] = $node->toNodeData($coverageData);
        };

        array_map($addChild, $this->directories);
        array_map($addChild, $this->files);

        return new ReportNodeData(
            PathUtils::basename($this->path),
            $this->getSummary($coverageData),
            $children
        );
    }

    /**
     * @param string $rootPath
     * @param array<string, array<int, int>> $coverageData
     * @return array<int, array{object: object, url: string, summary: JsonNode}>
     */
    public function getAllItems(string $rootPath, array $coverageData): array
    {
        $createItem = function(ReportNode $node, string $url) use ($coverageData) {
            return [
                'object' => $node,
                'url' => $url,
                'summary' => $node->toNodeData($coverageData)
            ];
        };

        $dirItems = array_merge(...array_map(
            fn($subdir) => array_merge(
                [$createItem($subdir, PathUtils::directoryIndex($subdir->path, $rootPath))],
                $subdir->getAllItems($rootPath, $coverageData)
            ),
            $this->directories
        ));

        $fileItems = array_map(
            fn($file) => $createItem($file, PathUtils::fileHtml($file->path, $rootPath)),
            $this->files
        );

        return array_merge($dirItems, $fileItems);
    }
}
