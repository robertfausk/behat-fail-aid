<?php

declare(strict_types=1);

namespace FailAid\Context\Contracts;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\ElementInterface;

interface ScreenshotInterface
{
    public static function takeScreenshot(ElementInterface $page, DriverInterface $driver): string;
}
