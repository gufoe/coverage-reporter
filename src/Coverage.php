<?php

declare(strict_types=1);

namespace CoverageReporter;

use CoverageReporter\Exceptions\CoverageExceptionNotInCoverageMode;
use CoverageReporter\Exceptions\CoverageExceptionAlreadyStarted;
use CoverageReporter\Exceptions\CoverageExceptionNotStarted;
use CoverageReporter\Exceptions\CoverageException;
use CoverageReporter\Reports\CoverageReport;
use CoverageReporter\Reports\FileCoverage;
use CoverageReporter\CoverageSummary;
use CoverageReporter\PathUtils;

class Coverage
{
    private static bool $isStarted = false;

    /**
     * Start collecting code coverage data using Xdebug
     *
     * @return void
     * @throws CoverageExceptionNotInCoverageMode if Xdebug is not in coverage mode
     * @throws CoverageExceptionAlreadyStarted if start() is called multiple times
     */
    public static function start(): void
    {
        if (!self::isCoverageModeEnabled()) {
            throw new CoverageExceptionNotInCoverageMode();
        }
        if (self::$isStarted) {
            throw new CoverageExceptionAlreadyStarted();
        }
        xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
        self::$isStarted = true;
    }

    /**
     * Stop collecting code coverage data and return the collected data
     *
     * @return array The collected coverage data
     * @throws CoverageException if Xdebug is not loaded or not in coverage mode
     */
    public static function stop(): array
    {
        if (!self::$isStarted) {
            throw new CoverageExceptionNotStarted();
        }
        $coverage = xdebug_get_code_coverage();

        // Remove coverage dead code (-2)
        foreach ($coverage as $file => $lines) {
            foreach ($lines as $line => $count) {
                if ($count === -2) {
                    unset($coverage[$file][$line]);
                }
            }
        }
        xdebug_stop_code_coverage();
        self::$isStarted = false;

        return $coverage;
    }

    /**
     * Merge multiple coverage data arrays
     * For overlapping lines, use the maximum execution count
     *
     * @param array ...$coverageArrays The coverage data arrays to merge
     * @return array The merged coverage data
     */
    public static function mergeCoverage(array ...$coverageArrays): array
    {
        $merged = [];
        foreach ($coverageArrays as $coverage) {
            foreach ($coverage as $file => $lines) {
                if (!isset($merged[$file])) {
                    $merged[$file] = $lines;
                    continue;
                }

                foreach ($lines as $line => $count) {
                    if (!isset($merged[$file][$line])) {
                        $merged[$file][$line] = $count;
                    } else {
                        // Use the maximum execution count
                        $merged[$file][$line] = max($merged[$file][$line], $count);
                    }
                }
            }
        }
        return $merged;
    }

    /**
     * Check if coverage collection is running
     *
     * @return bool
     */
    public static function isRunning(): bool
    {
        return self::$isStarted;
    }

    /**
     * Check if Xdebug is in coverage mode
     *
     * @return bool
     */
    private static function isCoverageModeEnabled(): bool
    {
        if (!extension_loaded('xdebug')) {
            return false;
        }

        // Check if Xdebug is in coverage mode through environment variable
        $mode = getenv('XDEBUG_MODE');
        if ($mode !== false && stripos($mode, 'coverage') !== false) {
            return true;
        }

        // Check if Xdebug is in coverage mode through ini setting
        $mode = ini_get('xdebug.mode');
        if ($mode !== false && stripos($mode, 'coverage') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Generate a JSON report from the coverage data
     *
     * @param array $data The coverage data collected by stop()
     * @return ReportNode The JSON report
     */
    public static function generateJsonReport(array $data): ReportNode
    {
        $builder = self::builder('.', $data);
        // Optionally apply filter logic here if needed
        return $builder->buildJsonReport();
    }

    /**
     * Generate an HTML report from the coverage data
     *
     * @param string $folder The folder where the report will be generated
     * @param array $data The coverage data collected by stop()
     * @return void
     */
    public static function generateHtmlReport(string $folder, array $data): void
    {
        $builder = self::builder('.', $data);
        $builder->buildHtmlReport($folder);
    }

    /**
     * Create a new ReportBuilder instance
     *
     * @param string $root The root directory of the coverage data
     * @param array $data The coverage data collected by stop()
     * @return ReportBuilder The new ReportBuilder instance
     */
    static function builder(string $root, array $data): ReportBuilder
    {
        return new ReportBuilder($root, $data);
    }
}
