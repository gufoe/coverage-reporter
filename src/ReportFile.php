<?php

namespace CoverageReporter;

use CoverageReporter\ReportNode;
use CoverageReporter\PathUtils;
use CoverageReporter\SyntheticCoverageGenerator;

class ReportFile implements ReportNode
{
    /**
     * The absolute path to this file
     */
    public string $path;

    /**
     * The coverage data for this file
     * @var array<int, int>
     */
    private array $coverageData = [];

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->initializeSyntheticCoverage();
    }

    /**
     * Initialize synthetic coverage data for all executable lines
     */
    private function initializeSyntheticCoverage(): void
    {
        $generator = new SyntheticCoverageGenerator($this->getSource());
        $this->coverageData = $generator->generate();
    }

    /**
     * Gets the source code of this file
     * @return string
     */
    public function getSource(): string
    {
        $content = file_get_contents($this->path);
        return $content === false ? '' : $content;
    }

    /**
     * Calculates coverage summary for this file:
     * 1. Checks if file has coverage data
     * 2. Counts total and executed lines
     * 3. Calculates coverage percentages
     *
     * Coverage data format:
     * - -1: Not executed
     * - 0+: Number of times executed
     *
     * @return CoverageSummary
     */
    public function getSummary(): CoverageSummary
    {
        // If no coverage data, return 0% coverage
        if (empty($this->coverageData)) {
            return new CoverageSummary(0, 0, 0, 1, 0, 0);
        }

        $totalLines = count($this->coverageData);
        $executedLines = count(array_filter($this->coverageData, fn($count) => $count > 0));
        $coveragePercent = $totalLines > 0 ? ($executedLines / $totalLines) * 100 : 0;

        // A file is considered covered if it has any executed lines
        $fileCoverage = $executedLines > 0 ? 100.0 : 0.0;

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
     * @return array<int, array{content: string, status: string}>
     */
    public function getLineCoverage(): array
    {
        $fileLines = explode("\n", $this->getSource());
        $result = [];

        // Convert to 1-based indexing
        foreach ($fileLines as $i => $line) {
            $lineNumber = $i + 1;  // Convert to 1-based
            $status = 'neutral';

            if (array_key_exists($lineNumber, $this->coverageData)) {
                $count = $this->coverageData[$lineNumber];
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
     * @return ReportNodeData
     */
    public function toNodeData(): ReportNodeData
    {
        return new ReportNodeData(
            PathUtils::basename($this->path),
            $this->getSummary(),
            null,
            $this->coverageData
        );
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Adds coverage data to this file
     * @param array<int, int> $coverageData
     */
    public function addCoverageData(array $coverageData): void
    {
        foreach ($coverageData as $line => $count) {
            $this->coverageData[$line] = isset($this->coverageData[$line]) ? max($this->coverageData[$line], $count) : $count;
        }
    }

    /**
     * Gets the coverage data for this file
     * @return array<int, int>
     */
    public function getCoverageData(): array
    {
        return $this->coverageData;
    }
}
