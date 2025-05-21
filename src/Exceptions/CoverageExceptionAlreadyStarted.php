<?php

declare(strict_types=1);

namespace CoverageReporter\Exceptions;

class CoverageExceptionAlreadyStarted extends CoverageException
{
    public function __construct()
    {
        parent::__construct('Coverage collection already started');
    }
}
