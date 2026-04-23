<?php

declare(strict_types=1);

namespace DocbookCS\Path;

final readonly class PathMatcher
{
    /** @var list<string> */
    private array $excludePatterns;

    /**
     * @param list<string> $excludePatterns
     */
    public function __construct(array $excludePatterns)
    {
        $this->excludePatterns = $excludePatterns;
    }

    public function isExcluded(string $filePath): bool
    {
        // Normalize to forward slashes for consistent matching.
        $normalized = str_replace('\\', '/', $filePath);

        foreach ($this->excludePatterns as $pattern) {
            $pattern = str_replace('\\', '/', $pattern);

            // Match against the full path.
            if (fnmatch($pattern, $normalized, FNM_NOESCAPE)) {
                return true;
            }

            // Also try matching against just the relative tail.
            // This lets a pattern like "*/skeleton.xml" match
            // "/abs/path/to/skeleton.xml".
            if (fnmatch($pattern, basename($normalized))) {
                return true;
            }
        }

        return false;
    }

    public function isIncluded(string $filePath): bool
    {
        return !$this->isExcluded($filePath);
    }
}
