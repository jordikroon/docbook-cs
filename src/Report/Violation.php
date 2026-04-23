<?php

declare(strict_types=1);

namespace DocbookCS\Report;

final readonly class Violation
{
    public function __construct(
        public string $sniffCode,
        public string $filePath,
        public int $line,
        public string $message,
        public Severity $severity = Severity::WARNING
    ) {
    }
}
