<?php

declare(strict_types=1);

namespace DocbookCS\Runner;

final class EntityPreprocessor
{
    private const array PREDEFINED = ['amp', 'lt', 'gt', 'quot', 'apos'];
    private const string ENTITY_PATTERN = '/&([a-zA-Z_][\w.\-]*);/';

    private string $replacement;

    public function __construct(string $replacement = '')
    {
        $this->replacement = $replacement;
    }

    public function process(string $xmlContent): string
    {
        return $this->neutralize(
            $this->stripDoctype($xmlContent)
        );
    }

    public function neutralize(string $xmlContent): string
    {
        return (string)preg_replace_callback(
            self::ENTITY_PATTERN,
            function (array $matches): string {
                if (in_array($matches[1], self::PREDEFINED, true)) {
                    return $matches[0]; // keep &amp; etc.
                }

                return $this->replacement;
            },
            $xmlContent,
        );
    }

    public function stripDoctype(string $xmlContent): string
    {
        $start = stripos($xmlContent, '<!DOCTYPE');

        if ($start === false) {
            return $xmlContent;
        }

        $length = strlen($xmlContent);
        $pos = $start + 9;
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inBracket = false;

        while ($pos < $length) {
            $char = $xmlContent[$pos];

            if ($char === "'" && !$inDoubleQuote) {
                $inSingleQuote = !$inSingleQuote;
            } elseif ($char === '"' && !$inSingleQuote) {
                $inDoubleQuote = !$inDoubleQuote;
            } elseif ($char === '[' && !$inSingleQuote && !$inDoubleQuote) {
                $inBracket = true;
            } elseif ($char === ']' && !$inSingleQuote && !$inDoubleQuote) {
                $inBracket = false;
            } elseif ($char === '>' && !$inSingleQuote && !$inDoubleQuote && !$inBracket) {
                // Found the end of the DOCTYPE.
                $before = substr($xmlContent, 0, $start);
                $after = substr($xmlContent, $pos + 1);

                return $before . $after;
            }

            $pos++;
        }

        return $xmlContent;
    }
}
