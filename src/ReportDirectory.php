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
    /**
     * The absolute path to this directory
     */
    private string $path;

    /**
     * Directories in this directory, indexed by their basename
     * Example: ['app' => ReportDirectory('/path/to/app')]
     * Note: This means we can only have one directory with a given name at each level
     */
    public array $directories = [];

    /**
     * PHP files in this directory, indexed by their basename
     * Example: ['Test.php' => ReportFile('/path/to/Test.php')]
     */
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
     * Recursively fills this directory with all files and subdirectories found
     * This is used when you want to include everything in a directory
     */
    public function autoFill(): void
    {
        foreach (glob($this->path . '/*') as $entry) {
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
     * Validates and normalizes a path:
     * 1. Converts relative paths to absolute using the current directory as base
     * 2. Verifies the path exists
     * 3. Ensures the path is within this directory's scope
     *
     * @param string $path
     * @return string The normalized absolute path
     * @throws \Exception if path is invalid or outside this directory
     */
    private function validatePath(string $path): string
    {
        // Convert to absolute path if it's not already
        if (!str_starts_with($path, '/')) {
            $path = realpath($this->path . '/' . $path);
            if ($path === false) {
                throw new \Exception("Path $path does not exist");
            }
        } else {
            $path = realpath($path);
            if ($path === false) {
                throw new \Exception("Path $path does not exist");
            }
        }

        if (!PathUtils::startsWith($path, $this->path)) {
            throw new \Exception("Path $path is not in directory $this->path");
        }
        return $path;
    }

    /**
     * Adds a file to this directory structure:
     * 1. If the file is directly in this directory, adds it here
     * 2. If the file is in a subdirectory, recursively creates the directory structure
     *
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
     * Adds a directory to this directory structure:
     * 1. If the directory is directly in this directory, adds it here
     * 2. If the directory is nested, recursively creates the parent directories
     *
     * The directory structure is maintained using basename indexing at each level,
     * which means we can only have one directory with a given name at each level.
     * This is why nested directories with the same name (like /app/app/) need to be
     * handled carefully through the recursive structure.
     *
     * Directory structure example:
     * /app/                  -> stored in root's directories as 'app'
     *   /app/app/           -> stored in first app's directories as 'app'
     *     /app/app/app/     -> stored in second app's directories as 'app'
     *
     * @param string $directory
     * @return ReportDirectory
     */
    public function addDirectory(string $directory): ReportDirectory
    {
        // Ensure $directory is a validated, absolute path.
        // $this->validatePath also ensures $directory is within $this->path's scope.
        $directory = $this->validatePath($directory);

        // If $directory is the same as $this->path, we're adding the directory to itself.
        // In this context, it means we should operate on this object.
        if ($directory === $this->path) {
            return $this;
        }

        $parentOfTargetDir = PathUtils::dirname($directory);
        $baseOfTargetDir = PathUtils::basename($directory);

        // Case 1: $directory is a direct child of $this->path.
        // Example: $this->path = '/app', $directory = '/app/app'.
        // Here, $parentOfTargetDir ('/app') === $this->path ('/app').
        if ($parentOfTargetDir === $this->path) {
            if (!isset($this->directories[$baseOfTargetDir])) {
                $this->directories[$baseOfTargetDir] = new ReportDirectory($directory);
            }
            return $this->directories[$baseOfTargetDir];
        }

        // Case 2: $directory is a deeper descendant.
        // Example: $this->path = '/app', $directory = '/app/foo/bar'.
        // We need to find or create the first part of the relative path (e.g., 'foo')
        // and then recursively call addDirectory on that child.

        // Relative path from $this->path to $directory
        // Example: $this->path = '/app', $directory = '/app/foo/bar' -> $relativePath = 'foo/bar'
        // Ensure $this->path ends with a slash for correct substr, or handle root '/' case.
        $normalizedBase = rtrim($this->path, '/') . '/';
        if ($this->path === '/') { // Root directory case
            $normalizedBase = '/';
        }

        // Check if $directory truly starts with $normalizedBase to prevent substr errors
        if (!PathUtils::startsWith($directory, $normalizedBase)) {
             // This should not happen if validatePath works correctly and $directory is deeper.
             // But as a safeguard:
             throw new \Exception("Cannot determine relative path for nesting: $directory is not under $normalizedBase");
        }

        $relativePath = substr($directory, strlen($normalizedBase));
        $parts = explode('/', $relativePath);
        $firstPart = $parts[0];

        if (empty($firstPart)) {
             // This could happen if $directory is effectively $this->path after normalization, e.g. $this->path='/app', $directory='/app/'
             // Should have been caught by $directory === $this->path earlier if paths are perfectly canonical.
             // Or if $relativePath was empty due to $directory being $normalizedBase minus a slash.
             return $this; // Or throw an error for unexpected empty firstPart
        }

        // Find or create the first intermediate directory.
        $intermediateDirObject = $this->addDirectory($this->path . '/' . $firstPart);

        // Now, recursively call addDirectory on this intermediate object with the original target.
        // This ensures that $intermediateDirObject (e.g. for '/app/foo') further processes '/app/foo/bar'.
        return $intermediateDirObject->addDirectory($directory);
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
