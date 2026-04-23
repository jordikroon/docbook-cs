<?php

declare(strict_types=1);

namespace DocbookCS\Report;

enum Severity: string
{
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';
}
