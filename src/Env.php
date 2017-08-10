<?php

namespace Dais;

class Env
{
    /**
     * Get the value of an environment variable.
     *
     * Throws RuntimeException if the variable is not set.
     *
     * @param string $name
     *   Name of variable.
     * @param string $errorMessage
     *   Error message of exception, if the variable is not set.
     *
     * @return string|false
     *   Value of variable, or false if not set.
     */
    public function get($name, $errorMessage = null)
    {
        $value = getenv($name);
        if ($value === false) {
            if (!$errorMessage) {
                $errorMessage = $name . " env variable not set.";
            }
            throw new \RuntimeException($errorMessage);
        }
        return $value;
    }
}
