<?php

declare(strict_types=1);

namespace CoverageReporter\Tests;

use PHPUnit\Framework\TestCase;
use CoverageReporter\Coverage;
use CoverageReporter\Exceptions\CoverageExceptionAlreadyStarted;
use CoverageReporter\Exceptions\CoverageExceptionNotStarted;

class CoverageTest extends TestCase
{
    private string $testDir = __DIR__ . '/test-files';

    protected function setUp(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
    }

    private function setupTestFolder(string $name): string
    {
        $dir = __DIR__ . '/../coverage-report/' . $name;
        if (is_dir($dir)) {
            self::removeRecursive($dir);
        }
        mkdir($dir, 0777, true);
        return $dir;
    }

    private static function removeRecursive(string $dir): void
    {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                self::removeRecursive($file);
            } else {
                if (!unlink($file)) {
                    throw new \Exception('Failed to remove file: ' . $file);
                }
            }
        }
        if (!rmdir($dir)) {
            throw new \Exception('Failed to remove directory: ' . $dir);
        }
    }

    protected function tearDown(): void
    {
        // Ensure coverage is stopped after each test
        if (Coverage::isRunning()) {
            Coverage::stop();
        }
        // Do not delete test files or report directories
    }

    public function testCollectThrowsExceptionWhenNotStarted(): void
    {
        $this->expectException(CoverageExceptionNotStarted::class);
        $this->expectExceptionMessage('Coverage collection must be started before calling stop()');
        Coverage::stop();
    }

    public function testStartAndCollectBasicFunctionality(): void
    {
        Coverage::start();

        // Execute some code to generate coverage
        $this->someFunction();

        $coverage = Coverage::stop();

        $this->assertIsArray($coverage);
        $this->assertNotEmpty($coverage);

        // Verify that our test file is in the coverage data
        $testFile = __FILE__;
        $this->assertTrue(array_key_exists($testFile, $coverage), 'Coverage data should contain the test file key');

        // Verify that the coverage data contains line numbers
        $this->assertNotEmpty($coverage[$testFile]);
    }

    public function testMultipleStartCalls(): void
    {
        Coverage::start();
        $this->expectException(CoverageExceptionAlreadyStarted::class);
        $this->expectExceptionMessage('Coverage collection already started');
        Coverage::start(); // Should throw an exception
    }

    public function testGenerateHtmlReport(): void
    {
        // Use static test file
        $testFile = $this->testDir . '/TestClass.php';
        require_once $testFile;

        // Generate coverage data
        Coverage::start();
        $testClass = new TestClass();
        $testClass->test();
        $coverage = Coverage::stop();

        // Create builder and include files
        $builder = Coverage::builder($this->testDir, $coverage);
        $builder->includeFile($testFile);  // Explicitly include the test file

        $report_dir = $this->setupTestFolder('testGenerateHtmlReport');

        // Generate the report
        $builder->buildHtmlReport($report_dir);

        // Verify the report structure
        $this->assertDirectoryExists($report_dir);
        $this->assertDirectoryExists($report_dir . '/assets/css');
        $this->assertFileExists($report_dir . '/assets/css/coverage.css');
        $this->assertFileExists($report_dir . '/index.html');

        // Verify the test file report
        $relativeTestFile = ltrim(str_replace($this->testDir, '', $testFile), '/');
        $testFileReport = $report_dir . '/' . $relativeTestFile . '.html';
        $this->assertFileExists($testFileReport);

        // Verify the report content
        $reportContent = file_get_contents($testFileReport);
        $this->assertStringContainsString('TestClass.php', $reportContent);
        $this->assertStringContainsString('100.0%', $reportContent); // Only the test() method is executed
    }

    public function testGenerateHtmlReportWithFilter(): void
    {
        // Use static test files
        $testFile1 = $this->testDir . '/TestClass1.php';
        $testFile2 = $this->testDir . '/TestClass2.php';
        require_once $testFile1;
        require_once $testFile2;

        // Generate coverage data
        Coverage::start();
        $testClass1 = new TestClass1();
        $testClass2 = new TestClass2();
        $testClass1->test();
        $testClass2->test();
        $coverage = Coverage::stop();

        // Create builder and include only TestClass1
        $builder = Coverage::builder($this->testDir, $coverage);
        $builder->includeFile($testFile1);

        $report_dir = $this->setupTestFolder('testGenerateHtmlReportWithFilter');

        // Generate report
        $builder->buildHtmlReport($report_dir);

        // Verify only TestClass1.php is included
        $relativeTestFile1 = ltrim(str_replace(dirname($testFile1), '', $testFile1), '/');
        $relativeTestFile2 = ltrim(str_replace(dirname($testFile2), '', $testFile2), '/');
        $this->assertFileExists($report_dir . '/' . $relativeTestFile1 . '.html');
        $this->assertFileDoesNotExist($report_dir . '/' . $relativeTestFile2 . '.html');
    }

    public function testGenerateJsonReport(): void
    {
        // Use static test files
        $testFile1 = $this->testDir . '/TestClass1.php';
        $testFile2 = $this->testDir . '/TestClass2.php';
        require_once $testFile1;
        require_once $testFile2;

        // Generate coverage data
        Coverage::start();
        $testClass1 = new TestClass1();
        $testClass2 = new TestClass2();
        $testClass1->test();
        $testClass2->test();
        $coverage = Coverage::stop();

        // Create builder and include files
        $builder = Coverage::builder($this->testDir, $coverage);
        $builder->includeFile($testFile1);
        $builder->includeFile($testFile2);

        // Generate report
        $report = $builder->buildJsonReport();

        // Verify summary
        $summary = $report->summary;
        $this->assertGreaterThan(0, $summary->total);
        $this->assertEquals(2, $summary->files);
        $this->assertGreaterThan(0, $summary->lines);
        $this->assertGreaterThan(0, $summary->executed);

        // Verify files (flatten children)
        $files = array_filter($report->children, fn($node) => $node->coverageData !== null);
        $this->assertCount(2, $files);
        $fileNames = array_map(fn($f) => $f->name, $files);
        $this->assertContains(basename($testFile1), $fileNames);
        $this->assertContains(basename($testFile2), $fileNames);

        // Verify file details
        foreach ($files as $file) {
            $this->assertGreaterThan(0, $file->summary->coverage);
            $this->assertGreaterThan(0, $file->summary->total);
            $this->assertGreaterThan(0, $file->summary->executed);
            $this->assertNotEmpty($file->coverageData);
        }

        // Verify JSON serialization
        $json = json_encode($report, JSON_PRETTY_PRINT);
        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('summary', $decoded);
        $this->assertArrayHasKey('children', $decoded);
        $this->assertCount(2, array_filter($decoded['children'], fn($node) => isset($node['coverage_data'])));
    }

    public function testGenerateJsonReportWithFilter(): void
    {
        // Use static test files
        $testFile1 = $this->testDir . '/TestClass1.php';
        $testFile2 = $this->testDir . '/TestClass2.php';
        require_once $testFile1;
        require_once $testFile2;

        // Generate coverage data
        Coverage::start();
        $testClass1 = new TestClass1();
        $testClass2 = new TestClass2();
        $testClass1->test();
        $testClass2->test();
        $coverage = Coverage::stop();

        // Create builder and include only TestClass1
        $builder = Coverage::builder($this->testDir, $coverage);
        $builder->includeFile($testFile1);

        // Generate report
        $report = $builder->buildJsonReport();

        // Verify summary
        $summary = $report->summary;
        $this->assertGreaterThan(0, $summary->total);
        $this->assertEquals(1, $summary->files);
        $this->assertGreaterThan(0, $summary->lines);
        $this->assertGreaterThan(0, $summary->executed);

        // Verify files (flatten children)
        $files = array_filter($report->children, fn($node) => $node->coverageData !== null);
        $this->assertCount(1, $files);

        // Verify filtered file
        $file = $files[0] ?? null;
        $this->assertNotNull($file, 'First file in files array should not be null');
        $this->assertEquals(basename($testFile1), $file->name);
        $this->assertGreaterThan(0, $file->summary->coverage);
        $this->assertGreaterThan(0, $file->summary->total);
        $this->assertGreaterThan(0, $file->summary->executed);
        $this->assertNotEmpty($file->coverageData);

        // Verify JSON serialization
        $json = json_encode($report, JSON_PRETTY_PRINT);
        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('summary', $decoded);
        $this->assertArrayHasKey('children', $decoded);
        $this->assertCount(1, array_filter($decoded['children'], fn($node) => isset($node['coverage_data'])));
    }

    public function testGenerateHtmlReportWithEmptySubfolders(): void
    {
        // Use pre-created nested subfolders and file
        $baseDir = __DIR__ . '/testdata';
        $withFile = $baseDir . '/nested/empty1/empty2/withFile';
        $filePath = $withFile . '/TestLeaf.php';
        require_once $filePath;

        // Generate coverage data
        Coverage::start();
        $testLeaf = new \CoverageReporter\Tests\TestLeaf();
        $testLeaf->test();
        $coverage = Coverage::stop();

        // Create builder and include the leaf file and subdirectories
        $report_dir = $this->setupTestFolder('testGenerateHtmlReportWithEmptySubfolders');
        $builder = Coverage::builder($baseDir, $coverage);  // Use $baseDir instead of $this->testDir
        $builder->includeAll();
        $builder->buildHtmlReport($report_dir);

        // Check that the report for the file exists
        $fileReport = $report_dir . '/nested/empty1/empty2/withFile/TestLeaf.php.html';
        $this->assertFileExists($fileReport);

        // Check that the index for the root exists
        $this->assertFileExists($report_dir . '/nested/index.html');

        // Check that the directory pages for empty1 and empty2 exist
        $this->assertFileExists($report_dir . '/nested/empty1/index.html');
        $this->assertFileExists($report_dir . '/nested/empty1/empty2/index.html');
        $this->assertFileExists($report_dir . '/nested/empty1/empty2/withFile/index.html');

        // Verify that the TestLeaf.php has 100% coverage
        $fileReport = file_get_contents($fileReport);
        $this->assertStringContainsString('100.0%', $fileReport);
    }

    public function testSimpleReport(): void
    {
        // Use pre-created nested subfolders and file
        $baseDir = __DIR__ . '/test-files';
        $withFile = $baseDir . '/TestClass1.php';
        require_once $withFile;

        // Generate coverage data
        Coverage::start();
        $testClass1 = new TestClass1();
        $testClass1->test();
        $coverage = Coverage::stop();

        $builder = Coverage::builder($baseDir, $coverage);
        $builder->includeAll();
        $report_dir = $this->setupTestFolder('testSimpleReport');
        $builder->buildHtmlReport($report_dir);

        $this->assertFileExists($report_dir . '/TestClass1.php.html');
    }

    private function someFunction(): string
    {
        $a = 1;
        $b = 2;
        return (string)($a + $b);
    }
}
