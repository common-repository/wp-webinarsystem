<?php

class WebinarSysteemCron
{
    static function update_cron_last_active() {
        update_option('wpws_cron_last_active', time());
    }

    static function was_active_within($minutes_ago = 30) {
        $last_active_at = get_option('wpws_cron_last_active');

        if ($last_active_at == null) {
            return false;
        }

        return time() - (int) $last_active_at < (60 * $minutes_ago);
    }
}
