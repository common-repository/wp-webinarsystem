<?php

class WebinarSysteemWebinarSession {
    private static function set_cookie($key, $value) {
        unset($_COOKIE[$key]);
        setcookie($key, '', time() - 3600, '/');
        setcookie($key, $value, time() + 60 * 60 * 24 * 30, '/');
        $_COOKIE[$key] = $value;
    }

    public static function clear_cookie($key) {
        unset($_COOKIE[$key]);
        setcookie($key, null, -1, '/');
    }

    private static function get_cookie_key($webinar_id) {
       return 'wpws_session_'.$webinar_id; 
    }

    public static function save_login($webinar_id, $rand, $email, $random_key) {
        // remove old cookies
        self::clear_cookie('_wswebinar_registered_email');
        self::clear_cookie('_wswebinar_registered_key');
        self::clear_cookie('_wswebinar_regrandom_key');
        self::clear_cookie('_wswebinar_registered');

        $data = WebinarSysteemBase64::encode_array([
            'email' => $email,
            'key' => $random_key,
            'secret' => $rand
        ]);

        self::set_cookie(self::get_cookie_key($webinar_id), $data);
    }

    public static function get_session($webinar_id) {
        $obj = new stdClass();

        // try the new format
        $key = self::get_cookie_key($webinar_id);

        if (isset($_COOKIE[$key])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            return WebinarSysteemBase64::decode_array($_COOKIE[$key]);
        }

        // fallback onto the old method
        if (isset($_COOKIE['_wswebinar_registered_email']))
        {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $obj->email = $_COOKIE['_wswebinar_registered_email'];
        }            

        if (isset($_COOKIE['_wswebinar_regrandom_key']))
        {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $obj->key = $_COOKIE['_wswebinar_regrandom_key'];
        }            

        return $obj;
    }

    public static function get_registration_key($webinar_id) {
        $session = self::get_session($webinar_id);
        return $session
            ? $session->key
            : null;
    }
}
