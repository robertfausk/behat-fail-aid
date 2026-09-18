<?php

declare(strict_types=1);

namespace FailAid\Service;

use Behat\Behat\Hook\Scope\ScenarioScope;

/**
 * Output class.
 */
class Output
{
    /** @var array<string, mixed> */
    private static array $output = [];

    /** @param array<string, mixed> $options */
    public static function setOptions(array $options): void
    {
        self::$output = $options;
    }

    /** @return array<string, mixed> */
    public static function getOptions(): array
    {
        return self::$output;
    }

    /**
     * @param array<string>|null $jsErrors
     * @param array<string>|null $jsLogs
     * @param array<string>|null $jsWarns
     */
    public static function getExceptionDetails(
        ?string $currentUrl,
        int|string|null $statusCode,
        ?string $featureFile,
        ?string $contextFile,
        ?string $screenshotPath,
        ?string $debugBarDetails,
        ?array $jsErrors,
        ?array $jsLogs,
        ?array $jsWarns,
        ?string $driver,
        ?ScenarioScope $scenario,
    ): string {
        $message = \PHP_EOL . \PHP_EOL;
        if (self::getOption('url')) {
            $message .= '[URL] ' . $currentUrl . \PHP_EOL;
        }

        if (self::getOption('status')) {
            $message .= '[STATUS] ' . $statusCode . \PHP_EOL;
        }

        if (self::getOption('feature')) {
            $message .= '[FEATURE] ' . $featureFile . \PHP_EOL;
        }

        if (self::getOption('tags') && null !== $scenario) {
            $tags = implode(', ', $scenario->getScenario()->getTags());
            $message .= rtrim('[TAGS] ' . $tags) . \PHP_EOL;
        }

        if (self::getOption('context')) {
            $message .= '[CONTEXT] ' . $contextFile . \PHP_EOL;
        }

        if (self::getOption('screenshot')) {
            $message .= '[SCREENSHOT] ' . $screenshotPath . \PHP_EOL;
        }

        if (self::getOption('driver')) {
            $message .= '[DRIVER] ' . $driver . \PHP_EOL;
        }

        if (self::getOption('rerun') && null !== $scenario) {
            $message .= '[RERUN] '
                . './vendor/bin/behat '
                . $featureFile
                . ':'
                . $scenario->getScenario()->getLine()
                . \PHP_EOL;
        }

        $glue = \PHP_EOL . '------' . \PHP_EOL;
        if ($jsErrors) {
            $message .= \PHP_EOL . '[JSERRORS] ' . implode($glue, $jsErrors) . \PHP_EOL;
        }

        if ($jsWarns) {
            $message .= \PHP_EOL . '[JSWARNS] ' . implode($glue, $jsWarns) . \PHP_EOL;
        }

        if ($jsLogs) {
            $message .= \PHP_EOL . '[JSLOGS] ' . implode($glue, $jsLogs) . \PHP_EOL;
        }

        if ($debugBarDetails) {
            $message .= \PHP_EOL . '[DEBUG BAR INFO]' . \PHP_EOL;
            $message .= $debugBarDetails;
        }

        return $message;
    }

    public static function getOption(string $key): mixed
    {
        if (!isset(self::$output[$key])) {
            throw new \Exception("Undefined output option '$key' provided.");
        }

        return self::$output[$key];
    }

    public static function setOption(string $key, mixed $value): void
    {
        self::$output[$key] = $value;
    }

    /**
     * @param string $expected
     * @param string $actual
     * @param string $message
     *
     * @return string
     */
    public static function provideDiff($expected, $actual, $message = null)
    {
        return 'Mismatch: (- expected, + actual)' . \PHP_EOL . \PHP_EOL .
            '- ' . $expected . \PHP_EOL .
            '+ ' . $actual . \PHP_EOL . \PHP_EOL .
            'Info: ' . $message;
    }
}
