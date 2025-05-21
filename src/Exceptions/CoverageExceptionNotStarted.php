<?php

declare(strict_types=1);

namespace CoverageReporter\Exceptions;

class CoverageExceptionNotStarted extends CoverageException
{
    public function __construct()
    {
        parent::__construct('Coverage collection must be started before calling stop()');
    }
}
