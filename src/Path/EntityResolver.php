<?php

declare(strict_types=1);

namespace DocbookCS\Path;

final class EntityResolver
{
    /** @var list<string> */
    private array $entityPaths;

    private string $extension;

    /**
     * @param list<string> $entityPaths
     */
    public function __construct(array $entityPaths, string $extension = 'ent')
    {
        $this->entityPaths = $entityPaths;
        $this->extension = ltrim($extension, '.');
    }

    /**
     * @return list<string>
     * @throws \UnexpectedValueException if any of the entity paths is a directory that cannot be read.
     */
    public function resolve(): array
    {
        $files = [];

        foreach ($this->entityPaths as $path) {
            if (is_file($path) && str_ends_with($path, '.' . $this->extension)) {
                $files[] = str_replace('\\', '/', $path);
                continue;
            }

            if (!is_dir($path)) {
                continue;
            }

            foreach ($this->scanDirectory($path) as $file) {
                $files[] = $file;
            }
        }

        sort($files);

        return array_values(array_unique($files));
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
            if (
                $fileInfo->isFile()
                && str_ends_with($fileInfo->getPathname(), '.' . $this->extension)
            ) {
                $found[] = str_replace('\\', '/', $fileInfo->getPathname());
            }
        }

        return $found;
    }
}
