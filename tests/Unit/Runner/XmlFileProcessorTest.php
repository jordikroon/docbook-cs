<?php

declare(strict_types=1);

namespace DocbookCS\Tests\Unit\Runner;

use DocbookCS\Report\FileReport;
use DocbookCS\Report\Severity;
use DocbookCS\Report\Violation;
use DocbookCS\Runner\XmlFileProcessor;
use DocbookCS\Runner\EntityPreprocessor;
use DocbookCS\Sniff\SniffInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EntityPreprocessor::class)]
#[CoversClass(FileReport::class)]
#[CoversClass(Violation::class)]
#[CoversClass(XmlFileProcessor::class)]
final class XmlFileProcessorTest extends TestCase
{
    #[Test]
    public function itReportsParseErrorForInvalidXml(): void
    {
        $processor = new XmlFileProcessor([]);

        $fileReport = $processor->processString('<broken><unclosed>', 'bad.xml');

        self::assertTrue($fileReport->hasViolations());
        self::assertStringContainsString(
            'XML parse error',
            $fileReport->getViolations()[0]->message,
        );
    }

    #[Test]
    public function itReportsErrorForMissingFile(): void
    {
        $processor = new XmlFileProcessor([]);

        $fileReport = $processor->processFile('/nonexistent/path/file.xml');

        self::assertTrue($fileReport->hasViolations());
        self::assertStringContainsString(
            'Could not read file',
            $fileReport->getViolations()[0]->message,
        );
    }

    #[Test]
    public function itUsesReportPathInsteadOfFilePathWhenProvided(): void
    {
        $processor = new XmlFileProcessor([]);

        $fileReport = $processor->processFile('/nonexistent/path/file.xml', [], 'relative/file.xml');

        self::assertSame('relative/file.xml', $fileReport->filePath);
    }

