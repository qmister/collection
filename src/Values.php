<?php


namespace tp5er;


use Closure;
use Countable;

class Values
{
    /**
     * Encode HTML special characters in a string.
     * @param      $value
     * @param bool $doubleEncode
     * @return string
     */
    public static function e($value, $doubleEncode = true)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }

    /**
     * Return the default value of the given value.
     * @param mixed $value
     * @return mixed
     */
    public static function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }

    /**
     * Determine if a value is "filled".
     * @param mixed $value
     * @return bool
     */
    public static function filled($value)
    {
        return !static::blank($value);
    }

    /**
     * Determine if the given value is "blank".
     * @param mixed $value
     * @return bool
     */
    public static function blank($value)
    {
        if (is_null($value)) {
            return true;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        if (is_numeric($value) || is_bool($value)) {
            return false;
        }
        if ($value instanceof Countable) {
            return count($value) === 0;
        }
        return empty($value);
    }

    /**
     * Call the given Closure with the given value then return the value.
     * @param mixed         $value
     * @param callable|null $callback
     * @return mixed
     */
    public static function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            return new HigherOrderTapProxy($value);
        }
        $callback($value);
        return $value;
    }

    /**
     * Transform the given value if it is present.
     * @param mixed    $value
     * @param callable $callback
     * @param mixed    $default
     * @return mixed|null
     */
    public static function transform($value, callable $callback, $default = null)
    {
        if (static::filled($value)) {
            return $callback($value);
        }
        if (is_callable($default)) {
            return $default($value);
        }
        return $default;
    }
}