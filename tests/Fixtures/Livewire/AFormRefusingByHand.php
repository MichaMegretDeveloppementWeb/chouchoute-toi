<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * A form the guard of the refusals reads and never draws · one refusal raised
 * here, one by its trait, and one a comment only quotes.
 */
final class AFormRefusingByHand extends Component
{
    use RefusesFromATrait;

    public function save(): void
    {
        // A comment that quotes withMessages(['quotedInAComment' => 'Refusé.']) raises nothing.
        throw ValidationException::withMessages(['raisedHere' => 'Refusé.']);
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
