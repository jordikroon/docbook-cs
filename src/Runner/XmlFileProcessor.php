<?php

declare(strict_types=1);

namespace DocbookCS\Runner;

use DocbookCS\Report\FileReport;
use DocbookCS\Report\Severity;
use DocbookCS\Report\Violation;
use DocbookCS\Sniff\SniffInterface;

final class XmlFileProcessor
{
    /** @var list<SniffInterface> */
    private array $sniffs;

    private EntityPreprocessor $preprocessor;

    /**
     * @param list<SniffInterface>      $sniffs
     * @param EntityPreprocessor|null   $preprocessor
     */
    public function __construct(
        array $sniffs,
        ?EntityPreprocessor $preprocessor = null,
    ) {
        $this->sniffs = $sniffs;
        $this->preprocessor = $preprocessor ?? new EntityPreprocessor();
    }

    public function processFile(string $filePath): FileReport
    {
        $fileReport = new FileReport($filePath);

        $content = @file_get_contents($filePath);
        if ($content === false) {
            $fileReport->addViolation(new Violation(
                sniffCode: 'DocbookCS.Internal',
                filePath: $filePath,
                line: 0,
                message: 'Could not read file.',
                severity: Severity::ERROR,
            ));
            return $fileReport;
        }

        return $this->processContent($content, $filePath, $fileReport);
    }

    public function processString(string $xmlContent, string $pseudoPath = 'input.xml'): FileReport
    {
        $fileReport = new FileReport($pseudoPath);

        return $this->processContent($xmlContent, $pseudoPath, $fileReport);
    }

    private function processContent(string $content, string $filePath, FileReport $fileReport): FileReport
    {
        $content = $this->preprocessor->process($content);

        $document = $this->parseXml($content, $filePath, $fileReport);
        if ($document === null) {
            return $fileReport;
        }

        foreach ($this->sniffs as $sniff) {
            foreach ($sniff->process($document, $content, $filePath) as $violation) {
                $fileReport->addViolation($violation);
            }
        }

        return $fileReport;
    }

    private function parseXml(string $content, string $filePath, FileReport $fileReport): ?\DOMDocument
    {
        $previousUseErrors = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->preserveWhiteSpace = true;

        // LIBXML_NONET prevents network access.
        // No LIBXML_DTDLOAD needed since we stripped the DOCTYPE.
        $loaded = $document->loadXML($content, LIBXML_NONET);

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseErrors);

        if (!$loaded) {
            $message = $errors !== []
                ? trim($errors[0]->message)
                : 'Unknown XML parse error'; // @codeCoverageIgnore

            $fileReport->addViolation(new Violation(
                sniffCode: 'DocbookCS.Internal',
                filePath: $filePath,
                line: $errors !== [] ? $errors[0]->line : 0,
                message: 'XML parse error: ' . $message,
                severity: Severity::ERROR,
            ));
            return null;
        }

        return $document;
    }
}
