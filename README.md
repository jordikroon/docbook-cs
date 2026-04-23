# DocbookCS

A static-analysis linter for DocBook XML files. It scans XML documentation sources and reports style and convention violations.

## Requirements

- PHP 8.5+
- Extensions: `dom`, `libxml`, `simplexml`

## Installation

```bash
composer require jordikroon/docbookcs
```

## Usage

```bash
# Run with the default config file (docbookcs.xml in the current directory)
vendor/bin/docbook-cs

# Specify a config file
vendor/bin/docbook-cs --config=myconfig.xml

# Scan specific paths (overrides paths in config)
vendor/bin/docbook-cs reference/ language/

# Output as Checkstyle XML (useful for CI)
vendor/bin/docbook-cs --report=checkstyle --no-colors > report.xml

# Output as JSON
vendor/bin/docbook-cs --report=json

# Suppress progress output
vendor/bin/docbook-cs --quiet
```

### Exit codes

| Code | Meaning |
|------|---------|
| `0`  | No violations found |
| `1`  | One or more violations found |
| `2`  | Runtime error (bad config, unreadable file, etc.) |

## Configuration

Copy `docbookcs.xml.dist` to `docbookcs.xml` and adjust to your project:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<docbookcs>
    <sniffs>
        <sniff class="DocbookCS\Sniff\SimparaSniff" />
        <sniff class="DocbookCS\Sniff\ExceptionNameSniff" />
        <sniff class="DocbookCS\Sniff\AttributeOrderSniff" />
    </sniffs>

    <paths>
        <path>reference</path>
        <path>language</path>
    </paths>

    <exclude>
        <pattern>*/skeleton.xml</pattern>
        <pattern>*/.git/*</pattern>
    </exclude>

    <entities>
        <directory>../doc-base/entities</directory>
        <file>entities/global.ent</file>
    </entities>
</docbookcs>
```

### `<sniffs>`

Register sniffs by fully-qualified class name. Sniffs support optional `<property>` children for per-sniff configuration (see sniff documentation below).

### `<paths>`

Directories or files to scan, relative to the config file or absolute. These are used when no paths are passed on the command line.

### `<exclude>`

`fnmatch`-style patterns for paths to skip.

### `<entities>`

Entity directories and files that should be loaded before parsing. Lets the XML parser resolve DocBook entities defined outside the scanned tree (e.g. from `doc-base`).

## Writing a custom sniff

Implement `DocbookCS\Sniff\SniffInterface` (or extend `AbstractSniff`) and register it in your config:

```php
namespace Acme\DocbookSniffs;

use DocbookCS\Sniff\AbstractSniff;

final class MySniff extends AbstractSniff
{
    public function getCode(): string
    {
        return 'Acme.MySniff';
    }

    public function process(\DOMDocument $document, string $content, string $filePath): array
    {
        $violations = [];
        // ... inspect $document, add violations via $this->createViolation(...)
        return $violations;
    }
}
```

```xml
<sniff class="Acme\DocbookSniffs\MySniff" />
```

## Development

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Static analysis
vendor/bin/phpstan

# Code style
vendor/bin/phpcs
```

## License

Apache 2.0
