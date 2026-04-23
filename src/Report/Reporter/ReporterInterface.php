<?php

declare(strict_types=1);

namespace DocbookCS\Report\Reporter;

use DocbookCS\Report\Report;

interface ReporterInterface
{
    public function generate(Report $report): string;
}
