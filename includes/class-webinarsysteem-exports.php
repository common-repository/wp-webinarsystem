<?php

class WebinarSysteemExports
{
    private static function get_title_for_filenames($title) {
        return preg_replace(
            "/[\s_]/", "_",
            preg_replace(
                "/[\s-]+/",
                " ",
                preg_replace(
                    "/[^a-z0-9_\s-]/",
                    "",
                    strtolower($title)
                )
            )
        );
    }

    private static function array_to_csv($input_array, $output_file_name, $delimiter) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $temp_memory = fopen('php://memory', 'w');

        foreach ($input_array as $line) {
            fputcsv($temp_memory, $line, $delimiter);
        }

        fseek($temp_memory, 0);
        header('Content-Type: application/csv');
        header('Content-Disposition: attachement; filename="' . $output_file_name . '";');

        fpassthru($temp_memory);
    }

    public static function attendees_csv($webinar_id) {
        if (!WebinarSysteemPermissions::can_manage_attendees()) {
            die();
        }

        $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);

        if ($webinar == null) {
            die();
        }

        $attendees = WebinarSysteemAttendees::get_webinar_attendees($webinar_id);

        $getTitle = get_the_title($webinar_id);
        $post_title = !empty($getTitle) ? $getTitle : 'Unknown';

        $title = 'attendees_' . self::get_title_for_filenames($post_title) . '_' . time() . '.csv';

        $csv = array();

        $headers = array('Name', 'Email', 'Registered on', 'Registered for', 'Attended');
        $custom_headers = $webinar->get_custom_fields();

        foreach ($custom_headers as $field) {
            array_push($headers, $field->labelValue);
        }

        $csv[] = $headers;

        $datetime_format = WebinarSysteem::get_wp_datetime_formats(WebinarSysteem::$WP_DATE_TIME_FORMAT);

        foreach ($attendees as $attendee) {
            $custom_field_values = array();
            $custom_values = json_decode($attendee->custom_fields);

            if ($custom_values != null) {
                foreach ($custom_headers as $field) {
                    foreach ($custom_values as $value) {
                        if ($value->id == $field->id) {
                            array_push($custom_field_values, $value->value);
                        }
                    }
                }
            }

            $data = array(
                !empty($attendee->name) ? $attendee->name : '',
                !empty($attendee->email) ? $attendee->email : '',
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                !empty($attendee->time) ? date($datetime_format, strtotime($attendee->time)) : '',
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                !empty($attendee->time) ? date($datetime_format, ($webinar->is_recurring() ? strtotime($attendee->exact_time) : WebinarSysteem::get_webinar_time($webinar_id))) : '',
                $attendee->attended == 1 ? 'Yes' : 'No'
            );

            $csv[] = array_merge($data, $custom_field_values);
        }

        self::array_to_csv($csv, $title, ',');
        exit();
    }

    static function attendees_bcc($webinar_id) {
        if (!WebinarSysteemPermissions::can_manage_attendees()) {
            die();
        }

        $attendees = WebinarSysteemAttendees::get_webinar_attendees($webinar_id);
        $filename = 'bcc_' . self::get_title_for_filenames(get_the_title($webinar_id) ?: 'Unknown') . '_' . time() . '.txt';

        $bcc = array_map(function ($attendee) {
            return $attendee->name." <".$attendee->email.">";
        }, $attendees);

        header('Content-type: text/plain; charset=utf-8');
        header('Content-Disposition: attachement; filename="' . $filename . '";');

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo implode(",\r\n", $bcc);
        exit();
    }

    public static function chats_csv($webinar_id) {
        if (!WebinarSysteemPermissions::can_manage_chats()) {
            die();
        }

        $title = get_the_title($webinar_id);
        $post_title = !empty($title) ? $title : 'Unknown';

        $filename = 'chat_' . self::get_title_for_filenames($post_title).'_'.time().'.csv';
        $datetime_format = WebinarSysteem::get_wp_datetime_formats(WebinarSysteem::$WP_DATE_TIME_FORMAT);

        $messages = array_map(function ($message) use ($datetime_format) {
            $name = !empty($message->attendee) && $message->attendee != null
                ? $message->attendee->name
                : '';

            $email = !empty($message->attendee) && $message->attendee != null
                ? $message->attendee->email
                : '';

            return [
                $message->id,
                $name,
                $email,
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                date($datetime_format, strtotime($message->created_at)),
                $message->content
            ];
        }, WebinarSysteemQuestions::get_messages($webinar_id));

        // create the csv
        $csv = array_merge(
            [['id', 'from', 'email', 'at', 'message']],
            $messages
        );

        self::array_to_csv($csv, $filename, ',');
        exit();
    }

    public static function questions_csv($webinar_id) {
        if (!WebinarSysteemPermissions::can_manage_questions()) {
            die();
        }

        $title = get_the_title($webinar_id);
        $post_title = !empty($title) ? $title : 'Unknown';

        $filename = 'questions_' . self::get_title_for_filenames($post_title).'_'.time().'.csv';
        $datetime_format = WebinarSysteem::get_wp_datetime_formats(WebinarSysteem::$WP_DATE_TIME_FORMAT);

        $questions = array_map(function ($question) use ($datetime_format) {
            $name = !empty($question->asked_by) && $question->asked_by != null
                ? $question->asked_by->name
                : '';

            $email = !empty($question->asked_by) && $question->asked_by != null
                ? $question->asked_by->email
                : '';

            return [
                $question->id,
                $name,
                $email,
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                date($datetime_format, strtotime($question->created_at)),
                $question->question,
                $question->answer,
            ];
        }, WebinarSysteemQuestions::get_questions($webinar_id));

        // create the csv
        $csv = array_merge(
            [['id', 'from', 'email', 'at', 'question', 'answer']],
            $questions
        );

        self::array_to_csv($csv, $filename, ',');
        exit();
    }

    static function system_info() {
        if (!WebinarSysteemPermissions::can_manage_settings()) {
            die();
        }

        // build out filename and timestamp
        $name = sanitize_title_with_dashes(get_bloginfo('name'), '', 'save');
        $file = $name.'-system-info.txt';

        $now = time();
        // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
        $stamp ='Report Generated: '.date('m-d-Y @ g:i:sa', $now).' system time';

        $data = '';
        $data .= $stamp."\n\n";
        $data .= WPWS_System_Snapshot_Report::getInstance()->snapshot_data();
        $data .= "\n\n".$stamp;

        nocache_headers();

        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="'.$file.'"');

        echo esc_html($data);
        die();
    }

    public static function system_log() {
        $url = WebinarSysteemLog::get_url();

        if (!WebinarSysteemLog::exists()) {
            die('Log not found');
        }

        wp_redirect($url);
        exit;
    }

    public static function handle_exports() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $request = (object)$_GET;

        if (!isset($request->wpws_export)) {
            return;
        }

        // Verify nonce
        if (!isset($request->export_nonce) || !wp_verify_nonce($request->export_nonce, WebinarSysteemJS::get_nonce_secret())) {
            die();
        }

        switch ($request->wpws_export) {
            case 'attendees_csv':
                self::attendees_csv($request->webinar_id);
                break;

            case 'attendees_bcc':
                self::attendees_bcc($request->webinar_id);
                break;

            case 'chats_csv':
                self::chats_csv($request->webinar_id);
                break;

            case 'questions_csv':
                self::questions_csv($request->webinar_id);
                break;

            case 'system_info':
                self::system_info();
                break;

            case 'system_log':
                self::system_log();
                break;
        }
    }
}
