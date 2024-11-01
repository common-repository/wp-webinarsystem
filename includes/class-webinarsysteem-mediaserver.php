<?php

class WebinarSysteemMediaServer
{
    static function get_media_server_url() {
        return apply_filters('wpws_get_media_server', WPWS_MEDIA_SERVER_API);
    }

    static function get_license_status($license = null) {
        $license = $license != null
            ? $license
            : WebinarSysteemLicense::get_license();

        if ($license == null || strlen($license) == 0) {
            return null;
        }

        $api_url = self::get_media_server_url();
        $request_url = $api_url.'/webinar/validate-license';

        $body = [
            'version' => WPWS_PLUGIN_VERSION,
            'licenseKey' => WebinarSysteemLicense::get_license(),
            'siteUrl' => home_url(),
        ];

        $res = wp_remote_post($request_url,
            [
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => wp_json_encode($body),
                'method' => 'POST',
                'blocking' => true,
                'data_format' => 'body',
            ]
        );

        if ($res == null || is_wp_error($res)) {
            return null;
        }

        // save the id.. ?
        $data = json_decode($res['body']);

        if ($data == null || !$data->ok) {
            return null;
        }

        return $data;
    }

    /**
     * Attempt to register this webinar with the media server
     * @param WebinarSysteemWebinar $webinar
     * @return object
     **/

