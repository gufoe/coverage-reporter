<?php

namespace CoverageReporter;

class PathUtils
{
    /**
     * Get the relative path to assets/css/coverage.css from a given file or directory.
     */
    public static function cssPath(string $fullPath, string $rootPath, bool $isFile = false): string
    {
        $relative = self::_resolveAndGetRelativePath($fullPath, $rootPath);

        if ($relative === null) {
            return 'assets/css/coverage.css';
        }

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
        $relative = self::_resolveAndGetRelativePath($fullPath, $rootPath);

        if ($relative === null) {
            return 'index.html';
        }

        return ($relative === '' ? '' : $relative . '/') . 'index.html';
    }

    /**
     * Get the relative URL to a file's HTML page from the report root.
     */
    public static function fileHtml(string $fullPath, string $rootPath): string
    {
        $relative = self::_resolveAndGetRelativePath($fullPath, $rootPath);

        if ($relative === null) {
            return basename($fullPath) . '.html';
        }

        return $relative . '.html';
    }

    /**
     * Get the relative URL to an asset from a given path.
     */
    public static function asset(string $assetPath, string $fromPath, string $rootPath): string
    {
        $relativeFrom = self::_resolveAndGetRelativePath($fromPath, $rootPath);

        if ($relativeFrom === null) {
            // If fromPath is outside rootPath, we can't reliably determine depth.
            // Default to a direct path to the asset, assuming it's relative to the current location.
            // This case should ideally not happen if inputs are always within the project scope.
            return $assetPath;
        }

        $depth = $relativeFrom === '' ? 0 : substr_count($relativeFrom, '/');
        return str_repeat('../', $depth) . $assetPath;
    }

    /**
     * Generate breadcrumbs for a given path.
     * @return array<string, string|null> [label => url|null]
     */
    public static function breadcrumbs(string $fullPath, string $rootPath, bool $isFile = false): array
    {
        $breadcrumbs = [];
        $relative = self::_resolveAndGetRelativePath($fullPath, $rootPath);

        if ($relative === null) {
            // If path is outside root, return a simple breadcrumb to root and the current item.
            $breadcrumbs['Root'] = 'index.html';
            $breadcrumbs[self::basename($fullPath)] = null;
            return $breadcrumbs;
        }

        $parts = array_filter(explode('/', $relative));
        $depth = count($parts);

        // Determine the base depth for ../ prefixes.
        // For files, it's one less than for directories because the file itself is the last part.
        // For directories, the last part is a directory, so links go up from there.
        $baseLinkDepth = $isFile ? $depth -1 : $depth;
        if ($baseLinkDepth < 0) $baseLinkDepth = 0; // Handle case where $fullPath is $rootPath and $isFile is true

        $breadcrumbs['Root'] = str_repeat('../', $baseLinkDepth) . 'index.html';

        foreach ($parts as $i => $part) {
            $isLast = ($i === $depth - 1);
            if ($isLast) {
                $breadcrumbs[$part] = null;
            } else {
                // For files, the path to an intermediate directory needs to go up one less level.
                // For directories, the path to an intermediate directory is relative to the current directory depth.
                $linkDepth = $isFile ? $depth - $i - 2 : $depth - $i - 1;
                if ($linkDepth < 0) $linkDepth = 0; // Ensure no negative repeat count
                $breadcrumbs[$part] = str_repeat('../', $linkDepth) . 'index.html';
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

    /**
     * Resolves a full path against a root path and returns the relative path if contained.
     *
     * @param string $fullPath The full path to resolve.
     * @param string $rootPath The root path to resolve against.
     * @return string|null The relative path if $fullPath is under $rootPath, otherwise null.
     */
    private static function _resolveAndGetRelativePath(string $fullPath, string $rootPath): ?string
    {
        $root = rtrim($rootPath, '/');
        // Ensure $root ends with a slash for prefix matching, unless it's the root directory '/'
        $rootPrefix = ($root === '/') ? '/' : $root . '/';

        if (str_starts_with($fullPath, $rootPrefix)) {
            return substr($fullPath, strlen($rootPrefix));
        }
        return null;
    }
}
