<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * A refused field keeps the kit's red under the cursor · the back office paints
 * the focus ring of every field in its own colour, and leaves a refused one alone.
 *
 * The sheet sits outside any layer and beats every utility, the kit's refused
 * ring among them · only a selector that leaves refused fields out keeps it.
 */
final class ARefusedFieldStaysRedUnderTheCursorTest extends TestCase
{
    public function test_every_focus_the_back_office_paints_leaves_a_refused_field_out(): void
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents(resource_path('css/shared/fields.css')));
        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $rules, PREG_SET_ORDER);

        $painting = [];
        $unguarded = [];

        foreach ($rules as [, $selectors, $body]) {
            if (! str_contains($body, '--tw-ring-color: var(--fb-focus)')) {
                continue;
            }

            foreach (array_map(trim(...), explode(',', $selectors)) as $selector) {
                $painting[] = $selector;

                if (! str_contains($selector, ":not([aria-invalid='true'])") && ! str_contains($selector, ":not(:has([aria-invalid='true']))")) {
                    $unguarded[] = $selector;
                }
            }
        }

        $this->assertNotSame([], $painting, 'The sheet paints no focus: the test reads nothing.');
        $this->assertSame([], $unguarded, 'These rules paint a refused field in the focus colour.');
    }
}
