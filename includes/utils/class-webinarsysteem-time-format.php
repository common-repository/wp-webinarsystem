<?php

class WebinarSysteemTimeFormat {

    public static function seconds_to_human($seconds) {
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);

        if ($seconds == 0) {
            return '-';
        }

        if ($seconds < 60) {
            return sprintf(
                // translators: %d will be replaced with the minute.
                __('%d minute'), 1);
        }

        if ($minutes < 60) {
            return sprintf(
                // translators: %d will be replaced with the minute
                _n(
                '%d minute',
                '%d minutes',
                $minutes
            ), $minutes);
        }

        return sprintf(
            // translators: %d will be replaced with the minute
            _n(
            '%d hour',
            '%d hours',
            $hours
        ), $hours);
    }
}
