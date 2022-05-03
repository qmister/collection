<?php

namespace tp5er;


use Closure;
use Traversable;
use tp5er\Traits\Macroable;
use ArrayAccess;
use InvalidArgumentException;


class Arr
{
    use Macroable;

    /**
     * Determine whether the given value is array accessible.
     * @param mixed $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     * @param array  $array
     * @param string $key
     * @param mixed  $value
     * @return array
     */
    public static function add(array $array, $key, $value)
    {
        if (is_null(static::get($array, $key))) {
            static::set($array, $key, $value);
        }

        return $array;
    }

    /**
     * Collapse an array of arrays into a single array.
     * @param iterable $array
     * @return array
     */
    public static function collapse($array)
    {
        $results = [];
        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (!is_array($values)) {
                continue;
            }
            $results[] = $values;
        }
        return array_merge([], ...$results);
    }

    /**
     * Cross join the given arrays, returning all possible permutations.
     * @param iterable ...$arrays
     * @return array
     */
    public static function crossJoin(...$arrays)
    {
        $results = [[]];
        foreach ($arrays as $index => $array) {
            $append = [];
            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;
                    $append[]        = $product;
                }
            }
            $results = $append;
        }
        return $results;
    }

    /**
     * Divide an array into two arrays. One with keys and the other with values.
     * @param array $array
     * @return array
     */
    public static function divide($array)
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     * @param iterable $array
     * @param string   $prepend
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }
        return $results;
    }

    /**
     * Convert a flatten "dot" notation array into an expanded array.
     * @param iterable $array
     * @return array
     */
    public static function undot($array)
    {
        $results = [];
        foreach ($array as $key => $value) {
            static::set($results, $key, $value);
        }
        return $results;
    }

    /**
     * Get all of the given array except for a specified array of keys.
     * @param array        $array
     * @param array|string $keys
     * @return array
     */
    public static function except($array, $keys)
    {
        static::forget($array, $keys);

        return $array;
    }

    /**
     * Determine if the given key exists in the provided array.
     * @param ArrayAccess|array $array
     * @param string|int        $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Return the first element in an array passing a given truth test.
     * @param iterable      $array
     * @param callable|null $callback
     * @param mixed         $default
     * @return mixed
     */
    public static function first($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return Values::value($default);
            }

            foreach ($array as $item) {
                return $item;
            }
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return Values::value($default);
    }

    /**
     * Return the last element in an array passing a given truth test.
     * @param array         $array
     * @param callable|null $callback
     * @param mixed         $default
     * @return mixed
     */
    public static function last($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? Values::value($default) : end($array);
        }

        return static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     * @param iterable $array
     * @param int      $depth
     * @return array
     */
    public static function flatten($array, $depth = INF)
    {
        $result = [];
        foreach ($array as $item) {
            $item = $item instanceof Collection ? $item->all() : $item;
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $values = $depth === 1
                    ? array_values($item)
                    : static::flatten($item, $depth - 1);
                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     * @param array        $array
     * @param array|string $keys
     * @return void
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;
        $keys = (array)$keys;
        if (count($keys) === 0) {
            return;
        }
        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);
                continue;
            }
            $parts = explode('.', $key);
            // clean up before each pass
            $array = &$original;
            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }
            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Get an item from an array using "dot" notation.
     * @param ArrayAccess|array       $array
     * @param string|int|null|Closure $key
     * @param mixed                   $default
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if ($key instanceof Closure) {
            return $key($array, $default);
        }
        if (!static::accessible($array)) {
            return Values::value($default);
        }
        if (is_null($key)) {
            return $array;
        }
        if (static::exists($array, $key)) {
            return $array[$key];
        }
        if (strpos($key, '.') === false) {
            return isset($array[$key]) ? $array[$key] : Values::value($default);
        }
        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return Values::value($default);
            }
        }

        return $array;
    }

    /**
     * @param $array
     * @param $name
     * @param $keepKeys
     * @return array
     */
    public static function column($array, $name, $keepKeys = true)
    {
        $result = [];
        if ($keepKeys) {
            foreach ($array as $k => $element) {
                $result[$k] = static::get($element, $name);
            }
        } else {
            foreach ($array as $element) {
                $result[] = static::get($element, $name);
            }
        }
        return $result;
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     * @param ArrayAccess|array $array
     * @param string|array      $keys
     * @return bool
     */
    public static function has($array, $keys)
    {
        $keys = (array)$keys;
        if (!$array || $keys === []) {
            return false;
        }
        foreach ($keys as $key) {
            $subKeyArray = $array;
            if (static::exists($array, $key)) {
                continue;
            }
            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Determine if any of the keys exist in an array using "dot" notation.
     * @param ArrayAccess|array $array
     * @param string|array      $keys
     * @return bool
     */
    public static function hasAny($array, $keys)
    {
        if (is_null($keys)) {
            return false;
        }
        $keys = (array)$keys;
        if (!$array) {
            return false;
        }
        if ($keys === []) {
            return false;
        }
        foreach ($keys as $key) {
            if (static::has($array, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determines if an array is associative.
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     * @param array $array
     * @return bool
     */
    public static function isAssoc(array $array)
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    /**
     * Get a subset of the items from the given array.
     * @param array        $array
     * @param array|string $keys
     * @return array
     */
    public static function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }

    /**
     * Pluck an array of values from an array.
     * @param iterable          $array
     * @param string|array      $value
     * @param string|array|null $key
     * @return array
     */
    public static function pluck($array, $value, $key = null)
    {
        $results = [];
        list($value, $key) = static::explodePluckParameters($value, $key);
        foreach ($array as $item) {
            $itemValue = static::dataGet($item, $value);
            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = static::dataGet($item, $key);
                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string)$itemKey;
                }
                $results[$itemKey] = $itemValue;
            }
        }
        return $results;
    }

    /**
     * The anonymous function can be used in the array of grouping keys as well:
     * @param $array
     * @param $key
     * @param $groups
     * @return array|mixed
     */
    public static function index($array, $key, $groups = [])
    {
        $results = [];
        $groups  = (array)$groups;
        if ($array instanceof Collection) {
            $array = $array->toArray();
        }
        foreach ($array as $element) {
            $lastArray = &$results;
            foreach ($groups as $group) {
                $value = static::get($element, $group);
                if (!array_key_exists($value, $lastArray)) {
                    $lastArray[$value] = [];
                }
                $lastArray = &$lastArray[$value];
            }
            if ($key === null) {
                if (!empty($groups)) {
                    $lastArray[] = $element;
                }
            } else {
                $value = static::get($element, $key);
                if ($value !== null) {
                    if (is_float($value)) {
                        $value = str_replace(',', '.', (string)$value);
                    }
                    $lastArray[$value][] = $element;
                }
            }
            unset($lastArray);
        }
        return $results;
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     * @param string|array      $value
     * @param string|array|null $key
     * @return array
     */
    protected static function explodePluckParameters($value, $key)
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * Push an item onto the beginning of an array.
     * @param array $array
     * @param mixed $value
     * @param mixed $key
     * @return array
     */
    public static function prepend($array, $value, $key = null)
    {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    /**
     * Get a value from the array, and remove it.
     * @param array  $array
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);

        static::forget($array, $key);

        return $value;
    }

    /**
     * Get one or a specified number of random values from an array.
     * @param array    $array
     * @param int|null $number
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function random($array, $number = null)
    {
        $requested = is_null($number) ? 1 : $number;
        $count     = count($array);
        if ($requested > $count) {
            throw new InvalidArgumentException(
                "You requested {$requested} items, but there are only {$count} items available."
            );
        }
        if (is_null($number)) {
            return $array[array_rand($array)];
        }
        if ((int)$number === 0) {
            return [];
        }
        $keys    = array_rand($array, $number);
        $results = [];
        foreach ((array)$keys as $key) {
            $results[] = $array[$key];
        }
        return $results;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     * If no key is given to the method, the entire array will be replaced.
     * @param array  $array
     * @param string $key
     * @param mixed  $value
     * @return array
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        $keys = explode('.', $key);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;
        return $array;
    }

    /**
     * Shuffle the given array and return the result.
     * @param array    $array
     * @param int|null $seed
     * @return array
     */
    public static function shuffle($array, $seed = null)
    {
        if (is_null($seed)) {
            shuffle($array);
        } else {
            mt_srand($seed);
            shuffle($array);
            mt_srand();
        }
        return $array;
    }

    /**
     * Sort the array using the given callback or "dot" notation.
     * @param array                $array
     * @param callable|string|null $callback
     * @return array
     */
    public static function sort($array, $callback = null)
    {
        return Collection::make($array)->sortBy($callback)->all();
    }

    /**
     * Recursively sort an array by keys and values.
     * @param array $array
     * @return array
     */
    public static function sortRecursive($array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = static::sortRecursive($value);
            }
        }
        if (static::isAssoc($array)) {
            ksort($array);
        } else {
            sort($array);
        }
        return $array;
    }

    /**
     * Conditionally compile classes from an array into a CSS class list.
     * @param array $array
     * @return string
     */
    public static function toCssClasses($array)
    {
        $classList = static::wrap($array);
        $classes   = [];
        foreach ($classList as $class => $constraint) {
            if (is_numeric($class)) {
                $classes[] = $constraint;
            } elseif ($constraint) {
                $classes[] = $class;
            }
        }
        return implode(' ', $classes);
    }

    /**
     * Convert the array into a query string.
     * @param array $array
     * @return string
     */
    public static function query($array)
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Filter the array using the given callback.
     * @param array    $array
     * @param callable $callback
     * @return array
     */
    public static function where($array, callable $callback)
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Filter items where the value is not null.
     * @param array $array
     * @return array
     */
    public static function whereNotNull($array)
    {
        return static::where($array, function ($value) {
            return !is_null($value);
        });
    }

    /**
     * If the given value is not an array and not null, wrap it in one.
     * @param mixed $value
     * @return array
     */
    public static function wrap($value)
    {
        if (is_null($value)) {
            return [];
        }
        return is_array($value) ? $value : [$value];
    }

    /**
     * For example,
     * ```php
     * $array = [
     *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
     *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
     *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
     * ];
     * $result = ArrayHelper::map($array, 'id', 'name');
     * // the result is:
     * // [
     * //     '123' => 'aaa',
     * //     '124' => 'bbb',
     * //     '345' => 'ccc',
     * // ]
     * $result = ArrayHelper::map($array, 'id', 'name', 'class');
     * // the result is:
     * // [
     * //     'x' => [
     * //         '123' => 'aaa',
     * //         '124' => 'bbb',
     * //     ],
     * //     'y' => [
     * //         '345' => 'ccc',
     * //     ],
     * // ]
     * @param $array
     * @param $from
     * @param $to
     * @param $group
     * @return array
     */
    public static function map($array, $from, $to, $group = null)
    {
        $result = [];
        foreach ($array as $element) {
            $key   = static::get($element, $from);
            $value = static::get($element, $to);
            if ($group !== null) {
                $result[static::get($element, $group)][$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * @param $a
     * @param $b
     * @return mixed|null
     */
    public static function merge($a, $b)
    {
        $args = func_get_args();
        $res  = array_shift($args);
        while (!empty($args)) {
            foreach (array_shift($args) as $k => $v) {
                if (is_int($k)) {
                    if (array_key_exists($k, $res)) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }
        return $res;
    }

    /**
     * @param $needle
     * @param $haystack
     * @param $strict
     * @return bool
     */
    public static function isIn($needle, $haystack, $strict = false)
    {
        if ($haystack instanceof Traversable) {
            foreach ($haystack as $value) {
                if ($needle == $value && (!$strict || $needle === $value)) {
                    return true;
                }
            }
        } elseif (is_array($haystack)) {
            return in_array($needle, $haystack, $strict);
        } else {
            throw new InvalidArgumentException('Argument $haystack must be an array or implement Traversable');
        }

        return false;
    }


    /**
     * Set an item on an array or object using dot notation.
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $value
     * @param bool         $overwrite
     * @return mixed
     */
    public static function dataSet(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);
        if (($segment = array_shift($segments)) === '*') {
            if (!Arr::accessible($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    static::dataSet($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (Arr::accessible($target)) {
            if ($segments) {
                if (!Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }
                static::dataSet($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                static::dataSet($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];

            if ($segments) {
                static::dataSet($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }

    /**
     * Get an item from an array or object using "dot" notation.
     * @param mixed                     $target
     * @param string|array|int|\Closure $key
     * @param mixed                     $default
     * @return mixed
     */
    public static function dataGet($target, $key, $default = null)
    {
        if ($key instanceof \Closure) {
            return $key($target, $default);
        }
        if (is_null($key)) {
            return $target;
        }
        $key = is_array($key) ? $key : explode('.', $key);
        while (!is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (!is_array($target)) {
                    return Values::value($default);
                }
                $result = [];
                foreach ($target as $item) {
                    $result[] = static::dataGet($item, $key);
                }
                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }
            if (static::accessible($target) && static::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return Values::value($default);
            }
        }
        return $target;
    }

    /**
     * Fill in data where it's missing.
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $value
     * @return mixed
     */
    public static function dataFill(&$target, $key, $value)
    {
        return static::dataSet($target, $key, $value, false);
    }

    /**
     * Return the given value, optionally passed through the given callback.
     * @param mixed         $value
     * @param callable|null $callback
     * @return mixed
     */
    public static function with($value, callable $callback = null)
    {
        return is_null($callback) ? $value : $callback($value);
    }

    /**
     * Get the first element of an array. Useful for method chaining.
     * @param array $array
     * @return mixed
     */
    public static function head($array)
    {
        return reset($array);
    }

}