    #[Test]
    public function itParsesCleanXmlWithNoViolations(): void
    {
        $processor = new XmlFileProcessor([]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<chapter>
  <simpara>Simple text.</simpara>
</chapter>
XML;

        $fileReport = $processor->processString($xml, 'clean.xml');

        self::assertFalse($fileReport->hasViolations());
    }

    #[Test]
    public function itNeutralizesEntitiesBeforeParsing(): void
    {
        $processor = new XmlFileProcessor([]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE chapter SYSTEM "docbook.dtd">
<chapter>
  <simpara>&link.superglobals; are &php.ini; things and &amp; works.</simpara>
</chapter>
XML;

        $fileReport = $processor->processString($xml, 'entity-test.xml');

        $parseErrors = array_filter(
            $fileReport->getViolations(),
            static fn($v) => $v->sniffCode === 'DocbookCS.Internal',
        );

        self::assertCount(0, $parseErrors);
    }

    #[Test]
    public function itAcceptsCustomPreprocessor(): void
    {
        $preprocessor = new EntityPreprocessor('[REPLACED]');
        $processor    = new XmlFileProcessor([], $preprocessor);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<chapter>
  <simpara>&custom.entity; text.</simpara>
</chapter>
XML;

        $fileReport = $processor->processString($xml, 'custom.xml');

        $parseErrors = array_filter(
            $fileReport->getViolations(),
            static fn($v) => $v->sniffCode === 'DocbookCS.Internal',
        );

        self::assertCount(0, $parseErrors);
    }

    #[Test]
    public function itReturnsZeroViolationsForEmptySniffList(): void
    {
        $processor = new XmlFileProcessor([]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<chapter><para>Hello.</para></chapter>
XML;

        $fileReport = $processor->processString($xml, 'empty-sniffs.xml');

        self::assertSame(0, $fileReport->getViolationCount());
    }

    #[Test]
    public function itReturnsAllViolationsWhenNoChangedLinesGiven(): void
    {
        $sniff = $this->makeSniffWithViolationsAtLines([3, 5]);
        $processor = new XmlFileProcessor([$sniff]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<chapter>
  <simpara>line 3</simpara>
  <simpara>line 4</simpara>
  <simpara>line 5</simpara>
</chapter>
XML;

        $fileReport = $processor->processString($xml, 'all.xml');

        self::assertSame(2, $fileReport->getViolationCount());
    }

    #[Test]
    public function itFiltersViolationsToChangedLinesWhenDiffProvided(): void
    {
        $sniff = $this->makeSniffWithViolationsAtLines([3, 5]);
        $processor = new XmlFileProcessor([$sniff]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<chapter>
  <simpara>line 3</simpara>
  <simpara>line 4</simpara>
  <simpara>line 5</simpara>
</chapter>
XML;

        // Only line 3 changed — violation at line 5 should be suppressed.
        $fileReport = $processor->processString($xml, 'filtered.xml', [3]);

        self::assertSame(1, $fileReport->getViolationCount());
        self::assertSame(3, $fileReport->getViolations()[0]->line);
    }

    #[Test]
    public function itIncludesViolationWhenChangedLineIsInsideElement(): void
    {
        // Violation is on the <para> opening tag (line 3), but the changed
        // content is on line 4 (inside the element). The filtering must
        // expand to the parent element's span.
        $sniff = $this->makeSniffWithViolationsAtLines([3]);
        $processor = new XmlFileProcessor([$sniff]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<chapter>
  <para>
    content on line 4
  </para>
</chapter>
XML;

        // Line 3 is the <para> opening; changed line is 4 (inside the element).
        $fileReport = $processor->processString($xml, 'inner.xml', [4]);

        self::assertSame(1, $fileReport->getViolationCount());
        self::assertSame(3, $fileReport->getViolations()[0]->line);
    }

    #[Test]
    public function itSuppressesViolationWhenNoChangedLineIsInsideElement(): void
    {
        // Violation at line 3 (<para>), but changed line is 7 (a different element).
        $sniff = $this->makeSniffWithViolationsAtLines([3]);
        $processor = new XmlFileProcessor([$sniff]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<chapter>
  <para>
    content
  </para>
  <simpara>other element at line 7</simpara>
</chapter>
XML;

        $fileReport = $processor->processString($xml, 'suppress.xml', [7]);

        self::assertSame(0, $fileReport->getViolationCount());
    }

    #[Test]
    public function itKeepsInternalErrorsRegardlessOfChangedLines(): void
    {
        $processor = new XmlFileProcessor([]);

        $fileReport = $processor->processFile('/nonexistent/path/file.xml', [42]);

        self::assertTrue($fileReport->hasViolations());
        self::assertSame('DocbookCS.Internal', $fileReport->getViolations()[0]->sniffCode);
    }

    /** @param list<int> $lines */
    private function makeSniffWithViolationsAtLines(array $lines): SniffInterface
    {
        return new class ($lines) implements SniffInterface {
            /** @param list<int> $lines */
            public function __construct(private readonly array $lines)
            {
            }

            public function getCode(): string
            {
                return 'Test.Stub';
            }

            public function process(\DOMDocument $document, string $content, string $filePath): array
            {
                return array_map(
                    fn(int $line) => new Violation(
                        sniffCode: $this->getCode(),
                        filePath: $filePath,
                        line: $line,
                        message: "violation at line {$line}",
                        severity: Severity::WARNING,
                    ),
                    $this->lines,
                );
            }

            public function setProperty(string $name, string $value): void
            {
            }
        };
    }

    #[Test]
    public function itExpandsViolationSpanThroughDeeplyNestedElements(): void
    {
        $sniff = $this->makeSniffWithViolationsAtLines([3]);
        $processor = new XmlFileProcessor([$sniff]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<chapter>
  <para>
    <emphasis>
      <literal>
        deeply nested content on line 6
      </literal>
    </emphasis>
  </para>
</chapter>
XML;

        $fileReport = $processor->processString($xml, 'deep-nest.xml', [6]);

        self::assertSame(1, $fileReport->getViolationCount());
        self::assertSame(3, $fileReport->getViolations()[0]->line);
    }

    #[Test]
    public function itDoesNotExpandSpanWhenNestedElementEndsBeforeMax(): void
    {
        $sniff = $this->makeSniffWithViolationsAtLines([3]);
        $processor = new XmlFileProcessor([$sniff]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<chapter>
  <para>
    <emphasis>short</emphasis>
    text content stretching to line 5
  </para>
  <simpara>unrelated line 7</simpara>
</chapter>
XML;

        $fileReport = $processor->processString($xml, 'shallow-nest.xml', [7]);

        self::assertSame(0, $fileReport->getViolationCount());
    }
}
