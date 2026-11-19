<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

/** A refusal a form raises through one of its traits. */
trait RefusesFromATrait
{
    public function refuseFromTheTrait(): void
    {
        $this->addError('raisedByTheTrait', 'Refusé.');
    }
}
