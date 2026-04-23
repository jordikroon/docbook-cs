<?php

declare(strict_types=1);

namespace DocbookCS\Sniff;

use DocbookCS\Report\Violation;

/**
 * A sniff receives a DOMDocument (already loaded) and the file path,
 * then returns zero or more Violation value objects.
 */
interface SniffInterface
{
    /**
     * Unique, human-readable code for this sniff (e.g. "DocbookCS.MySniff").
     */
    public function getCode(): string;

    /**
     * Apply the sniff to the given document.
     *
     * @return list<Violation>
     */
    public function process(\DOMDocument $document, string $content, string $filePath): array;

    /**
     * Accept a key/value property from the configuration.
     */
    public function setProperty(string $name, string $value): void;
}
