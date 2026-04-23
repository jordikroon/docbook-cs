<?php

declare(strict_types=1);

namespace DocbookCS\Tests\Unit\Path;

use DocbookCS\Path\PathLoader;
use DocbookCS\Path\PathMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathLoader::class)]
#[CoversClass(PathMatcher::class)]
final class PathLoaderTest extends TestCase
{
    private const string FIXTURE_ROOT = __DIR__ . '/../../fixtures/sample_tree';

    protected function setUp(): void
    {
        if (!is_dir(self::FIXTURE_ROOT)) {
            self::markTestSkipped('Fixture sample_tree not found.');
        }
    }

    #[Test]
    public function itFindsXmlFilesRecursively(): void
    {
        $matcher = new PathMatcher([]);
        $loader = new PathLoader([self::FIXTURE_ROOT], $matcher);

        $files = $loader->loadPaths();

        self::assertNotEmpty($files);

        foreach ($files as $file) {
            self::assertStringEndsWith('.xml', $file);
        }
    }

    #[Test]
    public function itExcludesMatchedFiles(): void
    {
        $matcher = new PathMatcher(['*/skeleton.xml']);
        $loader = new PathLoader([self::FIXTURE_ROOT], $matcher);

        $files = $loader->loadPaths();

        foreach ($files as $file) {
            self::assertStringNotContainsString('skeleton.xml', $file);
        }
    }

    #[Test]
    public function itAcceptsSingleFilePaths(): void
    {
        $singleFile = self::FIXTURE_ROOT . '/language/types.xml';

        if (!is_file($singleFile)) {
            self::markTestSkipped('Fixture file not found.');
        }

        $matcher = new PathMatcher([]);
        $loader = new PathLoader([$singleFile], $matcher);

        $files = $loader->loadPaths();

        self::assertCount(1, $files);
        self::assertStringContainsString('types.xml', $files[0]);
    }

    #[Test]
    public function itIgnoresSingleFileWithWrongExtension(): void
    {
        $txtFile = self::FIXTURE_ROOT . '/readme.txt';

        if (!is_file($txtFile)) {
            self::markTestSkipped('Fixture readme.txt not found.');
        }

        $matcher = new PathMatcher([]);
        $loader = new PathLoader([$txtFile], $matcher);

        $files = $loader->loadPaths();

        self::assertSame([], $files);
    }

    #[Test]
    public function itSkipsBrokenSymlinksInDirectoryScan(): void
    {
        $link = self::FIXTURE_ROOT . '/broken.xml';

        // Broken symlinks cannot be reliably committed to version control,
        // so this is one of the rare cases where runtime creation is justified.
        symlink(self::FIXTURE_ROOT . '/nonexistent_target', $link);

        try {
            $matcher = new PathMatcher([]);
            $loader = new PathLoader([self::FIXTURE_ROOT], $matcher);

            $files = $loader->loadPaths();

            foreach ($files as $file) {
                self::assertStringNotContainsString('broken.xml', $file);
            }
        } finally {
            unlink($link);
        }
    }

    #[Test]
    public function itSilentlySkipsNonexistentPaths(): void
    {
        $matcher = new PathMatcher([]);
        $loader = new PathLoader(['/nonexistent/path'], $matcher);

        $files = $loader->loadPaths();

        self::assertSame([], $files);
    }

    #[Test]
    public function itReturnsSortedDeduplicated(): void
    {
        $matcher = new PathMatcher([]);
        $loader = new PathLoader(
            [self::FIXTURE_ROOT, self::FIXTURE_ROOT],
            $matcher,
        );

        $files = $loader->loadPaths();

        $sorted = $files;
        sort($sorted);

        self::assertSame($sorted, $files);
        self::assertSame(array_unique($files), $files);
    }
}
