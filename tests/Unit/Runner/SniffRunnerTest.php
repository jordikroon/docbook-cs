<?php

declare(strict_types=1);

namespace DocbookCS\Tests\Unit\Runner;

use DocbookCS\Config\ConfigData;
use DocbookCS\Config\SniffEntry;
use DocbookCS\Path\PathLoader;
use DocbookCS\Path\PathMatcher;
use DocbookCS\Progress\NullProgress;
use DocbookCS\Progress\ProgressInterface;
use DocbookCS\Report\FileReport;
use DocbookCS\Report\Report;
use DocbookCS\Report\Severity;
use DocbookCS\Report\Violation;
use DocbookCS\Runner\EntityPreprocessor;
use DocbookCS\Runner\SniffRunner;
use DocbookCS\Runner\XmlFileProcessor;
use DocbookCS\Sniff\SniffInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SniffRunner::class)]
#[CoversClass(ConfigData::class)]
#[CoversClass(PathLoader::class)]
#[CoversClass(PathMatcher::class)]
#[CoversClass(NullProgress::class)]
#[CoversClass(EntityPreprocessor::class)]
#[CoversClass(XmlFileProcessor::class)]
#[CoversClass(Report::class)]
#[CoversClass(SniffEntry::class)]
#[CoversClass(FileReport::class)]
#[CoversClass(Violation::class)]
final class SniffRunnerTest extends TestCase
{
    private const string FIXTURE_DIR = __DIR__ . '/../../fixtures/sniff_runner/default';

    /** @param list<SniffEntry> $sniffs */
    private function createConfig(array $sniffs = []): ConfigData
    {
        return new ConfigData(
            $sniffs,
            [self::FIXTURE_DIR],
            [],
            [],
            self::FIXTURE_DIR,
        );
    }

    #[Test]
    public function itProcessesFilesWithoutViolations(): void
    {
        $config = $this->createConfig();

        $runner = new SniffRunner();
        $report = $runner->run($config);

        self::assertSame(2, $report->getFilesScanned());
        self::assertFalse($report->hasViolations());
        self::assertCount(0, $report->getFileReports());
    }

    #[Test]
    public function itUsesOverridePathsWhenProvided(): void
    {
        $config = $this->createConfig();

        $runner = new SniffRunner();
        $report = $runner->run($config, [self::FIXTURE_DIR . '/../override']);

        self::assertSame(1, $report->getFilesScanned());
    }

    #[Test]
    public function itCallsProgressMethods(): void
    {
        $progress = $this->createMock(ProgressInterface::class);

        $progress->expects($this->once())
            ->method('start')
            ->with(2);

        $progress->expects($this->exactly(2))
            ->method('advance');

        $progress->expects($this->once())
            ->method('finish');

        $config = $this->createConfig();

        $runner = new SniffRunner($progress);
        $runner->run($config);
    }

    #[Test]
    public function itAddsFileReportsForFilesWithViolations(): void
    {
        $sniff = new class implements SniffInterface {
            public function getCode(): string
            {
                return 'Test.ViolatingSniff';
            }

            public function process(\DOMDocument $document, string $content, string $filePath): array
            {
                return [
                    new Violation(
                        sniffCode: 'Test.ViolatingSniff',
                        filePath: $filePath,
                        line: 1,
                        message: 'Test violation message',
                        severity: Severity::WARNING,
                    ),
                ];
            }

            public function setProperty(string $name, string $value): void
            {
            }
        };

        $entry = new SniffEntry($sniff::class);
        $config = $this->createConfig(sniffs: [$entry]);

        $runner = new SniffRunner();
        $report = $runner->run($config);

        self::assertSame(2, $report->getFilesScanned());
        self::assertCount(2, $report->getFileReports());
        self::assertTrue($report->hasViolations());
    }

    #[Test]
    public function itPassesPropertiesToSniffs(): void
    {
        $sniffClass = new class implements SniffInterface {
            public static string $captured = '';

            public function setProperty(string $name, string $value): void
            {
                self::$captured = $value;
            }

            public function getCode(): string
            {
                return 'Test.ConfigurableSniff';
            }

            public function process(\DOMDocument $document, string $content, string $filePath): array
            {
                return [];
            }
        };

        $entry = new SniffEntry($sniffClass::class, ['someProp' => 'someValue']);
        $config = $this->createConfig(sniffs: [$entry]);

        $runner = new SniffRunner();
        $runner->run($config);

        self::assertSame('someValue', $sniffClass::$captured);
    }

    #[Test]
    public function itThrowsWhenSniffClassDoesNotExist(): void
    {
        $entry = new SniffEntry('NonExistent\\FakeSniff', []);
        $config = $this->createConfig(sniffs: [$entry]);

        $runner = new SniffRunner();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $runner->run($config);
    }

    #[Test]
    public function itThrowsWhenClassDoesNotImplementSniffInterface(): void
    {
        $entry = new SniffEntry(\stdClass::class, []);
        $config = $this->createConfig(sniffs: [$entry]);

        $runner = new SniffRunner();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not implement');

        $runner->run($config);
    }
}
