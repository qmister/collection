<?php

namespace tp5er;

use RuntimeException;
use tp5er\Traits\Macroable;


class Str
{
    use Macroable;

    /**
     * The cache of snake-cased words.
     * @var array
     */
    protected static $snakeCache = [];

    /**
     * The cache of camel-cased words.
     * @var array
     */
    protected static $camelCache = [];

    /**
     * The cache of studly-cased words.
     * @var array
     */
    protected static $studlyCache = [];


    /**
     * 在给定值后返回字符串的其余部分
     * Return the remainder of a string after the first occurrence of a given value.
     * @param string $subject
     * @param string $search
     * @return string
     */
    public static function after($subject, $search)
    {
        return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    /**
     * Return the remainder of a string after the last occurrence of a given value.
     * @param string $subject
     * @param string $search
     * @return string
     */
    public static function afterLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, (string)$search);

        if ($position === false) {
            return $subject;
        }

        return substr($subject, $position + strlen($search));
    }


    /**
     * 在给定值之前获取字符串的部分。
     * Get the portion of a string before the first occurrence of a given value.
     * @param string $subject
     * @param string $search
     * @return string
     */
    public static function before($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }
        $result = strstr($subject, (string)$search, true);
        return $result === false ? $subject : $result;
    }

    /**
     * Get the portion of a string before the last occurrence of a given value.
     * @param string $subject
     * @param string $search
     * @return string
     */
    public static function beforeLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }
        $pos = mb_strrpos($subject, $search);
        if ($pos === false) {
            return $subject;
        }
        return static::substr($subject, 0, $pos);
    }

    /**
     * Get the portion of a string between two given values.
     * @param string $subject
     * @param string $from
     * @param string $to
     * @return string
     */
    public static function between($subject, $from, $to)
    {
        if ($from === '' || $to === '') {
            return $subject;
        }
        return static::beforeLast(static::after($subject, $from), $to);
    }

    /**
     * 下划线转驼峰(首字母小写)
     * Convert a value to camel case.
     * @param string $value
     * @return string
     */
    public static function camel($value)
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }

        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }

    /**
     * 驼峰转下划线
     * Convert a string to snake case.
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    public static function snake($value, $delimiter = '_')
    {
        $key = $value;
        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }
        return static::$snakeCache[$key][$delimiter] = $value;
    }

    /**
     * 将字符串转换为kebab。
     * Convert a string to kebab case.
     * @param string $value
     * @return string
     */
    public static function kebab($value)
    {
        return static::snake($value, '-');
    }

    /**
     * 检查字符串中是否包含某些字符串
     * Determine if a given string contains a given substring.
     * @param string          $haystack
     * @param string|string[] $needles
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a given string contains all array values.
     * @param string   $haystack
     * @param string[] $needles
     * @return bool
     */
    public static function containsAll($haystack, array $needles)
    {
        foreach ($needles as $needle) {
            if (!static::contains($haystack, $needle)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 检查字符串是否以某些字符串结尾
     * Determine if a given string ends with a given substring.
     * @param string          $haystack
     * @param string|string[] $needles
     * @return bool
     */
    public static function endsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if (
                $needle !== '' && $needle !== null
                && substr($haystack, -strlen($needle)) === (string)$needle
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 用给定值的单个实例限制字符串
     * Cap a string with a single instance of a given value.
     * @param string $value
     * @param string $cap
     * @return string
     */
    public static function finish($value, $cap)
    {
        $quoted = preg_quote($cap, '/');

        return preg_replace('/(?:' . $quoted . ')+$/u', '', $value) . $cap;
    }

    /**
     * 确定给定的字符串是否与给定的模式匹配。
     * Determine if a given string matches a given pattern.
     * @param string|array $pattern
     * @param string       $value
     * @return bool
     */
    public static function is($pattern, $value)
    {
        $patterns = Arr::wrap($pattern);
        $value    = (string)$value;
        if (empty($patterns)) {
            return false;
        }
        foreach ($patterns as $pattern) {
            $pattern = (string)$pattern;
            // If the given value is an exact match we can of course return true right
            // from the beginning. Otherwise, we will translate asterisks and do an
            // actual pattern match against the two strings to see if they match.
            if ($pattern == $value) {
                return true;
            }
            $pattern = preg_quote($pattern, '#');
            // Asterisks are translated into zero-or-more regular expression wildcards
            // to make it convenient to check if the strings starts with the given
            // pattern such as "library/*", making any string check convenient.
            $pattern = str_replace('\*', '.*', $pattern);

            if (preg_match('#^' . $pattern . '\z#u', $value) === 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取字符串的长度
     * Return the length of the given string.
     * @param string      $value
     * @param string|null $encoding
     * @return int
     */
    public static function length($value, $encoding = null)
    {
        if ($encoding) {
            return mb_strlen($value, $encoding);
        }
        return mb_strlen($value);
    }

    /**
     * 限制字符串中的字符数。
     * Limit the number of characters in a string.
     * @param string $value
     * @param int    $limit
     * @param string $end
     * @return string
     */
    public static function limit($value, $limit = 100, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }


    /**
     * 限制字符串中单词的数量。
     * Limit the number of words in a string.
     * @param string $value
     * @param int    $words
     * @param string $end
     * @return string
     */
    public static function words($value, $words = 100, $end = '...')
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches);
        if (!isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }
        return rtrim($matches[0]) . $end;
    }


    /**
     * Masks a portion of a string with a repeated character.
     * @param string   $string
     * @param string   $character
     * @param int      $index
     * @param int|null $length
     * @param string   $encoding
     * @return string
     */
    public static function mask($string, $character, $index, $length = null, $encoding = 'UTF-8')
    {
        if ($character === '') {
            return $string;
        }
        if (is_null($length) && PHP_MAJOR_VERSION < 8) {
            $length = mb_strlen($string, $encoding);
        }
        $segment = mb_substr($string, $index, $length, $encoding);
        if ($segment === '') {
            return $string;
        }
        $start = mb_substr($string, 0, mb_strpos($string, $segment, 0, $encoding), $encoding);
        $end   = mb_substr($string, mb_strpos($string, $segment, 0, $encoding) + mb_strlen($segment, $encoding));
        return $start . str_repeat(mb_substr($character, 0, 1, $encoding), mb_strlen($segment, $encoding)) . $end;
    }

    /**
     * Get the string matching the given pattern.
     * @param string $pattern
     * @param string $subject
     * @return string
     */
    public static function match($pattern, $subject)
    {
        preg_match($pattern, $subject, $matches);
        if (!$matches) {
            return '';
        }
//        return $matches[1] ?? $matches[0];
        return isset($matches[1]) ? $matches[1] : $matches[0];
    }

    /**
     * Get the string matching the given pattern.
     * @param string $pattern
     * @param string $subject
     * @return Collection
     */
    public static function matchAll($pattern, $subject)
    {
        preg_match_all($pattern, $subject, $matches);
        if (empty($matches[0])) {
            return Collection::make([]);
        }
        return Collection::make(isset($matches[1]) ? $matches[1] : $matches[0]);
    }

    /**
     * Pad both sides of a string with another.
     * @param string $value
     * @param int    $length
     * @param string $pad
     * @return string
     */
    public static function padBoth($value, $length, $pad = ' ')
    {
        return str_pad($value, $length, $pad, STR_PAD_BOTH);
    }

    /**
     * Pad the left side of a string with another.
     * @param string $value
     * @param int    $length
     * @param string $pad
     * @return string
     */
    public static function padLeft($value, $length, $pad = ' ')
    {
        return str_pad($value, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * Pad the right side of a string with another.
     * @param string $value
     * @param int    $length
     * @param string $pad
     * @return string
     */
    public static function padRight($value, $length, $pad = ' ')
    {
        return str_pad($value, $length, $pad, STR_PAD_RIGHT);
    }


    /**
     *  生成一个更真实的随机字母数字字符串。
     * Generate a more truly "random" alpha-numeric string.
     * @param int $length
     * @return string
     * @throws \Exception
     */
    public static function random($length = 16)
    {
        $string = '';
        while (($len = strlen($string)) < $length) {
            $size   = $length - $len;
            $bytes  = static::randomBytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        return $string;
    }

    /**
     * Generate a more truly "random" bytes.
     * @param int $length
     * @return string
     * @throws RuntimeException|\Exception
     */
    public static function randomBytes($length = 16)
    {
        if (function_exists('random_bytes')) {
            $bytes = random_bytes($length);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($bytes === false || $strong === false) {
                throw new RuntimeException('Unable to generate random string.');
            }
        } else {
            throw new RuntimeException('OpenSSL extension is required for PHP 5 users.');
        }
        return $bytes;
    }

    /**
     * Repeat the given string.
     * @param string $string
     * @param int    $times
     * @return string
     */
    public static function repeat($string, $times)
    {
        return str_repeat($string, $times);
    }

    /**
     * 按顺序将字符串中的给定值替换为一个数组。
     * Replace a given value in the string sequentially with an array.
     * @param string                    $search
     * @param array<int|string, string> $replace
     * @param string                    $subject
     * @return string
     */
    public static function replaceArray($search, array $replace, $subject)
    {
        $segments = explode($search, $subject);
        $result = array_shift($segments);
        foreach ($segments as $segment) {
            $result .= (array_shift($replace) !== null ? array_shift($replace) : $search) . $segment;
        }
        return $result;
    }

    /**
     * Replace the given value in the given string.
     * @param string|string[] $search
     * @param string|string[] $replace
     * @param string|string[] $subject
     * @return string
     */
    public static function replace($search, $replace, $subject)
    {
        return str_replace($search, $replace, $subject);
    }

    /**
     * 替换字符串中给定值的第一个出现。
     * Replace the first occurrence of a given value in the string.
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public static function replaceFirst($search, $replace, $subject)
    {
        if ($search === '') {
            return $subject;
        }
        $position = strpos($subject, $search);
        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }
        return $subject;
    }

    /**
     * 替换字符串中给定值的最后出现。
     * Replace the last occurrence of a given value in the string.
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public static function replaceLast($search, $replace, $subject)
    {
        if ($search === '') {
            return $subject;
        }
        $position = strrpos($subject, $search);
        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }
        return $subject;
    }

    /**
     * Remove any occurrence of the given string in the subject.
     * @param string|array<string> $search
     * @param string               $subject
     * @param bool                 $caseSensitive
     * @return string
     */
    public static function remove($search, $subject, $caseSensitive = true)
    {
        $subject = $caseSensitive
            ? str_replace($search, '', $subject)
            : str_ireplace($search, '', $subject);

        return $subject;
    }


    /**
     * Begin a string with a single instance of a given value.
     * @param string $value
     * @param string $prefix
     * @return string
     */
    public static function start($value, $prefix)
    {
        $quoted = preg_quote($prefix, '/');
        return $prefix . preg_replace('/^(?:' . $quoted . ')+/u', '', $value);
    }


    /**
     * 转为首字母大写的标题格式
     * Convert the given string to title case.
     * @param string $value
     * @return string
     */
    public static function title($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convert the given string to title case for each word.
     * @param string $value
     * @return string
     */
    public static function headline($value)
    {
        $parts = explode(' ', $value);

        $parts = count($parts) > 1
            ? $parts = array_map([static::class, 'title'], $parts)
            : $parts = array_map([static::class, 'title'], static::ucsplit(implode('_', $parts)));

        $collapsed = static::replace(['-', '_', ' '], '_', implode('_', $parts));

        return implode(' ', array_filter(explode('_', $collapsed)));
    }


    /**
     * 检查字符串是否以某些字符串开头
     * Determine if a given string starts with a given substring.
     * @param string          $haystack
     * @param string|string[] $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ((string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * 字符串转小写
     * Convert the given string to lower-case.
     * @param string $value
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * 字符串转大写
     * Convert the given string to upper-case.
     * @param string $value
     * @return string
     */
    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * 下划线转驼峰(首字母大写)
     * Convert a value to studly caps case.
     * @param string $value
     * @return string
     */
    public static function studly($value)
    {
        $key = $value;
        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }
        $words       = explode(' ', static::replace(['-', '_'], ' ', $value));
        $studlyWords = array_map(function ($word) {
            return static::ucfirst($word);
        }, $words);

        return static::$studlyCache[$key] = implode($studlyWords);
    }


    /**
     * 截取字符串
     * Make a string's first character uppercase.
     * @param string $string
     * @return string
     */
    public static function ucfirst($string)
    {
        return static::upper(static::substr($string, 0, 1)) . static::substr($string, 1);
    }

    /**
     * 返回由起始和长度参数指定的字符串部分。
     * Returns the portion of the string specified by the start and length parameters.
     * @param string   $string
     * @param int      $start
     * @param int|null $length
     * @return string
     */
    public static function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    /**
     * Returns the number of substring occurrences.
     * @param string   $haystack
     * @param string   $needle
     * @param int      $offset
     * @param int|null $length
     * @return int
     */
    public static function substrCount($haystack, $needle, $offset = 0, $length = null)
    {
        if (!is_null($length)) {
            return substr_count($haystack, $needle, $offset, $length);
        } else {
            return substr_count($haystack, $needle, $offset);
        }
    }

    /**
     * Replace text within a portion of a string.
     * @param string|array   $string
     * @param string|array   $replace
     * @param array|int      $offset
     * @param array|int|null $length
     * @return string|array
     */
    public static function substrReplace($string, $replace, $offset = 0, $length = null)
    {
        if ($length === null) {
            $length = strlen($string);
        }

        return substr_replace($string, $replace, $offset, $length);
    }

    /**
     * Swap multiple keywords in a string with other keywords.
     * @param array  $map
     * @param string $subject
     * @return string
     */
    public static function swap(array $map, $subject)
    {
        return strtr($subject, $map);
    }


    /**
     * Split a string into pieces by uppercase characters.
     * @param string $string
     * @return array
     */
    public static function ucsplit($string)
    {
        return preg_split('/(?=\p{Lu})/u', $string, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Get the number of words a string contains.
     * @param string $string
     * @return int
     */
    public static function wordCount($string)
    {
        return str_word_count($string);
    }

    /**
     * Remove all strings from the casing caches.
     * @return void
     */
    public static function flushCache()
    {
        static::$snakeCache  = [];
        static::$camelCache  = [];
        static::$studlyCache = [];
    }
}