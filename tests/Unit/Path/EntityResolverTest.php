<?php

declare(strict_types=1);

namespace DocbookCS\Tests\Unit\Path;

use DocbookCS\Path\EntityResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EntityResolver::class)]
final class EntityResolverTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        $this->fixtureRoot = __DIR__ . '/../../fixtures/entity_tree';

        if (!is_dir($this->fixtureRoot)) {
            self::markTestSkipped('Fixture entity_tree not found.');
        }
    }

    #[Test]
    public function itReturnsEmptyArrayWhenNoPathsGiven(): void
    {
        $resolver = new EntityResolver([]);

        self::assertSame([], $resolver->resolve());
    }

    #[Test]
    public function itFindsEntityFilesRecursively(): void
    {
        $resolver = new EntityResolver([$this->fixtureRoot]);

        $files = $resolver->resolve();

        self::assertNotEmpty($files);

        foreach ($files as $file) {
            self::assertStringEndsWith('.ent', $file);
        }
    }

    #[Test]
    public function itAcceptsSingleFilePath(): void
    {
        $singleFile = $this->fixtureRoot . '/global.ent';

        if (!is_file($singleFile)) {
            self::markTestSkipped('Fixture file not found.');
        }

        $resolver = new EntityResolver([$singleFile]);

        $files = $resolver->resolve();

        self::assertCount(1, $files);
        self::assertStringContainsString('global.ent', $files[0]);
    }

    #[Test]
    public function itIgnoresFilesWithWrongExtension(): void
    {
        $xmlFile = $this->fixtureRoot . '/not_an_entity.xml';

        if (!is_file($xmlFile)) {
            self::markTestSkipped('Fixture file not found.');
        }

        $resolver = new EntityResolver([$xmlFile]);

        self::assertSame([], $resolver->resolve());
    }

    #[Test]
    public function itSilentlySkipsNonexistentPaths(): void
    {
        $resolver = new EntityResolver(['/nonexistent/path']);

        self::assertSame([], $resolver->resolve());
    }

    #[Test]
    public function itReturnsSortedDeduplicated(): void
    {
        $singleFile = $this->fixtureRoot . '/global.ent';

        if (!is_file($singleFile)) {
            self::markTestSkipped('Fixture file not found.');
        }

        $resolver = new EntityResolver(
            [$singleFile, $this->fixtureRoot],
        );

        $files = $resolver->resolve();

        $sorted = $files;
        sort($sorted);

        self::assertSame($sorted, $files);
        self::assertSame(array_values(array_unique($files)), $files);
    }

    #[Test]
    public function itSupportsCustomExtension(): void
    {
        $resolver = new EntityResolver([$this->fixtureRoot], 'xml');

        $files = $resolver->resolve();

        foreach ($files as $file) {
            self::assertStringEndsWith('.xml', $file);
        }
    }

    #[Test]
    public function itStripsLeadingDotFromExtension(): void
    {
        $resolver = new EntityResolver([$this->fixtureRoot], '.ent');

        $files = $resolver->resolve();

        self::assertNotEmpty($files);

        foreach ($files as $file) {
            self::assertStringEndsWith('.ent', $file);
        }
    }

    #[Test]
    public function itNormalizesBackslashesInPaths(): void
    {
        $resolver = new EntityResolver([$this->fixtureRoot]);

        $files = $resolver->resolve();

        foreach ($files as $file) {
            self::assertStringNotContainsString('\\', $file);
        }
    }
}
