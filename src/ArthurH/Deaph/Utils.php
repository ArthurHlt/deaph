<?php
/**
 * Created by IntelliJ IDEA.
 * User: arthurhalet
 * Date: 04/08/14
 * Time: 22:59
 */

namespace ArthurH\Deaph;


class Utils
{

    public static function rglob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, Utils::rglob($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    }

    public static function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } else if ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        }
        $seconds = round($seconds, 2);
        if ($seconds > 59) {
            $seconds = (int)$seconds;
            $seconds = sprintf("%02.2dm%02.2ds", floor($seconds / 60), $seconds % 60);
        } else {
            $seconds .= 's';
        }

        return $seconds;
    }

    public static function echoer($string)
    {
        echo $string;
        flush();
    }

    public static function array_merge_recursive_distinct(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
                $merged [$key] = Utils::array_merge_recursive_distinct($merged [$key], $value);
            } else {
                $merged [$key] = $value;
            }
        }

        return $merged;
    }
} 