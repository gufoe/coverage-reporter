<?php

declare(strict_types=1);

namespace CoverageReporter\Exceptions;

class CoverageExceptionFileOutsideDirectory extends CoverageException
{
    public function __construct(string $path, string $root)
    {
        parent::__construct("File $path is outside of the root directory $root.");
    }
}
