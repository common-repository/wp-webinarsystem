<?php

class WebinarSysteemEmails {
    protected static $ONE_HOUR_BEFORE = 1;
    protected static $ONE_DAY_BEFORE = 2;
    protected static $STARTING = 3;
    protected static $REPLAY = 4;

    public function __construct() {
        add_action('init', [$this, 'register_mail_sender']);
        add_action('wswebinar_send_scheduled_mails', [$this, 'send_scheduled_emails']);
        add_action('wswebinar_send_queued_mails', [$this, 'send_queued_emails']);
        add_filter('cron_schedules', [$this, 'cron_add_5_minutes']);
    }

    public function send_question_to_host($webinar_id, $attendee_name, $attendee_email, $question) {
        $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);
        $email_questions = $webinar->get_email_questions_to_host();

        if (!$email_questions) {
            return false;
        }

        $to = $webinar->get_email_questions_address();

        if (empty($to)) {
            $to = strlen($webinar->get_admin_email_address()) > 0
                ? $webinar->get_admin_email_address()
                : WebinarSysteemSettings::instance()->get_admin_email_address();
        }

        // TODO, replace this with something customisable by the user?
        $content = "Hey,

{$attendee_name} asked the following question during {$webinar->get_name()}:

<i>$question</i>";

        $content = apply_filters('meta_content', $content);

