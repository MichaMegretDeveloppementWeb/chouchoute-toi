<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\Support\MigrationsFingerprint;

/**
 * The analysis reads everything again once a migration changes.
 *
 * Larastan reads the schema in the migrations, and PHPStan's result cache only
 * reanalyses the files that changed · a column made nullable leaves the code
 * that reads it with its old verdict, and the chain says green on a type that
 * has become wrong. The fingerprint of the migrations enters what PHPStan
 * compares before reusing its cache.
 */
final class TheAnalysisReadsAgainWhenAMigrationChangesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/chouchoute-fingerprint-'.getmypid().'-'.bin2hex(random_bytes(4));

        $this->write('database/migrations/2026_01_01_000001_create_rooms_table.php', '<?php // rooms');
        $this->write('database/migrations/2026_01_01_000002_create_guests_table.php', '<?php // guests');
    }

    protected function tearDown(): void
    {
        $this->forget($this->root);

        parent::tearDown();
    }

    public function test_the_same_migrations_give_the_same_fingerprint(): void
    {
        $this->assertSame($this->fingerprint(), $this->fingerprint());
    }

    public function test_a_migration_corrected_in_place_changes_it(): void
    {
        $before = $this->fingerprint();

        $this->write('database/migrations/2026_01_01_000002_create_guests_table.php', '<?php // guests, a column made nullable');

        $this->assertNotSame($before, $this->fingerprint());
    }

    public function test_a_migration_added_changes_it(): void
    {
        $before = $this->fingerprint();

        $this->write('database/migrations/2026_01_01_000003_create_stays_table.php', '<?php // stays');

        $this->assertNotSame($before, $this->fingerprint());
    }

    public function test_a_migration_removed_changes_it(): void
    {
        $before = $this->fingerprint();

        unlink($this->root.'/database/migrations/2026_01_01_000002_create_guests_table.php');

        $this->assertNotSame($before, $this->fingerprint());
    }

    public function test_a_migration_renamed_changes_it(): void
    {
        $before = $this->fingerprint();

        rename(
            $this->root.'/database/migrations/2026_01_01_000002_create_guests_table.php',
            $this->root.'/database/migrations/2026_01_01_000009_create_guests_table.php',
        );

        $this->assertNotSame($before, $this->fingerprint());
    }

    /** Larastan reads below each directory, so a folder of migrations counts. */
    public function test_a_migration_in_a_sub_folder_counts(): void
    {
        $before = $this->fingerprint();

        $this->write('database/migrations/archive/2025_01_01_000001_create_notes_table.php', '<?php // notes');

        $this->assertNotSame($before, $this->fingerprint());
    }

    public function test_what_lies_outside_the_migrations_does_not_count(): void
    {
        $before = $this->fingerprint();

        $this->write('database/seeders/GuestSeeder.php', '<?php // guests');
        $this->write('database/migrations/notes.md', 'not read by larastan');

        $this->assertSame($before, $this->fingerprint());
    }

    public function test_an_absolute_directory_reads_the_same_files_as_its_relative_name(): void
    {
        $absolute = new MigrationsFingerprint([$this->root.'/database/migrations'], $this->root);

        $this->assertSame($this->fingerprint(), $absolute->getHash());
    }

    public function test_the_analysis_registers_it_on_the_directories_larastan_reads(): void
    {
        $configuration = (string) preg_replace('/#.*$/m', '', (string) file_get_contents(dirname(__DIR__, 2).'/phpstan.neon'));

        $this->assertMatchesRegularExpression(
            '/class:\s*'.preg_quote(MigrationsFingerprint::class, '/').'\s*\n\s*arguments:\s*\[%databaseMigrationsPath%,\s*%currentWorkingDirectory%\]\s*\n\s*tags:\s*\[phpstan\.resultCacheMetaExtension\]/',
            $configuration,
        );
    }

    private function fingerprint(): string
    {
        return (new MigrationsFingerprint(['database/migrations'], $this->root))->getHash();
    }

    private function write(string $relative, string $contents): void
    {
        $path = $this->root.'/'.$relative;

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $contents);
    }

    private function forget(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($entries as $entry) {
            if (! $entry instanceof SplFileInfo) {
                continue;
            }

            if ($entry->isDir()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }

        rmdir($directory);
    }
}
