<?php

namespace CoverageReporter;

class PathUtils
{
    /**
     * Get the relative path to assets/css/coverage.css from a given file or directory.
     */
    public static function cssPath(string $fullPath, string $rootPath, bool $isFile = false): string
    {
        $root = rtrim($rootPath, '/');
        $relative = ltrim(str_replace($root, '', $fullPath), '/');
        if ($relative === '') {
            $depth = 0;
        } else {
            $depth = substr_count($relative, '/');
            if (!$isFile) {
                $depth += 1;
            }
        }
        return str_repeat('../', $depth) . 'assets/css/coverage.css';
    }

    /**
     * Get the relative URL to a directory's index.html from the report root.
     */
    public static function directoryIndex(string $fullPath, string $rootPath): string
    {
        $root = rtrim($rootPath, '/');
        $relative = ltrim(str_replace($root, '', $fullPath), '/');
        return ($relative === '' ? '' : $relative . '/') . 'index.html';
    }

    /**
     * Get the relative URL to a file's HTML page from the report root.
     */
    public static function fileHtml(string $fullPath, string $rootPath): string
    {
        $root = rtrim($rootPath, '/');
        $relative = ltrim(str_replace($root, '', $fullPath), '/');
        return $relative . '.html';
    }

    /**
     * Get the relative URL to an asset from a given path.
     */
    public static function asset(string $assetPath, string $fromPath, string $rootPath): string
    {
        $root = rtrim($rootPath, '/');
        $relative = ltrim(str_replace($root, '', $fromPath), '/');
        $depth = $relative === '' ? 0 : substr_count($relative, '/');
        return str_repeat('../', $depth) . $assetPath;
    }

    /**
     * Generate breadcrumbs for a given path.
     * @return array<string, string|null> [label => url|null]
     */
    public static function breadcrumbs(string $fullPath, string $rootPath, bool $isFile = false): array
    {
        $breadcrumbs = [];
        $root = rtrim($rootPath, '/');
        $relative = ltrim(str_replace($root, '', $fullPath), '/');
        $parts = array_filter(explode('/', $relative));
        if ($isFile) {
            $depth = count($parts);
            $breadcrumbs['Root'] = str_repeat('../', $depth - 1) . 'index.html';
            foreach ($parts as $i => $part) {
                $isLast = ($i === $depth - 1);
                if ($isLast) {
                    $breadcrumbs[$part] = null;
                } else {
                    $breadcrumbs[$part] = str_repeat('../', $depth - $i - 2) . 'index.html';
                }
            }
        } else {
            $depth = count($parts);
            $breadcrumbs['Root'] = str_repeat('../', $depth) . 'index.html';
            foreach ($parts as $i => $part) {
                $isLast = ($i === $depth - 1);
                if ($isLast) {
                    $breadcrumbs[$part] = null;
                } else {
                    $breadcrumbs[$part] = str_repeat('../', $depth - $i - 1) . 'index.html';
                }
            }
        }
        return $breadcrumbs;
    }

    /**
     * Check if a path starts with a given prefix.
     */
    public static function startsWith(string $path, string $prefix): bool
    {
        return str_starts_with($path, $prefix);
    }

    /**
     * Get the directory name of a path.
     */
    public static function dirname(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Get the base name of a path.
     */
    public static function basename(string $path): string
    {
        return basename($path);
    }

    /**
     * Compute the relative path from $from to $to (both absolute paths or report-relative paths).
     */
    public static function relativeLink(string $from, string $to): string
    {
        $from = rtrim($from, '/');
        $to = rtrim($to, '/');
        $fromParts = explode('/', $from);
        $toParts = explode('/', $to);
        // Remove common path prefix
        while (count($fromParts) && count($toParts) && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }
        $up = str_repeat('../', max(0, count($fromParts) - 1)); // -1 because $from is a file, not a dir
        return $up . implode('/', $toParts);
    }
}
