<?php

class WebinarSysteemConfirmationTokenParser
{
    static $cache = [];

    public static function parse($token) {
        if ($token == null || empty($token)) {
            return null;
        }

        // check the cache so we don't reload?
        if (array_key_exists($token, self::$cache)) {
            return self::$cache[$token];
        }

        $data = WebinarSysteemBase64::decode_array($token);

        // parse the token (["123", "517039123099622077"])
        if ($data == null || !is_array($data) || count($data) !== 2) {
            return null;
        }

        // get the attendee
        $attendee = WebinarSysteemAttendees::get_by_webinar_id_and_key($data[0], $data[1]);

        if ($attendee == null) {
            return null;
        }

        // get the webinar
        $webinar = WebinarSysteemWebinar::create_from_id($data[0]);

        if ($webinar == null) {
            return null;
        }

        $start_at = WebinarSysteem::get_webinar_time($webinar->id, $attendee);

        $ret = (object) [
            'webinar_name' => $webinar->name,
            'link' => $webinar->get_url(),
            'link_with_auth' => $webinar->get_url_with_auth($attendee->email, $attendee->secretkey),
            'host' => $webinar->get_host(),
            'starts_at' => $start_at,
            'timezone_offset' => $webinar->get_timezone_offset() * 60,
            'duration' => $webinar->get_duration()
        ];

        // store in the cache
        self::$cache[$token] = $ret;

        return $ret;
    }

    public static function generate_token($webinar_id, $attendee_key) {
        return WebinarSysteemBase64::encode_array([$webinar_id, $attendee_key]);
    }
}
