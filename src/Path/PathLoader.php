<?php

declare(strict_types=1);

namespace DocbookCS\Path;

final class PathLoader
{
    private PathMatcher $matcher;

    /** @var list<string> */
    private array $includePaths;

    private string $fileExtension;

    /** @var \Closure(string): bool */
    private \Closure $isDir;

    /** @var \Closure(string): bool */
    private \Closure $isFile;

    /** @param list<string> $includePaths */
    public function __construct(
        array $includePaths,
        PathMatcher $matcher,
        string $fileExtension = 'xml',
        ?\Closure $isDir = null,
        ?\Closure $isFile = null,
    ) {
        $this->includePaths = $includePaths;
        $this->matcher = $matcher;
        $this->fileExtension = ltrim($fileExtension, '.');
        $this->isDir = $isDir ?? \is_dir(...);
        $this->isFile = $isFile ?? \is_file(...);
    }

    /**
     * @return list<string>
     * @throws \UnexpectedValueException if any of the include paths is a directory that cannot be read.
     */
    public function loadPaths(): array
    {
        $files = [];

        foreach ($this->includePaths as $path) {
            if (($this->isDir)($path)) {
                $files = array_merge($files, $this->scanDirectory($path));
            } elseif (($this->isFile)($path)) {
                $this->collectIfMatch($path, $files);
            }
        }

        sort($files);

        return array_values(array_unique($files));
    }

    /**
     * @param list<string> &$collected
     */
    private function collectIfMatch(string $filePath, array &$collected): void
    {
        $normalized = str_replace('\\', '/', $filePath);

        if (!str_ends_with($normalized, '.' . $this->fileExtension)) {
            return;
        }

        if ($this->matcher->isIncluded($normalized)) {
            $collected[] = $normalized;
        }
    }

    /**
     * @return list<string>
     * @throws \UnexpectedValueException if the directory cannot be read.
     */
    private function scanDirectory(string $directory): array
    {
        $found = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS,
            ),
        );

        /** @var \SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $this->collectIfMatch($fileInfo->getPathname(), $found);
        }

        return $found;
    }
}
