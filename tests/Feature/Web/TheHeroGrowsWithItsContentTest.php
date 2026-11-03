<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use PHPUnit\Framework\TestCase;

/**
 * The hero takes the height of what it holds, so a low screen cuts neither its
 * title nor its card · what a screen shows is measured in a browser, and this
 * keeps the content in the flow that makes it possible.
 */
final class TheHeroGrowsWithItsContentTest extends TestCase
{
    public function test_the_content_of_the_hero_stays_in_the_flow(): void
    {
        $container = $this->ruleOf('.hero__container');

        $this->assertStringContainsString('position: relative', $container);
        $this->assertStringNotContainsString('position: absolute', $container);
        $this->assertStringNotContainsString('transform', $container);
        $this->assertStringNotContainsString('height', $container);
        $this->assertStringContainsString('display: flex', $this->ruleOf('.hero'));
    }

    public function test_the_hero_keeps_its_least_height(): void
    {
        $this->assertStringContainsString('min-height: max(70vh, 30em)', $this->ruleOf('.hero'));
    }

    /** The first rule written for this selector. */
    private function ruleOf(string $selector): string
    {
        $sheet = (string) file_get_contents(__DIR__.'/../../../resources/css/web/home/hero.css');

        $this->assertSame(1, preg_match('/^'.preg_quote($selector, '/').' \{(.*?)^\}/ms', $sheet, $rule), "No rule for {$selector}.");

        return $rule[1];
    }
}
