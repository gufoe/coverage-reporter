<?php

declare(strict_types=1);

namespace CoverageReporter\Exceptions;

class CoverageExceptionNotInCoverageMode extends CoverageException
{
    public function __construct()
    {
        parent::__construct('Xdebug is not in coverage mode. Set XDEBUG_MODE=coverage or xdebug.mode=coverage in your php.ini or run the php command with the -d xdebug.mode=coverage parameter.');
    }
}
