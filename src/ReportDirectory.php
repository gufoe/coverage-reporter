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
     * @param string $file
     * @return ReportFile
     */
    public function addFile(string $file): ReportFile
    {
        $file = realpath($file);
        if (!is_file($file)) {
            throw new \Exception("File $file does not exist");
        }
        if (!PathUtils::startsWith($file, $this->path)) {
            throw new \Exception("File $file is not in directory $this->path");
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
        $directory = realpath($directory);
        if (!PathUtils::startsWith($directory, $this->path)) {
            throw new \Exception("Directory $directory is not in directory $this->path");
        }
        if (PathUtils::dirname($directory) === $this->path) {
            if (!isset($this->directories[PathUtils::basename($directory)])) {
                $coverageDir = new ReportDirectory($directory);
                $this->directories[PathUtils::basename($directory)] = $coverageDir;
            }
            return $this->directories[PathUtils::basename($directory)];
        }
        $parentDir = PathUtils::dirname($directory);
        if ($parentDir === $this->path) {
            return $this;
        }
        if ($parentDir === '' || $parentDir === $directory) {
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
            $totalFiles++;
            $summary = $file->getSummary($coverageData);
            $totalLines += $summary->total > 0 ? $summary->total : 0;
            $totalExecuted += $summary->executed > 0 ? $summary->executed : 0;
            if (!empty($coverageData[$file->path])) {
                $testedFiles++;
            }
        }

        // Add files and lines from subdirectories
        foreach ($this->directories as $dir) {
            $subSummary = $dir->getSummary($coverageData);
            $totalFiles += $subSummary->files;
            $testedFiles += $subSummary->fileCoverage > 0 ? round($subSummary->files * $subSummary->fileCoverage / 100) : 0;
            $totalLines += $subSummary->lines;
            $totalExecuted += $subSummary->executed;
        }

        if ($testedFiles === 0) {
            $totalLines = -1;
            $totalExecuted = -1;
            $coverage = -1;
        } else {
            $coverage = $totalLines > 0 ? ($totalExecuted / $totalLines) * 100 : 0;
        }
        $fileCoverage = $totalFiles > 0 ? ($testedFiles / $totalFiles) * 100 : 0;

        return new CoverageSummary($coverage, $totalLines, $totalExecuted, $totalFiles, $totalLines, $fileCoverage);
    }

    /**
     * @param array<string, array<int, int>> $coverageData
     * @return ReportNodeData
     */
    public function toNodeData(array $coverageData): ReportNodeData
    {
        $children = [];
        foreach ($this->directories as $dir) {
            $children[] = $dir->toNodeData($coverageData);
        }
        foreach ($this->files as $file) {
            $children[] = $file->toNodeData($coverageData);
        }
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
        $dirItems = array_merge(...array_map(
            fn($subdir) => array_merge(
                [[
                    'object' => $subdir,
                    'url' => PathUtils::directoryIndex($subdir->path, $rootPath),
                    'summary' => $subdir->toNodeData($coverageData)
                ]],
                $subdir->getAllItems($rootPath, $coverageData)
            ),
            $this->directories
        ));
        $fileItems = array_map(
            fn($file) => [
                'object' => $file,
                'url' => PathUtils::fileHtml($file->path, $rootPath),
                'summary' => $file->toNodeData($coverageData)
            ],
            $this->files
        );
        return array_merge($dirItems, $fileItems);
    }
}
