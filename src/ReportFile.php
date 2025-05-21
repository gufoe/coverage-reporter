<?php

namespace CoverageReporter;

use CoverageReporter\ReportNode;
use CoverageReporter\PathUtils;

class ReportFile implements ReportNode
{
    public string $path;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return file_get_contents($this->path);
    }

    /**
     * @param array<string, array<int, int>> $coverageData
     * @return CoverageSummary
     */
    public function getSummary(array $coverageData): CoverageSummary
    {
        if (!isset($coverageData[$this->path])) {
            // For files not in coverage data, return -1 for total to indicate N/A
            return new CoverageSummary(0, -1, 0, 1, -1, 0);
        }

        $coverage = $coverageData[$this->path];

        // Filter out dead code lines (-2)
        $coverage = array_filter($coverage, fn($count) => $count !== -2);

        $totalLines = count($coverage);
        $executedLines = count(array_filter($coverage, fn($line) => $line !== -1));
        $coveragePercent = $totalLines > 0 ? ($executedLines / $totalLines) * 100 : 0;

        // A file is considered covered if it has any executed lines
        $fileCoverage = $executedLines > 0 ? 100.0 : 0.0;

        return new CoverageSummary($coveragePercent, $totalLines, $executedLines, 1, $totalLines, $fileCoverage);
    }

    /**
     * @param array<string, array<int, int>> $coverageData
     * @return array<int, array{content: string, status: string}>
     */
    public function getLineCoverage(array $coverageData): array
    {
        $fileLines = explode("\n", $this->getSource());
        $coverage = $coverageData[$this->path] ?? [];
        $result = [];

        // Convert to 1-based indexing
        foreach ($fileLines as $i => $line) {
            $lineNumber = $i + 1;  // Convert to 1-based
            $status = 'neutral';

            if (array_key_exists($lineNumber, $coverage)) {
                $count = $coverage[$lineNumber];
                if ($count > 0) {
                    $status = 'executed';  // Green: line was executed
                } else {
                    $status = 'not-executed';  // Red: line was not executed (count = 0 or -1)
                }
            }

            $result[$lineNumber] = [  // Use 1-based line number as key
                'content' => $line,
                'status' => $status
            ];
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getBreadcrumbName(): string
    {
        return PathUtils::basename($this->path);
    }

    /**
     * @param array<string, array<int, int>> $coverageData
     * @return ReportNodeData
     */
    public function toNodeData(array $coverageData): ReportNodeData
    {
        return new ReportNodeData(
            PathUtils::basename($this->path),
            $this->getSummary($coverageData),
            null,
            $coverageData[$this->path] ?? []
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
