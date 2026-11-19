<?php

declare(strict_types=1);

namespace Tests\Support;

use FilesystemIterator;
use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * The migrations, in what PHPStan compares before reusing its result cache.
 *
 * Larastan reads the schema in the migrations, and the cache only reanalyses
 * the files that changed · a migration corrected in place would leave every
 * file that reads its columns with its old verdict.
 */
final readonly class MigrationsFingerprint implements ResultCacheMetaExtension
{
    /**
     * @param  list<string>  $directories  the migration directories larastan reads, as the analysis names them
     */
    public function __construct(
        private array $directories,
        private string $workingDirectory,
    ) {}

    public function getKey(): string
    {
        return 'migrations';
    }

    /** Every migration file, by its path from the working directory and its content. */
    public function getHash(): string
    {
        $context = hash_init('md5');

        foreach ($this->files() as $name => $path) {
            hash_update($context, $name."\0");
            hash_update_file($context, $path);
            hash_update($context, "\0");
        }

        return hash_final($context);
    }

    /**
     * The files larastan reads, found as it finds them · each glob, then every
     * PHP file below it.
     *
     * @return array<string, string> the absolute path, keyed by the path from the working directory
     */
    private function files(): array
    {
        $files = [];

        foreach ($this->directories as $directory) {
            $matches = glob($this->absolute($directory));

            if ($matches === false) {
                continue;
            }

            foreach ($matches as $match) {
                if (! is_dir($match)) {
                    continue;
                }

                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($match, FilesystemIterator::SKIP_DOTS)) as $file) {
                    if ($file instanceof SplFileInfo && $file->isFile() && preg_match('/\.php$/i', $file->getFilename()) === 1) {
                        $files[$this->nameOf($file->getPathname())] = $file->getPathname();
                    }
                }
            }
        }

        ksort($files, SORT_STRING);

        return $files;
    }

    private function absolute(string $path): string
    {
        if (preg_match('#^([a-z]:)?[/\\\\]#i', $path) === 1) {
            return $path;
        }

        return rtrim($this->workingDirectory, '/\\').'/'.$path;
    }

    /** The path from the working directory, with forward slashes, so that the fingerprint does not depend on where the package lies. */
    private function nameOf(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $root = rtrim(str_replace('\\', '/', $this->workingDirectory), '/').'/';

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
