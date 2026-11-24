<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\Assert;

/**
 * A rule of a sheet, read the same way by every test of the site · the body of
 * the first block a selector opens, to its own closing brace, and its
 * declarations, property by value.
 */
trait ReadsTheSheet
{
    /** The body of the first block a selector opens in a sheet, a block nested in it included. */
    private function blockOf(string $sheet, string $selector): string
    {
        $start = strpos($sheet, $selector.' {');

        Assert::assertNotFalse($start, "Aucun bloc pour {$selector}.");

        $opened = $start + strlen($selector) + 2;
        $depth = 1;

        for ($at = $opened; $at < strlen($sheet); $at++) {
            $depth += match ($sheet[$at]) {
                '{' => 1,
                '}' => -1,
                default => 0,
            };

            if ($depth === 0) {
                return substr($sheet, $opened, $at - $opened);
            }
        }

        Assert::fail("Le bloc de {$selector} ne se ferme pas.");
    }

    /**
     * The declarations of the first block a selector opens in a sheet.
     *
     * @return array<string, string>
     */
    private function declarationsOf(string $sheet, string $selector): array
    {
        $declarations = [];

        foreach (array_filter(array_map('trim', explode(';', $this->blockOf($sheet, $selector))), static fn (string $line): bool => $line !== '') as $line) {
            [$property, $value] = array_map('trim', explode(':', $line, 2));
            $declarations[$property] = $value;
        }

        return $declarations;
    }
}
