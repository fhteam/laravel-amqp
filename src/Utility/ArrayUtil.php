<?php

namespace Forumhouse\LaravelAmqp\Utility;

/**
 * Various array utilities
 *
 * @package Forumhouse\LaravelAmqp\Utility
 */
class ArrayUtil
{
    /**
     * array_map_recursive, which is missing from PHP, with the same signature
     *
     * @param callable $func
     * @param array    $arr
     *
     * @return array
     */
    public static function arrayMapRecursive(callable $func, array $arr)
    {
        array_walk_recursive($arr, function (&$v) use ($func) {
            $v = $func($v);
        });
        return $arr;
    }

    /**
     * array_filter_recursive which is missing from PHP
     *
     * @param array    $input
     * @param callable $callback
     *
     * @return array
     */
    public static function arrayFilterRecursive(array $input, callable $callback)
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = static::arrayFilterRecursive($value, $callback);
            }
        }

        return array_filter($input, $callback);
    }

    /**
     * Returns array with all
     *
     * @param array $arr
     *
     * @return array
     */
    public static function removeNullsRecursive(array $arr)
    {
        return static::arrayFilterRecursive($arr, function ($var) {
            return !is_null($var);
        });
    }
}
