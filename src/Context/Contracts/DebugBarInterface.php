<?php

declare(strict_types=1);

namespace FailAid\Context\Contracts;

use Behat\Mink\Element\DocumentElement;

/**
 * DebugBarInterface interface.
 */
interface DebugBarInterface
{
    /**
     * Override if gathering details is complex.
     */
    /** @param array<string, string> $debugBarSelectors */
    public function gatherDebugBarDetails(array $debugBarSelectors, DocumentElement $page): string;
}
