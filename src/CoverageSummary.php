<?php

namespace CoverageReporter;

/**
 * Represents a summary of code coverage for a file or directory.
 */
class CoverageSummary implements \JsonSerializable
{
    public readonly float $coverage;
    public readonly float $fileCoverage;
    public readonly int $total;
    public readonly int $executed;
    public readonly int $files;
    public readonly int $lines;

    /**
     * @param float $coverage Percentage of lines covered
     * @param int $total Total number of lines
     * @param int $executed Number of executed lines
     * @param int $files Number of files (optional, for directories)
     * @param int $lines Number of lines (optional, for directories)
     * @param float $fileCoverage Percentage of files covered (optional)
     */
    public function __construct(float $coverage, int $total, int $executed, int $files = 0, int $lines = 0, float $fileCoverage = 0.0)
    {
        $this->coverage = $coverage;
        $this->total = $total;
        $this->executed = $executed;
        $this->files = $files;
        $this->lines = $lines;
        $this->fileCoverage = $fileCoverage;
    }

    /**
     * Convert the summary to an associative array.
     *
     * @return array<string, int|float>
     */
    public function toArray(): array
    {
        return [
            'coverage' => $this->coverage,
            'fileCoverage' => $this->fileCoverage,
            'total' => $this->total,
            'executed' => $this->executed,
            'files' => $this->files,
            'lines' => $this->lines,
        ];
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return array<string, int|float>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
