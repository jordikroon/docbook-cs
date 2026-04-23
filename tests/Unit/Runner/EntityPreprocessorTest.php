<?php

declare(strict_types=1);

namespace DocbookCS\Tests\Unit\Runner;

use DocbookCS\Runner\EntityPreprocessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EntityPreprocessor::class)]
final class EntityPreprocessorTest extends TestCase
{
    private EntityPreprocessor $preprocessor;

    protected function setUp(): void
    {
        $this->preprocessor = new EntityPreprocessor();
    }

    #[Test]
    public function itPreservesPredefinedXmlEntities(): void
    {
        $input = '<simpara>1 &lt; 2 &amp; 3 &gt; 0 &quot;hi&quot; &apos;yo&apos;</simpara>';

        $result = $this->preprocessor->neutralize($input);

        self::assertSame($input, $result);
    }

    #[Test]
    public function itRemovesCustomEntities(): void
    {
        $input = '<simpara>&php.ini; is a &configuration.file; thing.</simpara>';

        $result = $this->preprocessor->neutralize($input);

        self::assertSame('<simpara> is a  thing.</simpara>', $result);
    }

    #[Test]
    public function itSubstitutesCustomReplacementString(): void
    {
        $preprocessor = new EntityPreprocessor('[ENTITY]');

        $result = $preprocessor->neutralize('<simpara>&php.ini; is important.</simpara>');

        self::assertSame('<simpara>[ENTITY] is important.</simpara>', $result);
    }

    #[Test]
    public function itPreservesNumericCharacterReferences(): void
    {
        $input = '<simpara>&#169; &#x00A9;</simpara>';

        $result = $this->preprocessor->neutralize($input);

        self::assertSame($input, $result);
    }

    #[Test]
    public function itHandlesMixedEntityTypes(): void
    {
        $input = '<simpara>&amp; &custom.entity; &#169; &lt;</simpara>';

        $result = $this->preprocessor->neutralize($input);

        self::assertSame('<simpara>&amp;  &#169; &lt;</simpara>', $result);
    }

    #[Test]
    public function itReturnsUnchangedContentWithNoEntities(): void
    {
        $input = '<simpara>No entities here.</simpara>';

        $result = $this->preprocessor->neutralize($input);

        self::assertSame($input, $result);
    }

    #[Test]
    #[DataProvider('entityNameProvider')]
    public function itHandlesVariousEntityNameFormats(string $input, string $expected): void
    {
        $result = $this->preprocessor->neutralize($input);

        self::assertSame($expected, $result);
    }

    /** @return array<string, array{string, string}> */
    public static function entityNameProvider(): array
    {
        return [
            'simple'           => ['&foo;', ''],
            'dotted'           => ['&foo.bar;', ''],
            'hyphenated'       => ['&foo-bar;', ''],
            'underscored'      => ['&foo_bar;', ''],
            'predefined amp'   => ['&amp;', '&amp;'],
            'predefined lt'    => ['&lt;', '&lt;'],
            'numeric decimal'  => ['&#8212;', '&#8212;'],
            'numeric hex'      => ['&#x2014;', '&#x2014;'],
        ];
    }

    #[Test]
    public function itStripsSimpleDoctype(): void
    {
        $input = '<?xml version="1.0"?><!DOCTYPE book SYSTEM "docbook.dtd"><book/>';

        $result = $this->preprocessor->stripDoctype($input);

        self::assertSame('<?xml version="1.0"?><book/>', $result);
    }

    #[Test]
    public function itStripsDoctypeWithInternalSubset(): void
    {
        $input = <<<'XML'
<?xml version="1.0"?>
<!DOCTYPE book [
  <!ENTITY foo "bar">
  <!ENTITY baz '<link linkend="x">text</link>'>
]>
<book/>
XML;

        $result = $this->preprocessor->stripDoctype($input);

        self::assertStringNotContainsString('DOCTYPE', $result);
        self::assertStringNotContainsString('ENTITY', $result);
        self::assertStringContainsString('<book/>', $result);
    }

    #[Test]
    public function itLeavesContentUnchangedWhenNoDoctypePresent(): void
    {
        $input = '<?xml version="1.0"?><book/>';

        $result = $this->preprocessor->stripDoctype($input);

        self::assertSame($input, $result);
    }

    #[Test]
    public function itStripsDoctypeContainingQuotedAngleBrackets(): void
    {
        $input = '<!DOCTYPE book [ <!ENTITY x "<simpara>hi</simpara>"> ]><book/>';

        $result = $this->preprocessor->stripDoctype($input);

        self::assertSame('<book/>', $result);
    }

    #[Test]
    public function itProducesParseableXmlFromFullPipeline(): void
    {
        $input = <<<'XML'
<?xml version="1.0"?>
<!DOCTYPE chapter [
  <!ENTITY link.superglobals '<link linkend="language.variables.superglobals">superglobals</link>'>
  <!ENTITY php.ini "php.ini">
]>
<chapter>
  <simpara>&link.superglobals; and &php.ini; and &amp; done.</simpara>
</chapter>
XML;

        $result = $this->preprocessor->process($input);

        $dom = new \DOMDocument();
        $loaded = $dom->loadXML($result);

        self::assertTrue($loaded, 'Preprocessed XML should parse without errors.');
    }

    #[Test]
    public function itStripsDoctypeAndNeutralizesEntitiesInProcess(): void
    {
        $input = <<<'XML'
<?xml version="1.0"?>
<!DOCTYPE chapter SYSTEM "docbook.dtd">
<chapter>
  <simpara>&link.superglobals; are &amp; special.</simpara>
</chapter>
XML;

        $result = $this->preprocessor->process($input);

        self::assertStringNotContainsString('DOCTYPE', $result);
        self::assertStringNotContainsString('&link.superglobals;', $result);
        self::assertStringContainsString('&amp;', $result);
        self::assertStringContainsString('<chapter>', $result);
    }

    #[Test]
    public function itReturnsOriginalContentWhenDoctypeIsNotClosed(): void
    {
        $input = '<!DOCTYPE book [ <!ENTITY foo "bar" ><book/>';

        $result = $this->preprocessor->stripDoctype($input);

        self::assertSame($input, $result);
    }
}
