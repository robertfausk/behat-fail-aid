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
     *
     * @return string
     */
    public function gatherDebugBarDetails(array $debugBarSelectors, DocumentElement $page);
}
