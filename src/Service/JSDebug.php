<?php

declare(strict_types=1);

namespace FailAid\Service;

use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Session;

/**
 * JSDebug class.
 */
class JSDebug
{
    /** @var array<string, mixed> */
    private static array $trackJs;

    /** @param array<string, mixed> $trackJs */
    public static function setOptions(array $trackJs): void
    {
        self::$trackJs = $trackJs;
    }

    /** @return array<string, mixed> */
    public static function getOptions(): array
    {
        return self::$trackJs;
    }

    /** @return array<string> */
    public static function getJsErrors(Session $session): array
    {
        return self::handleRetrieval('errors', $session);
    }

    /** @return array<string> */
    public static function getJsLogs(Session $session): array
    {
        return self::handleRetrieval('logs', $session);
    }

    /** @return array<string> */
    public static function getJsWarns(Session $session): array
    {
        return self::handleRetrieval('warns', $session);
    }

    /** @return array<string> */
    private static function handleRetrieval(string $type, Session $session): array
    {
        $content = [];
        try {
            if (isset(self::$trackJs[$type]) && self::$trackJs[$type]) {
                $content = self::getJsFromPage($type, $session);
            }

            if (!empty(self::$trackJs['trim']) && \is_int(self::$trackJs['trim'])) {
                $content = self::trimArrayMessages($content, self::$trackJs['trim']);
            }
        } catch (UnsupportedDriverActionException $e) {
            // ignore...
        } catch (\Exception $e) {
            $content = [\sprintf('Unable to fetch js %s: %s', $type, $e->getMessage())];
        }

        return $content;
    }

    /** @return array<string> */
    private static function getJSFromPage(string $type, Session $session)
    {
        $var = \sprintf('window.js%s', ucfirst($type));

        if ('undefined' === $session->evaluateScript(\sprintf('typeof %s', $var))) {
            throw new \Exception(\sprintf('JS %s enabled but %s is undefined, please check implementation and on page load js errors.', $type, $var));
        }

        $errors = $session->evaluateScript('return ' . $var);

        if (!\is_array($errors) || empty($errors)) {
            return [];
        }

        return array_map(static fn (mixed $v): string => \is_scalar($v) ? (string) $v : '', $errors);
    }

    /**
     * @param array<string> $messages
     *
     * @return array<string>
     */
    private static function trimArrayMessages(array $messages, int $length)
    {
        array_walk($messages, static function (&$msg) use ($length) {
            $msg = substr($msg, 0, $length);
        });

        return $messages;
    }
}
