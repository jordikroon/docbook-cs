<?php

declare(strict_types=1);

namespace DocbookCS\Config;

final class SniffEntry
{
    /** @var array<string, string> */
    private array $properties;

    /**
     * @param array<string, string> $properties
     * @throws \InvalidArgumentException if $className is empty or only whitespace.
     */
    public function __construct(
        private readonly string $className,
        array $properties = [],
    ) {
        if (trim($className) === '') {
            throw new \InvalidArgumentException('Sniff class name must not be empty.');
        }

        $this->properties = $properties;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getProperty(string $name, ?string $default = null): ?string
    {
        return $this->properties[$name] ?? $default;
    }

    /** @return array<string, string> */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