    static function attempt_register_webinar($webinar) {
        // Enable media server if enabled for all webinars in settings or if this
        // webinar is using WebinarPress Live
        $webinar_should_use_media_server = (
            WebinarSysteemSettings::instance()->get_use_realtime_servers() ||
            $webinar->is_using_webinarpress_live()
        );

        if ($webinar == null ||
            !WebinarSysteemLicense::is_license_active() ||
            !$webinar_should_use_media_server ||
            !WebinarSysteemCron::was_active_within()) {
            return null;
        }

        // parse the web socket info from the url
        $api_url = self::get_media_server_url();
        $port = wp_parse_url($api_url, PHP_URL_PORT);
        $host = wp_parse_url($api_url, PHP_URL_HOST);
        $secure = wp_parse_url($api_url, PHP_URL_SCHEME) == 'https';

        // get any current settings
        $current_id = $webinar->get_media_server_id();

        if ($current_id != null) {
            WebinarSysteemLog::log("Using saved media settings for {$webinar->id}: {$current_id}");
            return (object) [
                'host' => $host,
                'port' => $port,
                'secure' => $secure,
                'id' => $current_id
            ];
        }

        WebinarSysteemLog::log("Attempting to register webinar $webinar->id with media server");

        $request_url = $api_url.'/webinar/setup';

        $body = [
            'version' => WPWS_PLUGIN_VERSION,
            'licenseKey' => WebinarSysteemLicense::get_license(),
            'siteUrl' => get_site_url(),
            'webinarId' => $webinar->id,
            'teamMemberKey' => $webinar->get_team_member_key(),
            'accessKey' => $webinar->get_access_key(),
            'pendingMessagesKey' => $webinar->get_pending_messages_key(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'params' => $webinar->get_params()
        ];

        $res = wp_remote_post($request_url,
            [
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => wp_json_encode($body),
                'method' => 'POST',
                'blocking' => true,
                'data_format' => 'body',
            ]
        );

        if ($res == null || is_wp_error($res)) {
            WebinarSysteemLog::log("Failed to register {$webinar->id} with media server: {$res->get_error_message()}");
            return null;
        }

        // save the id.. ?
        $data = json_decode($res['body']);

        if ($data == null) {
            WebinarSysteemLog::log("Unable to decode server response: {$res['body']}");
            return null;
        }

        if (!$data->ok) {
            WebinarSysteemLog::log("Failed to register {$webinar->id} with media server");
            return null;
        }

        WebinarSysteemLog::log("Registered {$webinar->id} with media server: {$data->id} @ {$api_url}");

        // record the settings
        $webinar->set_media_server_id($data->id);

        return (object) [
            'host' => $host,
            'port' => $port,
            'secure' => $secure,
            'id' => $data->id
        ];
    }

    /**
     * Attempt to register this webinar with the media server
     * @param WebinarSysteemWebinar $webinar
     * @return void
     **/

    static function update_webinar_status($webinar, $status = null) {
        // get any current settings
        $id = $webinar->get_media_server_id();

        if ($id == null) {
            return;
        }

        WebinarSysteemLog::log("Requesting websocket update for $webinar->id with media server");

        $request_url = self::get_media_server_url().'/webinar/updated';

        $body = [
            'version' => WPWS_PLUGIN_VERSION,
            'uniqueId' => $id,
            'teamMemberKey' => $webinar->get_team_member_key(),
            'status' => $status
        ];

        $res = wp_remote_post($request_url,
            [
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => wp_json_encode($body),
                'method' => 'POST',
                'blocking' => true,
                'data_format' => 'body',
            ]
        );

        if ($res == null || is_wp_error($res)) {
            WebinarSysteemLog::log("Failed to request websocket update {$webinar->id} with media server: {$res->get_error_message()}");
            return null;
        }

        // save the id.. ?
        $data = json_decode($res['body']);

        if (!$data->ok) {
            WebinarSysteemLog::log("Failed when requesting websocket status update for {$webinar->id}: {$data->code}");
            return null;
        }

        WebinarSysteemLog::log("Requested status update via websockets for {$webinar->id}");
    }

    /**
     * Fetch pending messages from the media server
     * @param WebinarSysteemWebinar $webinar
     * @return array
     **/

    static function fetch_pending_messages($webinar) {
        // parse the web socket info from the url
        $api_url = self::get_media_server_url();

        // get any current settings
        $id = $webinar->get_media_server_id();

        if ($id == null) {
            return null;
        }

        WebinarSysteemLog::log("Fetching pending messages for webinar $webinar->id");

        $request_url = $api_url.'/webinar/pending-messages';

        $body = [
            'uniqueId' => $id,
            'pendingMessagesKey' => $webinar->get_pending_messages_key()
        ];

        $res = wp_remote_post($request_url,
            [
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => wp_json_encode($body),
                'method' => 'POST',
                'blocking' => true,
                'data_format' => 'body',
            ]
        );

        if ($res == null || is_wp_error($res)) {
            WebinarSysteemLog::log("Failed to fetch pending messages for {$webinar->id}: {$res->get_error_message()}");
            return null;
        }

        // save the id.. ?
        $data = json_decode($res['body']);

        if (!$data->ok) {
            WebinarSysteemLog::log("Failed to fetch pending messages {$webinar->id}: {$data->code}");
            return null;
        }

        $message_count = count($data->messages);
        WebinarSysteemLog::log("Fetched {$message_count} pending message(s) for webinar {$webinar->id}");

        // record the settings
        return $data->messages;
    }

    /**
     * Fetch pending messages from the media server
     * @param WebinarSysteemWebinar $webinar
     * @param int $keep_after
     * @return void
     **/

    static function trim_pending_messages($webinar, $keep_after) {
        // parse the web socket info from the url
        $api_url = self::get_media_server_url();

        // get any current settings
        $id = $webinar->get_media_server_id();

        if ($id == null) {
            return;
        }

        WebinarSysteemLog::log("Trimming pending messages for $webinar->id");

        $request_url = $api_url.'/webinar/trim-pending-messages';

        $body = [
            'uniqueId' => $id,
            'pendingMessagesKey' => $webinar->get_pending_messages_key(),
            'keepAfter' => $keep_after
        ];

        $res = wp_remote_post($request_url,
            [
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => wp_json_encode($body),
                'method' => 'POST',
                'blocking' => true,
                'data_format' => 'body',
            ]
        );

        if ($res == null || is_wp_error($res)) {
            WebinarSysteemLog::log("Failed to trim pending messages for  {$webinar->id}: {$res->get_error_message()}");
            return;
        }

        // save the id.. ?
        $data = json_decode($res['body']);

        if (!$data->ok) {
            WebinarSysteemLog::log("Failed to trim pending messages {$webinar->id} with media server: {$data->code}");
            return;
        };
    }

    static function install_cron() {
        // add the cron if missing
        add_filter('cron_schedules', ['WebinarSysteemMediaServer', 'cron_cron_schedules']);

        // define our cron
        add_action('wpws_process_pending_messages', ['WebinarSysteemMediaServer', 'process_pending_messages']);

        // schedule the job
        if (!wp_next_scheduled('wpws_process_pending_messages')) {
            wp_schedule_event(time(), 'every1minute', 'wpws_process_pending_messages');
        }
    }

    /**
     * Fetch pending messages from the media server
     * @param WebinarSysteemWebinar $webinar
     * @return void
     **/

    public static function fetch_pending_messages_for_webinar($webinar) {

        // get any pending messages
        $messages = self::fetch_pending_messages($webinar);

        if ($messages == null || count($messages) == 0) {
            return;
        }

        // update the last active time for this webinar
        $webinar->update_last_active_time();

        // process the messages
        WebinarSysteemWebinarMessages::process_messages($webinar, $messages);

        // trim messages from the server
        $last = end($messages);
        self::trim_pending_messages($webinar, $last->createdAt);
    }

    public static function process_pending_messages() {
        // update the cron last active time
        WebinarSysteemCron::update_cron_last_active();

        if (!WebinarSysteemSettings::instance()->get_use_realtime_servers()) {
            return;
        }

        WebinarSysteemLog::log("Checking webinars for pending messages");

        $loop = new WP_Query(array(
            'post_type' => 'wswebinars',
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters
            'suppress_filters' => true,
            'posts_per_page' => -1,
        ));

        while ($loop->have_posts()) {
            $loop->the_post();

            // has the webinar been active in the last 30 mins?
            $webinar = WebinarSysteemWebinar::create_from_id(get_the_ID());

            if ($webinar == null || !$webinar->was_active_within(30)) {
                WebinarSysteemLog::log($webinar->name.' was not active in last 30 mins');
                continue;
            }

            WebinarSysteemLog::log("Checking webinars for pending messages for ".$webinar->id);

            self::fetch_pending_messages_for_webinar($webinar);
        }
    }

    // add custom interval
    public static function cron_cron_schedules($schedules)
    {
        // Adds once every minute
        $schedules['every1minute'] = array(
            'interval' => 60,
            'display' => __('Every minute'),
        );

        return $schedules;
    }

    public static function get_webinar_recordings() {

        $api_url = self::get_media_server_url();
        $request_url = $api_url.'/webinar/recordings';

        $body = [
            'version' => WPWS_PLUGIN_VERSION,
            'licenseKey' => WebinarSysteemLicense::get_license(),
        ];

        $res = wp_remote_post($request_url,
            [
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => wp_json_encode($body),
                'method' => 'POST',
                'blocking' => true,
                'data_format' => 'body',
            ]
        );

        if ($res == null || is_wp_error($res)) {
            WebinarSysteemLog::log("Failed to get webinar recordings: {$res->get_error_message()}");
            return null;
        }

        $res = json_decode($res['body']);

        return array_map(function ($recording) {
            return (object) [
                'id' => $recording->id,
                'last_modified' => $recording->lastModified,
                'delete_at' => $recording->deleteAt,
                'webinar_name' => $recording->webinarName,
                'download_url' => $recording->downloadUrl,
                'size' => $recording->size
            ];
        }, $res->recordings);
    }

    public static function delete_webinar_recording($recording_id) {

        $api_url = self::get_media_server_url();
        $request_url = $api_url.'/webinar/recordings/delete';

        $body = [
            'version' => WPWS_PLUGIN_VERSION,
            'licenseKey' => WebinarSysteemLicense::get_license(),
            'recordingId' => $recording_id
        ];

        $res = wp_remote_post($request_url,
            [
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => wp_json_encode($body),
                'method' => 'POST',
                'blocking' => true,
                'data_format' => 'body',
            ]
        );

        if ($res == null || is_wp_error($res)) {
            WebinarSysteemLog::log("Failed to delete webinar recording: {$res->get_error_message()}");
            return null;
        }
    }

    public static function get_zoom_signature($params) {
        $api_url = self::get_media_server_url();
        $request_url = $api_url.'/zoom/signature';

        $body = [
            'version' => WPWS_PLUGIN_VERSION,
            'params' => $params
        ];

        $res = wp_remote_post($request_url,
            [
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => wp_json_encode($body),
                'method' => 'POST',
                'blocking' => true,
                'data_format' => 'body',
            ]
        );

        if ($res == null || is_wp_error($res)) {
            WebinarSysteemLog::log("Failed to get zoom signature: {$res->get_error_message()}");
            return null;
        }

        return json_decode($res['body']);
    }
}