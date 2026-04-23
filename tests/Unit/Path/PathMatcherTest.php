<?php

declare(strict_types=1);

namespace DocbookCS\Tests\Unit\Path;

use DocbookCS\Path\PathMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathMatcher::class)]
final class PathMatcherTest extends TestCase
{
    /**
     * @param list<string> $patterns fnmatch()-compatible patterns.
     */
    #[Test]
    #[DataProvider('exclusionProvider')]
    public function itMatchesExclusions(
        array $patterns,
        string $filePath,
        bool $expectedExcluded,
    ): void {
        $matcher = new PathMatcher($patterns);

        self::assertIsList($patterns);
        self::assertSame($expectedExcluded, $matcher->isExcluded($filePath));
        self::assertSame(!$expectedExcluded, $matcher->isIncluded($filePath));
    }

    /** @return iterable<string, array{list<string>, string, bool}> */
    public static function exclusionProvider(): iterable
    {
        yield 'wildcard matches skeleton.xml anywhere' => [
            ['*/skeleton.xml'],
            '/project/doc/reference/skeleton.xml',
            true,
        ];

        yield 'specific subdirectory pattern' => [
            ['reference/*/versions.xml'],
            'reference/strings/versions.xml',
            true,
        ];

        yield 'non-matching pattern leaves file included' => [
            ['*/skeleton.xml'],
            '/project/doc/reference/strlen.xml',
            false,
        ];

        yield 'empty patterns exclude nothing' => [
            [],
            '/any/path.xml',
            false,
        ];

        yield 'multiple patterns - first matches' => [
            ['*/foo.xml', '*/bar.xml'],
            '/project/foo.xml',
            true,
        ];

        yield 'multiple patterns - second matches' => [
            ['*/foo.xml', '*/bar.xml'],
            '/project/bar.xml',
            true,
        ];

        yield 'backslash paths are normalized' => [
            ['*/skeleton.xml'],
            'C:\\project\\doc\\skeleton.xml',
            true,
        ];

        yield 'basename-only match when full path does not match' => [
            ['skeleton.xml'],
            '/project/doc/reference/skeleton.xml',
            true,
        ];
    }
}