        return $this->send_email(
            $webinar_id,
            $to,
            'New question from '.$attendee_email,
            $content,
            true);
    }

    public function send_attendee_email(
        $email_settings,
        $attendee,
        $to,
        $send_later = false,
        $send_if_disabled = false
    ) {
        if (!$email_settings->enabled && !$send_if_disabled) {
            WebinarSysteemLog::log("Email type is disabled, not sending to {$to}");
            return false;
        }

        WebinarSysteemLog::log("Sending email to {$to}");

        $body = self::replace_tags(
            $email_settings->content,
            $attendee->webinar_id,
            $attendee
        );

        $subject = self::replace_tags(
            $email_settings->subject,
            $attendee->webinar_id,
            $attendee,
            false);

        return $this->send_email($attendee->webinar_id, $to, $subject, $body, false, $send_later);
    }

    public function send_preview($to, $subject, $content, $attendee_name = 'Joe Bloggs') {
        $posts = query_posts(['post_type' => 'wswebinars']);
        $post_id = $posts[0]->ID;

        $attendee = (object) [
            'name' => $attendee_name,
            'email' => $to,
            // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            'exact_time' => date('Y-m-d H:i:s'),
            'secretkey' => null,
            'custom_fields' => []
        ];

        $body = self::replace_tags(
            $content,
            $post_id,
            $attendee);

        $subject = self::replace_tags(
            $subject,
            $post_id,
            $attendee,
            false);

        return $this->send_email($post_id, $to, $subject, $body);
    }

    function get_email_settings($webinar_id, $type) {
        $settings = WebinarSysteemSettings::instance();
        return $settings->get_email_template_options($type);
    }

    public function send_new_registration_email_to_admin($attendee) {
        $admin_email = WebinarSysteemSettings::instance()->get_admin_email_address();

        // overridden for this webinar?
        $webinar = WebinarSysteemWebinar::create_from_id($attendee->webinar_id);

        if ($webinar != null && strlen($webinar->get_admin_email_address()) > 0) {
            $admin_email = $webinar->get_admin_email_address();
        }

        return $this->send_attendee_email(
            $this->get_email_settings($attendee->webinar_id, 'newreg'),
            $attendee,
            $admin_email,
            true);
    }

    public function send_new_registration_email($attendee, $send_if_disabled = false) {
        return $this->send_attendee_email(
            $this->get_email_settings($attendee->webinar_id, 'regconfirm'),
            $attendee,
            $attendee->email,
            true,
            $send_if_disabled);
    }

    public function send_mail_to_attendee_24hr_template($attendee) {
        return $this->send_attendee_email(
            $this->get_email_settings($attendee->webinar_id, '24hrb4'),
            $attendee,
            $attendee->email);
    }

    public function send_mail_to_attendee_1hr_template($attendee) {
        return $this->send_attendee_email(
            $this->get_email_settings($attendee->webinar_id, '1hrb4'),
            $attendee,
            $attendee->email);
    }

    public function send_mail_to_attendee_started_template($attendee) {
        return $this->send_attendee_email(
            $this->get_email_settings($attendee->webinar_id, 'wbnstarted'),
            $attendee,
            $attendee->email);
    }

    public function send_mail_to_reader_on_wc_order_complete($attendee, $send_if_disabled = false) {
        $this->send_attendee_email(
            $this->get_email_settings($attendee->webinar_id, 'order_complete'),
            $attendee,
            $attendee->email,
            true,
            $send_if_disabled);
    }

    public function send_webinar_reply_email($attendee) {
        return $this->send_attendee_email(
            $this->get_email_settings($attendee->webinar_id, 'wbnreplay'),
            $attendee,
            $attendee->email,
            false);
    }

    public function send_pending_email_confirmation($attendee) {
        return $this->send_attendee_email(
            $this->get_email_settings($attendee->webinar_id, 'double_optin'),
            $attendee,
            $attendee->email,
            true);
    }

    public static function replace_tags($text, $webinar_id, $attendee, $is_html = true) {
        try {
            $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);

            if ($webinar == null) {
                return $text;
            }

            $wb_timezone = get_post_meta($webinar_id, '_wswebinar_timezoneidentifier', true);
            $time_format = WebinarSysteem::get_wp_datetime_formats(WebinarSysteem::$WP_TIME_FORMAT);
            $date_format = WebinarSysteem::get_wp_datetime_formats(WebinarSysteem::$WP_DATE_FORMAT);

            if (empty($wb_timezone)) {
                $timezone_string = get_option('timezone_string');
                $wpoffset = get_option('gmt_offset');
                $gmt_offset = WebinarSysteemDateTime::format_timezone(($wpoffset > 0) ? '+' . $wpoffset : $wpoffset);
                $wb_timezone = (!empty($timezone_string))
                    ? $timezone_string
                    : 'UTC' . $gmt_offset . '';
            }

            $time = $attendee->exact_time;
            $wb_date = date_i18n($date_format, strtotime($time));
            $wb_time = date_i18n($time_format, strtotime($time));

            // generate the webinar url
            if ($attendee->secretkey != null) {
                $webinar_link = $webinar->get_url_with_auth($attendee->email, $attendee->secretkey);
            } else {
                $webinar_link = get_permalink($webinar_id, false);
            }

            $confirm_link = $webinar->get_confirmation_link($attendee);

            $replacements = [
                'attendee-name' => $attendee->name,
                'attendee-email' => $attendee->email,
                'receiver-name' => $attendee->name,
                'receiver-email' => $attendee->email,
                'webinar-title' => get_the_title($webinar_id),
                'webinar-link' => $webinar_link,
                'webinar-link-button' => function ($args) use ($webinar_link) {
                    $text = __('Join the webinar', '_wswebinar');
                    if (isset($args->text)) {
                        $text = $args->text;
                    }
                    return self::render_link_button_html(
                        $text,
                        $webinar_link
                    );
                },
                'confirm-registration-button' => function ($args) use ($confirm_link) {
                    $text = __('Confirm my registration', '_wswebinar');
                    if (isset($args->text)) {
                        $text = $args->text;
                    }
                    return self::render_link_button_html(
                        $text,
                        $confirm_link
                    );
                },
                'webinar-date' => $wb_date,
                'webinar-time' => $wb_time,
                'webinar-timezone' => $wb_timezone
            ];

            // Add the custom fields replacements
            $custom_values = is_array($attendee->custom_fields)
                ? $attendee->custom_fields
                : json_decode($attendee->custom_fields);

            if ($custom_values != null) {
                foreach ($custom_values as $field) {
                    $replacements['custom_'.$field->id] = $field->value;
                }
            }

            return WebinarSysteemHelperFunctions::replace_tags($text, $replacements, $is_html);

        } catch (Exception $e) {
            return $text;
        }
    }

    private function get_email_headers($name, $email) {
        $headers = array();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=utf-8";
        $headers[] = "From: ".$name." <".$email.'>';

        return implode("\r\n", $headers);
    }

    protected static function render_link_button_html($text, $url) {
        // get settings
        $settings = WebinarSysteemSettings::instance();

        $params = (object) [
            'text' => $text,
            'url' => $url,
            'button_background_color' => $settings->get_email_button_background_color(),
            'button_text_color' => $settings->get_email_button_text_color(),
        ];

        // render the file
        $file = WebinarSysteemTemplates::get_path('template-email-cta.php');
        ob_start();
        include $file;

        // return the contents
        return ob_get_clean();
    }

    private function render_webinar_email(
        $title,
        $content,
        $include_unsubscribe_link,
        $manage_url,
        $unsubscribe_url
    ) {
        // get settings
        $settings = WebinarSysteemSettings::instance();

        $params = (object) [
            'title' => $title,
            'preview' => $title,
            'content' => $content,
            'include_unsubscribe_link' => $include_unsubscribe_link,
            'manage_url' => $manage_url,
            'unsubscribe_url' => $unsubscribe_url,
            'footer_text' => $settings->get_email_footer_text(),
            'header_image_url' => $settings->get_email_header_image(),
            'header_image_height' => $settings->get_email_image_height(),
            'base_color' => $settings->get_email_base_color(),
            'background_color' => $settings->get_email_background_color(),
            'body_background_color' => $settings->get_email_body_background_color(),
            'text_color' => $settings->get_email_body_text_color(),
        ];

        // render the file
        $file = WebinarSysteemTemplates::get_path('template-email-layout.php');
        ob_start();
        include $file;

        // return the contents
        return ob_get_clean();
    }

    private function send_email(
        $webinar_id,
        $to,
        $subject,
        $body,
        $disable_unsubscribe_links = false,
        $send_later = false
    ) {
        if (WebinarSysteemUnsubscribe::is_unsubscribed($to)) {
            WebinarSysteemLog::log('Not sending email to '.$to.' because they are unsubscribed');
            return true;
        }

        $settings = WebinarSysteemSettings::instance();
        $unsubscribe_url = WebinarSysteemUserPages::get_unsubscribe_url($to);
        $manage_url = WebinarSysteemUserPages::get_email_management_url($to);

        $body = $this->render_webinar_email(
            $subject,
            $body,
            $settings->get_include_unsubscribe_links() && !$disable_unsubscribe_links,
            $manage_url,
            $unsubscribe_url);

        // check webinar overrides
        $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);

        // get the send headers
        $settings = WebinarSysteemSettings::instance();
        $name = $settings->get_email_from_name();
        $email = $settings->get_email_from_address();

        if ($webinar != null) {
            if (strlen($webinar->get_email_from_name()) > 0) {
                $name = $webinar->get_email_from_name();
            }

            if (strlen($webinar->get_email_from_address()) > 0) {
                $email = $webinar->get_email_from_address();
            }
        }

        $headers = $this->get_email_headers($name, $email);

        if ($send_later) {
            $this->enqueue_mail_for_sending_later((object) [
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
                'headers' => $headers
            ]);

            return true;
        }

        WebinarSysteemLog::log("Sending webinar email ($to, $subject)");
        $email_was_sent = wp_mail(
            $to,
            wp_specialchars_decode($subject),
            $body,
            $headers);

        if ($email_was_sent == true) {
            WebinarSysteemLog::log("Email successfully sent (according to wp_mail)");
        } else {
            WebinarSysteemLog::log("Failed to send (according to wp_mail)");
        }

        return $email_was_sent;
    }

    /*
     * Queue an email for sending later so it doesn't block the response to the visitor
     * Some email servers are really slow which can cause delays when registering
     */

    public function enqueue_mail_for_sending_later($args) {
        global $wpdb;
        WebinarSysteemLog::log("Queuing mail to send later ($args->to, $args->subject)");
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $res = $wpdb->insert(WebinarSysteemTables::get_email_queue(),
            ['args' => serialize($args)],
            ['%s']);
        WebinarSysteemLog::log("Mail queue result: $res");
    }

    public function send_queued_emails() {
        global $wpdb;

        if (WebinarSysteemRunningProcesses::is_running('queued_emails')) {
            WebinarSysteemLog::log("Another process is already sending queued emails, exiting");
            return;
        }

        // update the process running state
        WebinarSysteemRunningProcesses::refresh('queued_emails', 59);

        // update the cron last active time
        WebinarSysteemCron::update_cron_last_active();
        WebinarSysteemLog::log('Sending queued emails');

        while (true) {
            $table = WebinarSysteemTables::get_email_queue();
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $row = $wpdb->get_row("SELECT * FROM $table LIMIT 1");

            // no emails to process
            if ($row == null) {
                WebinarSysteemLog::log('No more queued emails, exiting');
                break;
            }

            // delete the row from the db
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete(
                WebinarSysteemTables::get_email_queue(), ['id' => (int)$row->id]
            );

            // send the mail
            $args = unserialize($row->args);

            $headers = property_exists($args, 'headers')
                ? $args->headers
                : $this->get_email_headers(
                    WebinarSysteemSettings::instance()->get_email_from_name(),
                    WebinarSysteemSettings::instance()->get_email_from_address());

            WebinarSysteemLog::log("Sending webinar email from queue ($args->to, $args->subject)");

            $email_was_sent = wp_mail(
                $args->to,
                wp_specialchars_decode($args->subject),
                $args->body,
                $headers);

            if ($email_was_sent == true) {
                WebinarSysteemLog::log("Email successfully sent (according to wp_mail)");
            } else {
                WebinarSysteemLog::log("Failed to send (according to wp_mail)");
            }

            // update the process running state
            WebinarSysteemRunningProcesses::refresh('queued_emails');
        }
    }

    /**
     *
     * @param WebinarSysteemWebinar $webinar
     * @param string $type
     * @return object
     *
     */

    protected function get_second_range_for_type($webinar, $type) {
        switch ($type) {
            case self::$ONE_HOUR_BEFORE:
                return (object) [
                    'description' => 'one hour before',
                    'from' => 55,
                    'to' => 65,
                    'multiplier' => MINUTE_IN_SECONDS,
                    'sent_field' => 'onehourmailsent',
                    'offset_duration' => false
                ];

            case self::$ONE_DAY_BEFORE:
                return (object) [
                    'description' => 'one day before',
                    'from' => 23,
                    'to' => 24,
                    'multiplier' => HOUR_IN_SECONDS,
                    'sent_field' => 'onedaymailsent',
                    'offset_duration' => false
                ];

            case self::$STARTING:
                return (object) [
                    'description' => '5 minutes before',
                    'from' => -5,
                    'to' => 5,
                    'multiplier' => MINUTE_IN_SECONDS,
                    'sent_field' => 'wbstartingmailsent',
                    'offset_duration' => false
                ];

            case self::$REPLAY:
                return (object) [
                    'description' => '10 minutes after (replay)',
                    // using negative values because we are checking after the webinar ends.. not before it starts :/
                    'from' => -10,
                    'to' => 0,
                    'multiplier' => MINUTE_IN_SECONDS,
                    'sent_field' => 'replaymailsent',
                    'offset_duration' => true
                ];

            default:
                return null;
        }
    }

    protected function send_email_for_type($type, $attendee) {
        if ($attendee->anonymous_email) {
            return;
        }
        switch ($type) {
            case self::$STARTING:
                return $this->send_mail_to_attendee_started_template($attendee);

            case self::$ONE_HOUR_BEFORE:
                return $this->send_mail_to_attendee_1hr_template($attendee);

            case self::$ONE_DAY_BEFORE:
                return $this->send_mail_to_attendee_24hr_template($attendee);

            case self::$REPLAY:
                return $this->send_webinar_reply_email($attendee);

            default:
                return false;
        }
    }

    /**
     *
     * @param WebinarSysteemWebinar $webinar
     * @param string $type
     * @return void
     *
     */

    protected function send_scheduled_emails_between($webinar, $type) {
        global $wpdb;

        $config = $this->get_second_range_for_type($webinar, $type);

        $webinar_title = get_the_title($webinar->id);
        WebinarSysteemLog::log("Checking emails for {$webinar_title} ({$webinar->id}) {$config->description}");

        $subscribers_table = WebinarSysteemTables::get_subscribers();
        $notifications_table = WebinarSysteemTables::get_notifications();

        // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
        $webinar_now = date('Y-m-d H:i:s',
            WebinarSysteemWebinar::get_now_in_webinar_timezone($webinar->id));

        // add an offset of the webinar time
        $offset = $config->offset_duration
            ? $webinar->get_duration()
            : 0;

        // Calculate the time from webinar end
        $sql = "
            SELECT s.id, s.name, s.email, s.webinar_id, s.secretkey, s.exact_time, s.custom_fields
            FROM {$subscribers_table} s
            LEFT JOIN {$notifications_table} n
              ON s.id = n.attendee_id
                AND s.webinar_id = n.webinar_id
                AND n.notification_type = %d
            WHERE s.webinar_id=%d
                AND DATE_ADD(exact_time, INTERVAL %d SECOND) > DATE_ADD(%s, INTERVAL %d SECOND)
                AND DATE_ADD(exact_time, INTERVAL %d SECOND) < DATE_ADD(%s, INTERVAL %d SECOND)
                AND s.{$config->sent_field} != 1
                AND n.id IS null
                AND s.anonymous_email != 1
        ";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $query = $wpdb->prepare( $sql,
            $type,
            $webinar->id,
            $offset,
            $webinar_now,
            $config->from * $config->multiplier,
            $offset,
            $webinar_now,
            $config->to * $config->multiplier);

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $attendees = $wpdb->get_results($query);

        if (!empty($attendees)) {
            WebinarSysteemLog::log('Found '.count($attendees).' attendees');
        }

        foreach ($attendees as $attendee) {
            WebinarSysteemLog::log("Sending email to {$attendee->email} for webinar {$attendee->webinar_id}");

            $this->send_email_for_type($type, $attendee);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->insert(
                $notifications_table,
                [
                    'attendee_id' => $attendee->id,
                    'notification_type' => $type,
                    'webinar_id' => $attendee->webinar_id
                ],
                ['%d', '%d', '%d']
            );

            // update the process running state
            WebinarSysteemRunningProcesses::refresh('scheduled_emails');
        }
    }

    public function send_replay_emails($webinar_id) {
        global $wpdb;

        $webinar_title = get_the_title($webinar_id);
        WebinarSysteemLog::log("Checking replay emails for {$webinar_title} ({$webinar_id})");

        $subscribers_table = WebinarSysteemTables::get_subscribers();
        $notifications_table = WebinarSysteemTables::get_notifications();

        $sql = "
            SELECT s.id, s.name, s.email, s.webinar_id, s.secretkey, s.exact_time, s.custom_fields
            FROM {$subscribers_table} s
            LEFT JOIN {$notifications_table} n
              ON s.id = n.attendee_id
                AND s.webinar_id = n.webinar_id
                AND n.notification_type = %d
            WHERE s.webinar_id=%d
                AND s.replaymailsent != 1
                AND n.id IS null
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $attendees = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->prepare($sql, self::$REPLAY, $webinar_id));

        if (!empty($attendees)) {
            WebinarSysteemLog::log('Found '.count($attendees).' attendees');
        }

        foreach ($attendees as $attendee) {
            WebinarSysteemLog::log("Sending replay email to {$attendee->email} for webinar {$attendee->webinar_id}");

            $this->send_email_for_type(self::$REPLAY, $attendee);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->insert(
                $notifications_table,
                [
                    'attendee_id' => $attendee->id,
                    'notification_type' => self::$REPLAY,
                    'webinar_id' => $attendee->webinar_id
                ],
                ['%d', '%d', '%d']
            );

            // update the process running state
            WebinarSysteemRunningProcesses::refresh('scheduled_emails');
        }
    }

    public function send_scheduled_emails() {
        if (WebinarSysteemRunningProcesses::is_running('scheduled_emails')) {
            WebinarSysteemLog::log("Another process is already sending scheduled emails, exiting");
            return;
        }

        // update the process running state
        WebinarSysteemRunningProcesses::refresh('scheduled_emails');

        /*
         * TODO, add exact_time_utc to attendees so we can just do one query for each
         *
         * The problem with that is if there is an hour change after the user registers the UTC
         * time will then be wrong.
         *
         * Is the only way to accurately check the time is re-calculate it every time? It could be done
         * by calculating the UTC time in the webinar for a future date?
         */

        $loop = new WP_Query([
            'post_type' => 'wswebinars',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_key' => '_wswebinar_gener_webinar_status',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'meta_value' => 'clo',
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters
            'meta_compare' => '!=',
            'posts_per_page' => -1,
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters
            'suppress_filters' => true,
        ]);

        while ($loop->have_posts()) {
            $loop->the_post();

            $webinar = WebinarSysteemWebinar::create_from_id(get_the_ID());

            $this->send_scheduled_emails_between($webinar, self::$STARTING);
            $this->send_scheduled_emails_between($webinar, self::$ONE_HOUR_BEFORE);
            $this->send_scheduled_emails_between($webinar, self::$ONE_DAY_BEFORE);

            if ($webinar->is_automated() && $webinar->get_automated_replay_enabled()) {
                $this->send_scheduled_emails_between($webinar, self::$REPLAY);
            }
        }

        // check manual reply posts
        $loop = new WP_Query([
            'post_type' => 'wswebinars',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_key' => '_wswebinar_gener_webinar_status',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'meta_value' => 'rep',
            'meta_compare' => '=',
            'posts_per_page' => -1,
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters
            'suppress_filters' => true,
        ]);

        while ($loop->have_posts()) {
            $loop->the_post();
            $this->send_replay_emails(get_the_ID());
        }
    }

    // add custom interval
    public function cron_add_5_minutes($schedules) {
        $schedules['every5minutes'] = [
            'interval' => 60 * 5,
            'display' => __('Every 5 minutes'),
        ];

        $schedules['every1minute'] = [
            'interval' => 60,
            'display' => __('Every 1 minute'),
        ];

        return $schedules;
    }

    public function register_mail_sender() {
        if (!wp_next_scheduled('wswebinar_send_scheduled_mails')) {
            wp_schedule_event(time(), 'every5minutes',
                'wswebinar_send_scheduled_mails');
        }

        if (!wp_next_scheduled('wswebinar_send_queued_mails')) {
            wp_schedule_event(time(), 'every1minute',
                'wswebinar_send_queued_mails');
        }
    }

    function update_last_schedule_run() {
        update_option('wpws_emails_last_schedule_run', time());
    }

    function was_last_schedule_run_within($minutes_ago = 5) {
        $last_active_at = get_option('wpws_emails_last_schedule_run');

        if ($last_active_at == null) {
            return false;
        }

        return time() - (int) $last_active_at < (60 * $minutes_ago);
    }
}
