<?php

declare(strict_types=1);

namespace FailAid\Service;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Session;
use FailAid\Context\Contracts\ScreenshotInterface;

class Screenshot implements ScreenshotInterface
{
    public const SCREENSHOT_MODE_DEFAULT = 'default';

    public const SCREENSHOT_MODE_PNG = 'png';

    public const SCREENSHOT_MODE_HTML = 'html';

    /**
     * @var string
     */
    public static $screenshotMode;

    /**
     * @var string
     */
    public static $screenshotDir;

    /**
     * @var bool
     */
    public static $screenshotAutoClean = false;

    /**
     * @var array<int, string>
     */
    public static $screenshotSize = [];

    /**
     * @var string|null
     */
    public static $screenshotHostDirectory;

    /**
     * @var string|null
     */
    public static $screenshotHostUrl;

    /**
     * @var array<string, string>
     */
    public static $siteFilters;

    /**
     * @param array<string, mixed>  $options
     * @param array<string, string> $siteFilters
     */
    public static function setOptions(array $options, array $siteFilters): void
    {
        self::$screenshotDir = tempnam(sys_get_temp_dir(), date('Ymd-'));
        self::$screenshotMode = self::SCREENSHOT_MODE_DEFAULT;

        if (isset($options['directory']) && \is_string($options['directory'])) {
            self::$screenshotDir = (realpath($options['directory']) ?: '').\DIRECTORY_SEPARATOR.date('Ymd-');
        }

        if (isset($options['mode']) && \is_string($options['mode'])) {
            self::$screenshotMode = $options['mode'];
        }

        if (isset($options['autoClean'])) {
            self::$screenshotAutoClean = (bool) $options['autoClean'];
        }

        if (isset($options['size']) && \is_string($options['size'])) {
            self::$screenshotSize = explode('x', $options['size'], 2);
        }

        if (isset($options['hostDirectory']) && \is_string($options['hostDirectory'])) {
            self::$screenshotHostDirectory = rtrim(self::resolveEnvVarsInString($options['hostDirectory']), \DIRECTORY_SEPARATOR).
                \DIRECTORY_SEPARATOR.
                date('Ymd-');
        } else {
            self::$screenshotHostDirectory = null;
        }

        if (isset($options['hostUrl']) && \is_string($options['hostUrl'])) {
            self::$screenshotHostUrl = rtrim(self::resolveEnvVarsInString($options['hostUrl']), \DIRECTORY_SEPARATOR).
                \DIRECTORY_SEPARATOR.
                date('Ymd-');
        } else {
            self::$screenshotHostUrl = null;
        }

        self::$siteFilters = $siteFilters;
    }

    public static function resolveEnvVarsInString(string $string): string
    {
        return rtrim((string) shell_exec("echo $string"), \PHP_EOL);
    }

    public static function takeScreenshot(ElementInterface $page, DriverInterface $driver): string
    {
        if (!$page->getHtml()) {
            throw new \Exception('Unable to take screenshot, page content not found.');
        }

        $content = null;
        $filename = microtime(true);

        switch (self::$screenshotMode) {
            case self::SCREENSHOT_MODE_DEFAULT:
                try {
                    $content = $driver->getScreenshot();
                    $filename .= '.png';
                    self::handleResize(self::$screenshotSize, $driver);
                } catch (DriverException $e) {
                    $content = static::applySiteSpecificFilters($page->getHtml());
                    $filename .= '.html';
                }
                break;
            case self::SCREENSHOT_MODE_HTML:
                $content = static::applySiteSpecificFilters($page->getHtml());
                $filename .= '.html';
                break;
            case self::SCREENSHOT_MODE_PNG:
                try {
                    self::handleResize(self::$screenshotSize, $driver);
                    $content = $driver->getScreenshot();
                    $filename .= '.png';
                } catch (DriverException $e) {
                    throw new \Exception('unable to produce screenshot: '.$e->getMessage());
                }
                break;
        }

        file_put_contents(self::$screenshotDir.$filename, $content);

        if (self::$screenshotHostDirectory) {
            return 'file://'.self::$screenshotHostDirectory.$filename;
        } elseif (self::$screenshotHostUrl) {
            return self::$screenshotHostUrl.$filename;
        }

        return 'file://'.self::$screenshotDir.$filename;
    }

    public static function canTakeScreenshot(Session $session): bool
    {
        if ($session->isStarted()) {
            return true;
        }

        throw new \Exception('Session has not started yet.');
    }

    public static function applySiteSpecificFilters(string $content): string
    {
        $filters = self::getSiteSpecificFilters();

        $from = array_keys($filters);
        $to = array_values($filters);

        return str_replace($from, $to, $content);
    }

    /**
     * @param array<int, string> $size
     */
    private static function handleResize(array $size, DriverInterface $driver): void
    {
        if (!$size) {
            return;
        }

        $driver->resizeWindow((int) $size[0], (int) $size[1], 'current');
    }

    /**
     * Override this method if you're using Goutte to produce html screenshots and want to fix broken relative links for
     * assets.
     *
     * @example [
     *     '/images/' => 'http://dev.environment/images/',
     *     '/js/' => 'http://dev.environment/js/'
     * ]
     *
     * @return array<string, string>
     */
    protected static function getSiteSpecificFilters(): array
    {
        return self::$siteFilters;
    }
}
