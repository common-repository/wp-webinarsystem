<?php

class WebinarSysteemWebHooks {

    protected  static function format_date($date) {
        // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
        return date('Y-m-d H:i:s', $date);
    }

    protected  static function send_webhook($webhook_config, $post_id, $attendee, $extra_data = []) {
        return false;
    }

    public static function send_new_registration($post_id, $attendee) {
        WebinarSysteemWebHooks::send_webhook(
            '_wswebinar_new_registration_webhook',
            $post_id, $attendee);
    }

    public static function send_attended($post_id, $attendee) {
        WebinarSysteemWebHooks::send_webhook(
            '_wswebinar_attended_webinar_webhook',
            $post_id,
            $attendee,
            ['joined_at' => WebinarSysteemWebHooks::format_date(strtotime('now'))]);
    }

    public  static function test_webhook($webhook_url, $extra_data = []) {
        return false;
    }

    public static function test_new_registration($webhook_url) {
        return WebinarSysteemWebHooks::test_webhook($webhook_url);
    }

    public static function test_attended_webinar($webhook_url) {
        return WebinarSysteemWebHooks::test_webhook(
            $webhook_url,
            ['joined_at' => WebinarSysteemWebHooks::format_date(strtotime('now'))]);
    }
}
