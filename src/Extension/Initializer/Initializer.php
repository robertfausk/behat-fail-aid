<?php

declare(strict_types=1);

namespace FailAid\Extension\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use FailAid\Context\FailureContext;
use FailAid\Service\StaticCallerService;

/**
 * ContextInitialiser class.
 */
class Initializer implements ContextInitializer
{
    /** @var array<string, mixed> */
    public array $screenshot;

    /** @var array<string, string> */
    public array $siteFilters;

    /** @var array<string, string> */
    public array $debugBarSelectors;

    /** @var array<string, mixed> */
    public array $trackJs;

    public ?string $defaultSession;

    /** @var array<string, mixed> */
    public array $output;

    /**
     * @param array<string, mixed>  $screenshot
     * @param array<string, string> $siteFilters
     * @param array<string, string> $debugBarSelectors
     * @param array<string, mixed>  $trackJs
     * @param array<string, mixed>  $output
     */
    public function __construct(
        array $screenshot,
        array $siteFilters = [],
        array $debugBarSelectors = [],
        array $trackJs = [],
        ?string $defaultSession = null,
        array $output = [],
    ) {
        $this->screenshot = $screenshot;
        $this->siteFilters = $siteFilters;
        $this->debugBarSelectors = $debugBarSelectors;
        $this->trackJs = $trackJs;
        $this->defaultSession = $defaultSession;
        $this->output = $output;
    }

    public function initializeContext(Context $context): void
    {
        if ($context instanceof FailureContext) {
            $context->setStaticCaller(new StaticCallerService());
            $context->setConfig(
                $this->screenshot,
                $this->siteFilters,
                $this->debugBarSelectors,
                $this->trackJs,
                $this->defaultSession,
                $this->output
            );
        }
    }
}
