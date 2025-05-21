<?php
require_once __DIR__ . '/tests/test-files/TestClass1.php';
xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);


$x = new \CoverageReporter\Tests\TestClass1();
if (rand(0, 1) === 0) {
    $x->test();
} else {
    $x->test2();
}

$coverage = xdebug_get_code_coverage();

echo json_encode($coverage, JSON_PRETTY_PRINT);
// echo json_encode(file_get_contents(__DIR__ . '/tests/test-files/TestClass1.php'));
echo "\n\n";
