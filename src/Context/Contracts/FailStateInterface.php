<?php

declare(strict_types=1);

namespace FailAid\Context\Contracts;

interface FailStateInterface
{
    /**
     * @BeforeScenario
     */
    public function refreshStates(): void;

    /**
     * @param string|int $value
     */
    public static function addState(string $name, $value): void;

    /**
     * @param array<string, mixed> $states
     */
    public function getStateDetails(array $states): string;
}
