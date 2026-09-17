<?php

declare(strict_types=1);

namespace FailAid\Service;

/**
 * StaticCallerService class.
 */
class StaticCallerService
{
    /**
     * @param array<mixed> $params
     */
    public function call(string $class, string $function, array $params = []): mixed
    {
        /* @phpstan-ignore argument.type */
        return \call_user_func_array([$class, $function], $params);
    }
}
