<?php

declare(strict_types=1);

namespace DocbookCS\Sniff;

/**
 * Detects whitespace and indentation issues in DocBook source files.
 *
 * The following violations are detected:
 * - Trailing whitespace at the end of a line
 * - Spaces used before tabs in indentation
 * - Mixed use of tabs and spaces within indentation
 */
final class WhitespaceSniff extends AbstractSniff
{
    public function getCode(): string
    {
        return 'DocbookCS.Whitespace';
    }

    /** @throws \LogicException if an invalid severity level is configured */
    public function process(\DOMDocument $document, string $content, string $filePath): array
    {
        $lines = explode(PHP_EOL, $content);
        $pattern = '/([ \t]+$)|^(\t* +\t+|\t+ +\t*)|^( +)\t/';

        $violations = [];
        foreach ($lines as $lineNumber => $line) {
            $lineNo = $lineNumber + 1;

            if (preg_match($pattern, $line, $matches)) {
                $message = match (true) {
                    !empty($matches[1]) => 'Trailing whitespace detected.',
                    !empty($matches[2]) || !empty($matches[3]) => 'Mixed tabs and spaces in indentation.',
                    default => 'Inconsistent indentation.', // @codeCoverageIgnore
                };

                $violations[] = $this->createViolation($filePath, $lineNo, $message);
            }
        }

        return $violations;
    }
}
