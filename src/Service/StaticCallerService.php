<?php

declare(strict_types=1);

namespace FailAid\Service;

/**
 * StaticCallerService class.
 */
class StaticCallerService
{
    public function call($class, $function, array $params = [])
    {
        return \call_user_func_array("$class::$function", $params);
    }
}
