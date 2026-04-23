<?php

declare(strict_types=1);

namespace DocbookCS\Sniff;

use DocbookCS\Report\Severity;
use DocbookCS\Report\Violation;

abstract class AbstractSniff implements SniffInterface
{
    /** @var array<string, string> */
    protected array $properties = [];

    public function setProperty(string $name, string $value): void
    {
        $this->properties[$name] = $value;
    }

    protected function getProperty(string $name, string $default = ''): string
    {
        return $this->properties[$name] ?? $default;
    }

    protected function createViolation(
        string $filePath,
        int $line,
        string $message,
        Severity $severity = Severity::WARNING,
    ): Violation {
        return new Violation(
            sniffCode: $this->getCode(),
            filePath: $filePath,
            line: $line,
            message: $message,
            severity: $severity,
        );
    }
}
