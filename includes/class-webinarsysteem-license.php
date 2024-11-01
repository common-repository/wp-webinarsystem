<?php

class WebinarSysteemLicense {
    static $license_option_key = '_wswebinar_licensekey';
    static $status_option_key = '_wswebinar_license_status';

    public static function get_renewal_url() {
        return 'https://webinarpress.com/checkout/?nocache=true&download_id=1559&edd_license_key='.self::get_license();
    }

    public static function activate_license($key, $url) {
        WebinarSysteemLog::log("Attempting to activate license $key for $url");

        $api_params = [
            'edd_action' => 'activate_license',
            'license' => $key,
            'item_name' => urlencode(WSWEB_ITEM_NAME),
            'url' => $url
                ? $url
                : home_url()
        ];

        // Call the custom API.
        $response = wp_remote_post(
            WSWEB_STORE_URL,
            ['timeout' => 15, 'sslverify' => false, 'body' => $api_params]
        );

        WebinarSysteemLog::log("Activate Response ".WebinarSysteemLog::dump_object($response));

        // make sure the response came back okay
        if (is_wp_error($response)) {
            return (object) [
                'ok' => false,
                'msg' => $response->get_error_message(),
                'code' => 'unknown'
            ];
        }

        // decode the license data
        $json = wp_remote_retrieve_body($response);
        $data = json_decode($json);

        if (!$data->success) {
            // this is to unify codes with media server. it was decided
            // in media server to change disabled->license_disabled

            $code = $data->error == 'disabled'
                ? 'license_disabled'
                : $data->error;

            return (object) [
                'ok' => false,
                'code' => $code
            ];
        }

        update_option(self::$license_option_key, $key);
        return (object) [
            'ok' => true,
        ];
    }

    public static function deactivate_license($url = null) {
        $license = self::get_license();

        if ($license == null) {
            return false;
        }

        delete_option(self::$license_option_key);
        delete_option(self::$status_option_key);

        // data to send in our API request
        $api_params = [
            'edd_action' => 'deactivate_license',
            'license' => $license,
            'item_name' => urlencode(WSWEB_ITEM_NAME),
            'url' => $url
                ? $url
                : home_url()
        ];

        // Call the custom API.
        $response = wp_remote_post(
            WSWEB_STORE_URL, [
                'timeout' => 15,
                'sslverify' => false,
                'body' => $api_params
            ]
        );

        // make sure the response came back okay
        if (is_wp_error($response)) {
            return false;
        }

        return true;
    }

    public static function get_license() {
        return get_option(self::$license_option_key);
    }

    public static function is_license_installed() {
        return strlen(self::get_license()) > 0;
    }

    public static function get_license_for_update() {
        if (!is_multisite()) {
            return self::get_license();
        }

        $sites = get_sites();

        foreach ($sites as $site) {
            $status = self::get_license_status_for_blog($site->blog_id);

            if ($status == 'valid') {
                return get_blog_option($site->blog_id, self::$license_option_key);
            }
        }

        return false;
    }

    public static function is_license_active() {
        return self::get_license_status() == 'valid';
    }

    static function get_license_status_for_blog($blog_id) {
        if (!is_multisite()) {
            return null;
        }

        $status = get_blog_option($blog_id, self::$status_option_key);

        if ($status != false && time() - (int) $status->at < (60 * 60 * 24)) {
            return $status->code;
        }

        // get the license
        $license = get_blog_option($blog_id, self::$license_option_key);

        if ($license == false) {
            return null;
        }

        // get license from the server
        $data = WebinarSysteemMediaServer::get_license_status($license);

        $code = $data
            ? $data->status
            : null;

        update_blog_option($blog_id, self::$status_option_key, (object) [
            'at' => time(),
            'code' => $code,
            'max_hosted_attendee_count' => $data
                ? $data->maxHostedAttendeeCount
                : 0
        ]);

        return $code;
    }

    static function get_license_details() {
        $status = get_option(self::$status_option_key);

        if ($status != false && time() - (int) $status->at < (60 * 60 * 24) && $status->code != null) {
            return $status;
        }

        // get the license
        $license = get_option(self::$license_option_key);

        if ($license == false) {
            return null;
        }

        // get license from the server
        $data = WebinarSysteemMediaServer::get_license_status($license);

        $code = $data
            ? $data->status
            : null;

        $details = (object) [
            'at' => time(),
            'code' => $code,
            'max_hosted_attendee_count' => $data
                ? $data->maxHostedAttendeeCount
                : 0
        ];

        update_option(self::$status_option_key, $details);

        return $details;
    }

    static function get_license_status() {
        $details = self::get_license_details();
        return $details
            ? $details->code
            : null;
    }

    static function get_max_hosted_attendee_count() {
        // WebinarPress Live needs WebinarPress Connect
        if (!WebinarSysteemSettings::instance()->get_use_realtime_servers()) {
            return 0;
        }

        $details = self::get_license_details();
        return $details && property_exists($details, 'max_hosted_attendee_count')
            ? $details->max_hosted_attendee_count
            : 0;
    }
}