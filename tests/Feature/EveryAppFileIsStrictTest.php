<?php

declare(strict_types=1);

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Every PHP file of `app/` opens on `declare(strict_types=1)` · without it, a
 * value such as "12abc" handed to an `int` parameter silently becomes 12.
 */
final class EveryAppFileIsStrictTest extends TestCase
{
    public function test_every_file_of_the_app_declares_strict_types(): void
    {
        $missing = [];

        foreach (Finder::create()->files()->in(app_path())->name('*.php')->sortByName() as $file) {
            if (preg_match('/\A<\?php\s+declare\(strict_types=1\);/', $file->getContents()) !== 1) {
                $missing[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $missing, "These files of app/ do not open on declare(strict_types=1):\n".implode("\n", $missing));
    }
}
