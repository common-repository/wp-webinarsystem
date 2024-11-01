<?php

class WebinarSysteemDateTime
{
    public static function format_timezone($timezone)
    {
        $sign = ($timezone >= 0) ? '+' : '-';
        $timezone = str_replace(array("+", "-"), array(" ", " "), $timezone);
        $init = $timezone * 60 * 60;
        $hours = floor($init / 3600);
        $minutes = floor(($init / 60) % 60);

        return $sign . $hours . (($minutes > 0) ? '.' . $minutes : '');
    }

    public static function format_timezone_offset($offset) {
        return "GMT" . ($offset < 0 ? $offset : "+".$offset);
    }

    public static function get_timezone_offset($timezone, $at_date = null) {
        $dtz = new DateTimeZone($timezone);
        $time = $at_date == null
            ? new DateTime('now', $dtz)
            : new DateTime('@'.$at_date);

        return $dtz->getOffset($time) / 3600;
    }

    public static function get_timezone_abbreviation($timezone_id)
    {
        if (!$timezone_id) {
            return false;
        }

        $time_zones = timezone_identifiers_list();

        foreach ($time_zones as $time_zone_id) {
            if ($time_zone_id != $timezone_id) {
                continue;
            }

            $dateTime = new DateTime();
            $dateTime->setTimeZone(new DateTimeZone($timezone_id));

            return strtoupper($dateTime->format('T'));
        }
    }

    public static function strtotime_with_timezone($str, $timezone) {
        try {
            $datetime = new DateTime($str, new DateTimeZone($timezone));
            return strtotime($datetime->format('Y-m-d H:i:s'));
        } catch (Exception $exc) {
            // fall back to default..
            return strtotime($str);
        }
    }

    public static function strtotime_for_wp($str) {
        $tz_string = get_option('timezone_string');
        $tz_offset = get_option('gmt_offset', 0);

        // if site timezone option string exists, use it
        if (!empty($tz_string)) {
            return self::strtotime_with_timezone($str, $tz_string);
        }

        // get UTC offset, if it isn’t set then return UTC
        if ($tz_offset == 0) {
            return self::strtotime_with_timezone($str, 'UTC');
        }

        // check if we need to add a +
        $first_char = substr($tz_offset, 0, 1);

        if ($first_char != "-" &&
            $first_char != "+" &&
            $first_char != "U") {
            return self::strtotime_with_timezone($str, '+' . $tz_offset);
        }

        return self::strtotime_with_timezone($str, $tz_offset);
    }
}
