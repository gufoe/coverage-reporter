<?php

namespace CoverageReporter;

use CoverageReporter\ReportNode;
use CoverageReporter\PathUtils;

class ReportFile implements ReportNode
{
    /**
     * The absolute path to this file
     */
    public string $path;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Gets the source code of this file
     * @return string
     */
    public function getSource(): string
    {
        return file_get_contents($this->path);
    }

    /**
     * Calculates coverage summary for this file:
     * 1. Checks if file has coverage data
     * 2. Filters out dead code lines (-2)
     * 3. Counts total and executed lines
     * 4. Calculates coverage percentages
     *
     * Coverage data format:
     * - -2: Dead code (not executable)
     * - -1: Not executed
     * - 0+: Number of times executed
     *
     * @param array<string, array<int, int>> $coverageData
     * @return CoverageSummary
     */
    public function getSummary(array $coverageData): CoverageSummary
    {
        if (!isset($coverageData[$this->path])) {
            // For files not in coverage data, return null for total and executed to indicate N/A
            return new CoverageSummary(0, null, null, 1, 0, 0);
        }

        $coverage = $coverageData[$this->path];

        // Filter out dead code lines (-2)
        $coverage = array_filter($coverage, fn($count) => $count !== -2);

        $totalLines = count($coverage);
        $executedLines = count(array_filter($coverage, fn($line) => $line !== -1));
        $coveragePercent = $totalLines > 0 ? ($executedLines / $totalLines) * 100 : 0;

        // A file is considered covered if it has any executed lines
        $fileCoverage = $executedLines > 0 ? 100.0 : 0.0;

        // If there are no lines to cover, treat as N/A
        if ($totalLines === 0) {
            return new CoverageSummary(0, null, null, 1, 0, 0);
        }

        return new CoverageSummary($coveragePercent, $totalLines, $executedLines, 1, $totalLines, $fileCoverage);
    }

    /**
     * Gets line-by-line coverage information for this file:
     * 1. Splits file into lines
     * 2. Maps each line to its coverage status
     * 3. Returns array with line numbers as keys and coverage info as values
     *
     * Coverage statuses:
     * - 'executed': Line was executed (count > 0)
     * - 'not-executed': Line was not executed (count = 0 or -1)
     * - 'neutral': Line has no coverage data
     *
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
     * Gets the basename of this file for breadcrumb display
     * @return string
     */
    public function getBreadcrumbName(): string
    {
        return PathUtils::basename($this->path);
    }

    /**
     * Converts this file to a node in the report structure:
     * 1. Uses basename as the node name
     * 2. Includes coverage summary
     * 3. Includes raw coverage data for detailed view
     *
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

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
