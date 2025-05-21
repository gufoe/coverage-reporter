<?php

namespace CoverageReporter;

/**
 * ReportNode is a unified, serializable node for both files and directories in coverage reports.
 */
class ReportNodeData implements \JsonSerializable
{
    public readonly string $name;
    public readonly CoverageSummary $summary;
    /** @var null|array<int, ReportNode> */
    public readonly ?array $children;
    /** @var null|array<int, int> */
    public readonly ?array $coverageData;

    /**
     * @param string $name
     * @param CoverageSummary $summary
     * @param null|array<int, ReportNode> $children
     * @param null|array<int, int> $coverageData
     */
    public function __construct(string $name, CoverageSummary $summary, ?array $children = null, ?array $coverageData = null)
    {
        $this->name = $name;
        $this->summary = $summary;
        $this->children = $children;
        $this->coverageData = $coverageData;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'name' => $this->name,
            'summary' => $this->summary,
        ];
        if ($this->children !== null) {
            $data['children'] = $this->children;
        }
        if ($this->coverageData !== null) {
            $data['coverage_data'] = $this->coverageData;
        }
        return $data;
    }
}
