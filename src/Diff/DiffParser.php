<?php

declare(strict_types=1);

namespace DocbookCS\Diff;

final class DiffParser
{
    /** @return array<string, list<int>> */
    public function parse(string $diff): array
    {
        $result = [];
        $currentFile = null;
        $deleted = false;
        $newLineNumber = 0;

        foreach (explode("\n", $diff) as $line) {
            if (str_starts_with($line, 'diff --git ')) {
                $currentFile = null;
                $deleted = false;
                $newLineNumber = 0;
                continue;
            }

            if (str_starts_with($line, 'deleted file mode')) {
                $deleted = true;
                continue;
            }

            // Target file header: "+++ b/path" or "+++ /dev/null"
            if (str_starts_with($line, '+++ ') && !$deleted) {
                $path = rtrim(substr($line, 4));
                if (str_starts_with($path, 'b/')) {
                    $path = substr($path, 2);
                }
                $currentFile = $path !== '/dev/null' ? $path : null;
                if ($currentFile !== null && !isset($result[$currentFile])) {
                    $result[$currentFile] = [];
                }
                continue;
            }

            if ($currentFile === null) {
                continue;
            }

            // Hunk header: @@ -old_start[,old_count] +new_start[,new_count] @@
            if (str_starts_with($line, '@@ ')) {
                if (preg_match('/\+(\d+)(?:,\d+)?/', $line, $m)) {
                    $newLineNumber = (int) $m[1];
                }
                continue;
            }

            if (str_starts_with($line, '+')) {
                $result[$currentFile][] = $newLineNumber;
                $newLineNumber++;
                continue;
            }

            if (!str_starts_with($line, '-')) {
                // Context line — present in both old and new file.
                $newLineNumber++;
            }
        }

        return $result;
    }
}
