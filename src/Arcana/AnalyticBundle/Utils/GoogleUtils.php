<?php

namespace Arcana\AnalyticBundle\Utils;


class GoogleUtils {

    /**
     * Case insensitive array_key_exists function, also returns
     * matching key.
     *
     * @param String $key
     * @param Array $search
     * @return String Matching array key
     */
    public static function array_key_exists_nc($key, $search)
    {
        if (array_key_exists($key, $search)) {
            return $key;
        }
        if (!(is_string($key) && is_array($search))) {
            return false;
        }
        $key = strtolower($key);
        foreach ($search as $k => $v) {
            if (strtolower($k) == $key) {
                return $k;
            }
        }

        return false;
    }

    /**
     * converts seconds to HH:mm:ss format
     * @param $sec
     * @param bool $padHours
     * @return string
     */
    public static function sec2hms ($sec, $padHours = false) {
        $hms = "";
        $hours = intval(intval($sec) / 3600);
        $hms .= ($padHours)
            ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
            : $hours. ':';
        $minutes = intval(($sec / 60) % 60);
        $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';
        $seconds = intval($sec % 60);
        $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
        return $hms;
    }

} 