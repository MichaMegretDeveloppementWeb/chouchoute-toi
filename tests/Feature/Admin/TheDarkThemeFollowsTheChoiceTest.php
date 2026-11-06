<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * The colours the office writes for its dark theme follow the theme the page
 * carries, as the kit's do · never the setting of the visitor's system, which
 * would leave the account's name dark on a dark bar.
 */
final class TheDarkThemeFollowsTheChoiceTest extends TestCase
{
    public function test_the_dark_rules_of_the_office_answer_the_class_of_the_page(): void
    {
        $sheet = $this->compiledSheetFor('resources/css/admin.css');

        $this->assertStringNotContainsString('prefers-color-scheme:dark', str_replace(' ', '', $sheet), 'A dark rule of the office follows the system setting.');
        $this->assertStringContainsString(':where(.dark,.dark *)', $sheet, 'No dark rule of the office answers the class of the page.');
    }

    /** The file the build produced for an entry, as its manifest names it. */
    private function compiledSheetFor(string $entry): string
    {
        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);
        $this->assertIsArray($manifest, 'No build manifest · run `npm run build`.');
        $this->assertIsArray($manifest[$entry] ?? null, $entry.' is not an entry of the build.');

        $file = $manifest[$entry]['file'] ?? null;
        $this->assertIsString($file);

        return (string) file_get_contents(public_path('build/'.$file));
    }
}
