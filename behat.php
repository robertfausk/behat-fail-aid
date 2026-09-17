<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use FailAid\Extension\ServiceContainer\Extension as FailAidExtension;

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withSuite(
                (new Suite('default'))
                    ->withContexts(
                        \FeatureContext::class,
                        \Behat\MinkExtension\Context\MinkContext::class,
                        \FailAid\Context\FailureContext::class,
                    )
            )
            ->withSuite(
                (new Suite('api'))
                    ->withContexts(
                        \FeatureContext::class,
                        \Behat\MinkExtension\Context\MinkContext::class,
                        \FailAid\Context\FailureContext::class,
                    )
            )
            ->withSuite(
                (new Suite('generic'))
                    ->withContexts(
                        \FeatureContext::class,
                        \Behat\MinkExtension\Context\MinkContext::class,
                        \FailAid\Context\FailureContext::class,
                    )
            )
            ->withExtension(
                new Extension(FailAidExtension::class, [
                    'output' => ['api' => false, 'tags' => false],
                    'screenshot' => [
                        'directory' => './features/failures',
                        'mode' => 'default',
                        'autoClean' => false,
                        'size' => '1444x1280',
                        'hostUrl' => 'http://ci/failures/$USER/',
                    ],
                    'debugBarSelectors' => [
                        'message' => '#debugBar .message',
                        'queries' => '#debugBar .queries',
                    ],
                    'siteFilters' => [
                        '/images/' => 'http://dev.environment/images/',
                        '/js/' => 'http://dev.environment/js/',
                    ],
                    'trackJs' => ['errors' => true, 'warns' => true, 'logs' => true, 'trim' => 1000],
                ])
            )
            ->withExtension(
                new Extension(MinkExtension::class, [
                    'default_session' => 'browserkit_http',
                    'base_url' => 'http://localhost:8531/',
                    'sessions' => [
                        'browserkit_http' => ['browserkit_http' => null],
                    ],
                ])
            )
    );
