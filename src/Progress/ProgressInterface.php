<?php

declare(strict_types=1);

namespace DocbookCS\Progress;

interface ProgressInterface
{
    public function start(int $totalFiles): void;

    public function advance(int $current, string $filePath, int $violations): void;

    public function finish(): void;
}
