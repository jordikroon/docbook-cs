<?php

declare(strict_types=1);

namespace DocbookCS\Tests\Unit\Runner;

use DocbookCS\Report\FileReport;
use DocbookCS\Report\Violation;
use DocbookCS\Runner\XmlFileProcessor;
use DocbookCS\Runner\EntityPreprocessor;
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
}
