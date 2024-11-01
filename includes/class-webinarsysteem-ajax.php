<?php

class WebinarSysteemAjax
{
    private static function get_query_param() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        return empty($_POST['query']) || $_POST['query'] == '' ? null : strtolower($_POST['query']);
    }

    private static function return_data($data = [])
    {
        header('Content-Type:application/json');
        echo wp_json_encode(array('status' => true, 'data' => $data));
        wp_die();
    }

    public static function raise_attendee_hand() {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;
        $subscriber = WebinarSysteemAttendees::get_attendee($request->webinar_id);

        if (!isset($subscriber->id) || $subscriber->id === 0) {
            wp_send_json_error(null, 422);
        }

        WebinarSysteemAttendees::modify_attendee(
            $subscriber->id,
            ['high_five' => (int)$subscriber->high_five === 1 ? 0 : 1],
            ['%s']
        );

        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function unraise_attendee_hands() {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        $subscribers = WebinarSysteemAttendees::get_webinar_attendees($request->webinar_id);

        foreach ($subscribers as $subscriber) {
            if (isset($subscriber->id) && $subscriber->id > 0) {
                WebinarSysteemAttendees::modify_attendee($subscriber->id, array('high_five' => 0));
            }
        }

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success([]);
    }

    public static function get_online_attendees($webinar_id = 0) {
        global $wpdb;

        $table = WSWEB_DB_TABLE_PREFIX.'subscribers';
        $now = gmdate('Y-m-d H:i:s');
        $query = "
            SELECT
                id,
                name,
                high_five,
                email,
                last_seen,
                TIMESTAMPDIFF(MINUTE, last_seen, %s) idle_minutes
            FROM $table
            WHERE webinar_id=%d and
              last_seen > date_add(%s, interval -10 minute);
        ";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $data = $wpdb->get_results($wpdb->prepare($query, $now, $webinar_id, $now));

        return [
            'count' => count($data),
            'attendees' => array_map(function ($row) {
                return [
                    'id' => (int)$row->id,
                    'name' => $row->name,
                    'hand_raised' => (bool)$row->high_five,
                    'last_seen' => $row->last_seen,
                    'idle_minutes' => (int) $row->idle_minutes,
                    'is_team_member' => false
                ];
            }, $data),
        ];
    }

    public static function sync_import_images()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->img_values) || empty($request->img_names)) {
            wp_send_json_error(null, 400);
        }

        $newImagesPath = [];
        $namesSet = [];
        $count = 0;

        foreach ($request->img_values as $imgUrl) {
            // Download to current server
            if ($status = self::does_url_exist($imgUrl)) {
                $newImgName = basename($imgUrl);
                $uploadDir = wp_upload_dir();
                $directory = $uploadDir['path'] . '/' . $newImgName;
                $path = $uploadDir['url'] . '/' . $newImgName;

                $namesSet[] = $request->img_names[$count];
                $newImagesPath[] = $path;

                if (!file_exists($directory)) {
                    try {
                        copy($imgUrl, $directory);
                        self::register_image($newImgName, $directory, $path);
                    } catch (Exception $exc) {
                        $count++;
                    }
                }
            }

            $count++;
        }

        $main_bucket['names'] = $namesSet;
        $main_bucket['values'] = $newImagesPath;
        echo wp_json_encode($main_bucket);
        wp_die();

        /*
        wp_send_json_success([
            'names' => $namesSet,
            'values' => $newImagesPath,
            'count' => $count
        ]);
        */
    }

    public static function register_image($fileName, $filePath, $fileUrl)
    {
        try {
            $wpFiletype = wp_check_filetype($filePath, null);

            $attachment = [
                'guid' => $fileUrl,
                'post_mime_type' => $wpFiletype['type'],
                'post_title' => $fileName,
                'post_status' => 'inherit',
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                'post_date' => date('Y-m-d H:i:s'),
            ];

            $attachmentId = wp_insert_attachment($attachment, $filePath);
            $attachmentData = wp_generate_attachment_metadata($attachmentId, $filePath);

            wp_update_attachment_metadata($attachmentId, $attachmentData);
        } catch (Exception $e) {
            echo esc_html( $e->getTraceAsString());
        }
    }

    public static function does_url_exist($url) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init
        $ch = curl_init($url);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
        curl_setopt($ch, CURLOPT_NOBODY, true);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
        curl_exec($ch);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
        curl_close($ch);

        return $code === 200;
    }

    public static function get_chats($webinarId, $pageCategory) {
        global $wpdb;

        $tableChats = WebinarSysteemTables::get_chats();
        $tableSubsc = WebinarSysteemTables::get_subscribers();
        $tableQuest = WebinarSysteemTables::get_questions();

        $chats_query = "SELECT 
            {$tableChats}.id,
            {$tableChats}.webinar_id,
            attendee_id,
            content,
            timestamp,
            name,
            `admin`,
            `private`
        FROM {$tableChats}
        LEFT OUTER JOIN {$tableSubsc} w ON {$tableChats}.attendee_id=w.id
        WHERE {$tableChats}.webinar_id=%d
        ORDER BY id ASC
        ";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $chats = $wpdb->get_results($wpdb->prepare($chats_query, $webinarId));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $questions = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare("SELECT * FROM {$tableQuest} WHERE webinar_id=%d ORDER BY id DESC",
                $webinarId
            )
        );

        return [
            'questions' => $questions,
            'chats' => $chats,
            'show_chatbox' => get_post_meta($webinarId, '_wswebinar_' . $pageCategory . 'show_chatbox', true) === 'yes',
            'show_questionbox' => get_post_meta($webinarId, '_wswebinar_' . $pageCategory . 'askq_yn', true) === 'yes'
        ];
    }

    public static function get_webinar_chats($webinar_id)
    {
        global $wpdb;

        $chats_table = WebinarSysteemTables::get_chats();
        $attendees_table = WebinarSysteemTables::get_subscribers();

        $query = "
            select
                {$chats_table}.id,
                {$chats_table}.webinar_id,
                attendee_id,
                content,
                timestamp,
                name,
                `admin` is_team_member,
                private is_private
            from {$chats_table}
            left outer join {$attendees_table} attendees
              on {$chats_table}.attendee_id = attendees.id
            where {$chats_table}.webinar_id = %d
            order by id asc
        ";

        return array_map(
            function ($chat) {
                return [
                    'id' => (int)$chat['id'],
                    'attendee_id' => (int)$chat['attendee_id'],
                    'is_private' => $chat['is_private'] == '1',
                    'is_team_member' => $chat['is_team_member'] == '1',
                    'name' => $chat['name'],
                    'created_at' => strtotime($chat['timestamp']),
                    'content' => $chat['content']
                ];
            },
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->get_results($wpdb->prepare($query, $webinar_id),
                ARRAY_A
            )
        );
    }

    public static function get_webinar_questions($webinar_id)
    {
        global $wpdb;

        $questions_table = WebinarSysteemTables::get_questions();
        $attendees_table = WebinarSysteemTables::get_subscribers();

        $query = "
            select
              q.*,
              answered_by.name answered_by_name,
              asked_by.name asked_by_name
            from {$questions_table} q
            left join {$attendees_table} answered_by
              on q.answered_by = answered_by.id
            left join {$attendees_table} asked_by
              on q.asked_by = asked_by.id
            where q.webinar_id = %d
            order by q.id asc;
        ";

        return array_map(
            function ($question) {
                return [
                    'id' => (int)$question['id'],
                    'name' => $question['asked_by_name'] != null
                        ? $question['asked_by_name'] : $question['name'],
                    'created_at' => strtotime($question['time']),
                    'question' => $question['question'],
                    'is_private' => $question['is_private'] == '1',
                    'answer' => $question['answer'] == null
                        ? null : $question['answer'],
                    'asked_by' => $question['asked_by'] == null
                        ? null : (int)$question['asked_by'],
                    'answered_by' => $question['answered_by'] == null
                        ? null : (int)$question['answered_by'],
                    'answered_at' => $question['answered_at'] == null
                        ? null : strtotime($question['answered_at']),
                    'answered_by_name' => $question['answered_by_name'],
                ];
            },
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->get_results($wpdb->prepare($query, $webinar_id),
                ARRAY_A
            )
        );
    }

    public static function delete_webinar_question() {
        WebinarSysteemJS::check_ajax_nonce();

        global $wpdb;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;
        $attendee = WebinarSysteemAttendees::get_attendee($request->webinar_id);

        if (empty($request->question_id) ||
            empty($request->webinar_id) ||
            $attendee == null) {
            wp_send_json_error(null, 400);
        }

        $table = WebinarSysteemTables::get_questions();

        if (current_user_can('manage_options')) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete($table, [
                'id' => $request->question_id,
                'webinar_id' => $request->webinar_id,
            ], ['%d', '%d']);
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete($table, [
                'id' => $request->question_id,
                'webinar_id' => $request->webinar_id,
                'asked_by' => $attendee->id
            ], ['%d', '%d', '%d']);
        }

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    private static function get_key_prefix_from_status($webinar_id, $status) {
        $status = empty($status)
            ? 'replay'
            : $status;

        $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);

        $is_recurring = $webinar->is_recurring();

        if (!$is_recurring && in_array($status, ['live', 'liv', 'cou'], true)) {
            return '_wswebinar_livep_';
        }

        return '_wswebinar_replayp_';
    }

    public static function incentiveStatus($webinar_id, $status)
    {
        // TODO, refactor this when releasing the new live view
        $key = self::get_key_prefix_from_status($webinar_id, $status).'incentive_yn';
        $incentive_status = get_post_meta($webinar_id, $key, true);

        return [
            'isShow' => $incentive_status === 'yes']
        ;
    }

    public static function update_incentive()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // TODO, refactor this when releasing the new live view
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        $status = $request->status;
        $status = empty($status)
            ? 'replay'
            : $status;

        $key = self::get_key_prefix_from_status($request->webinar_id, $status).'incentive_yn';

        if (!empty($request->incentive_status)) {
            $new_value = $request->incentive_status;
        } else {
            $current_value = get_post_meta($request->webinar_id, $key, true);
            $new_value = ($current_value == 'yes' ? '' : 'yes');
        }

        update_post_meta($request->webinar_id, $key, $new_value);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success([
            'incentive_status' => $new_value == 'yes',
        ]);
    }

    public static function get_cta_status($webinarId, $page)
    {
        if (get_post_meta($webinarId, '_wswebinar_' . $page . 'call_action', true) === 'aftertimer') {
            $plusMinutes = get_post_meta($webinarId, '_wswebinar_' . $page . 'cta_show_after', true);
            $webinarStarted = WebinarSysteem::get_webinar_time($webinarId, WebinarSysteemAttendees::get_attendee($webinarId));
            $addTime = strtotime('+ ' . $plusMinutes . ' minutes', $webinarStarted);
            $curTime = strtotime(WebinarSysteem::getTimezoneTime($webinarId));

            return ($addTime < $curTime);
        }

        return get_post_meta($webinarId, '_wswebinar_' . $page . 'manual_show_cta', true) === 'yes';
    }


    public static function set_cta_status()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id) || empty($request->cta_status)) {
            wp_send_json_error(null, 400);
        }

        $status = get_post_meta($request->webinar_id, '_wswebinar_gener_webinar_status', true);
        $pageState = ($status === 'live' || $status === 'liv') ? 'livep_' : 'replayp_';

        // update status
        if (!update_post_meta($request->webinar_id, '_wswebinar_' . $pageState . 'manual_show_cta', $request->cta_status)) {
            wp_send_json_error(null, 422);
        }

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success([
            'showStatus' => $request->cta_status === 'yes',
        ]);
    }

    public static function setHostUpdateBox()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id) || empty($request->webinar_status) || empty($request->box_status)) {
            wp_send_json_error(null, 400);
        }

        update_post_meta($request->webinar_id, '_wswebinar_' . $request->webinar_status . 'hostbox_yn', $request->box_status);
        update_post_meta($request->webinar_id, '_wswebinar_' . $request->webinar_status . 'webdes_yn', $request->box_status);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function getHostDescStatus($webinarId, $page)
    {
        $hostbox = get_post_meta($webinarId, '_wswebinar_' . $page . 'hostbox_yn', true);
        $descbox = get_post_meta($webinarId, '_wswebinar_' . $page . 'webdes_yn', true);

        return $hostbox === 'yes' || $descbox === 'yes';
    }

    public static function setActionBox()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id) || empty($request->box_status)) {
            wp_send_json_error(null, 400);
        }

        $status = get_post_meta($request->webinar_id, '_wswebinar_gener_webinar_status', true);
        $pageState = ($status === 'live' || $status === 'liv') ? 'livep_' : 'replayp_';

        // update status
        if (!update_post_meta($request->webinar_id, '_wswebinar_' . $pageState . 'show_actionbox', $request->box_status)) {
            wp_send_json_error(null, 422);
        }

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success([
            'showStatus' => $request->box_status === 'yes'
        ]);
    }

    public static function getActionBoxStatus($webinarId, $page)
    {
        return get_post_meta($webinarId, '_wswebinar_' . $page . 'show_actionbox', true) === 'yes';
    }

    /*
     * Delete selected or all chats.
     */
    public static function deleteChats()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id) || empty($request->messages)) {
            wp_send_json_error(null, 400);
        }

        // TODO make sure the attendee has permission?
        foreach ($request->messages as $id) {
            self::deleteChatEntry($id);
        }

        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function deleteChatEntry($chatId)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete(
            WebinarSysteemTables::get_chats(),
            ['id' => $chatId],
            '%d'
        );
    }

    public static function deleteQuestions()
    {
        WebinarSysteemJS::check_ajax_nonce();
        
        global $wpdb;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->delete_type) || empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
            return;
        }

        $table = WebinarSysteemTables::get_questions();

        if ($request->delete_type === 'all') {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete($table, ['webinar_id' => $request->webinar_id], '%d');
        } else {
            if (empty($request->question_ids)) {
                wp_send_json_error(null, 400);
                return;
            }

            foreach ($request->question_ids as $id) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->delete($table, ['id' => $id], '%d');
            }
        }

        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success([
            'ids' => $request->question_ids,
            'type' => $request->delete_type,
        ]);
    }

    public static function updateWebinarCache()
    {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function updateLastSeen()
    {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        $attendee = WebinarSysteemAttendees::get_attendee($request->webinar_id);
        $seconds_attended = $attendee->seconds_attended;

        if (isset($request->seconds_since_last_update)) {
            $seconds_attended += (int) $request->seconds_since_last_update;
        }

        if (!isset($attendee->id) || $attendee->id == 0) {
            wp_send_json_success(
                ['has_valid_session' => false]
            );
            return;
        }

        // update the last seen of this attendee
        WebinarSysteemAttendees::update_last_seen($attendee->id, $seconds_attended);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success(
            ['has_valid_session' => true]
        );
    }

    public static function post_question()
    {
        WebinarSysteemJS::check_ajax_nonce();
        
        global $wpdb;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->question) ||
            empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        $attendee = WebinarSysteemAttendees::get_attendee($request->webinar_id);

        if ($attendee === null) {
            wp_send_json_error(null, 422);
            return;
        }

        $tableName = WebinarSysteemTables::get_questions();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $num = $wpdb->insert(
            $tableName,
            [
                'name' => sanitize_text_field($attendee->name),
                'email' => sanitize_text_field($attendee->email),
                'question' => str_replace('\\', '', sanitize_textarea_field($request->question)),
                'time' => current_time('mysql', 1),
                'webinar_id' => sanitize_text_field($request->webinar_id),
                'asked_by' => $attendee->id,
                'is_private' => $request->is_private == 'true'
            ]
        );

        if ($num !== 1) {
            wp_send_json_error(null, 422);
            return;
        }

        (new WebinarSysteemEmails())
            ->send_question_to_host(
                $request->webinar_id,
                $attendee->name,
                $attendee->email,
                $request->question);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
        $now_in_webinar = date("Y-m-d H:i A", WebinarSysteemWebinar::get_now_in_webinar_timezone($request->webinar_id));

        wp_send_json_success([
            'time' => $now_in_webinar
        ]);
    }

    public static function post_question_answer()
    {
        WebinarSysteemJS::check_ajax_nonce();

        global $wpdb;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!WebinarSysteemPermissions::user_is_team_member()
            || empty($request->question_id)
            || empty($request->webinar_id)
            || empty($request->answer)) {
            wp_send_json_error(null, 400);
        }

        $attendee = WebinarSysteemAttendees::get_attendee($request->webinar_id);

        // make sure this is a team member
        if ($attendee === null || !current_user_can('manage_options')) {
            wp_send_json_error(null, 400);
            return;
        }

        $table = WebinarSysteemTables::get_questions();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table, [
                'answered_at' => current_time('mysql', 1),
                'answered_by' => $attendee->id,
                'answer' => str_replace('\\', '', sanitize_textarea_field($request->answer)),
            ], [
                'id' => $request->question_id,
                'webinar_id' => $request->webinar_id
            ]);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function update_question_visibility()
    {
        WebinarSysteemJS::check_ajax_nonce();

        global $wpdb;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!WebinarSysteemPermissions::user_is_team_member()
            || empty($request->question_id)
            || empty($request->webinar_id)
            || empty($request->is_private)) {
            wp_send_json_error(null, 400);
        }

        $attendee = WebinarSysteemAttendees::get_attendee($request->webinar_id);

        // make sure this is a team member
        if ($attendee === null || !current_user_can('manage_options')) {
            wp_send_json_error(null, 400);
            return;
        }

        $table = WebinarSysteemTables::get_questions();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table, [
                'is_private' => $request->is_private == 'Yes'
            ], [
                'id' => $request->question_id,
                'webinar_id' => $request->webinar_id
            ]);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function clear_question_answer()
    {
        WebinarSysteemJS::check_ajax_nonce();

        global $wpdb;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!WebinarSysteemPermissions::user_is_team_member()
            || empty($request->question_id)
            || empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        $attendee = WebinarSysteemAttendees::get_attendee($request->webinar_id);

        // make sure this is a team member
        if ($attendee === null || !current_user_can('manage_options')) {
            wp_send_json_error(null, 400);
            return;
        }

        $table = WebinarSysteemTables::get_questions();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table, [
            'answered_at' => null,
            'answered_by' => null,
            'answer' => null,
        ], [
            'id' => $request->question_id,
            'webinar_id' => $request->webinar_id
        ]);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function post_chat_message()
    {
        WebinarSysteemJS::check_ajax_nonce();

        global $wpdb;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->message)
            || empty($request->webinar_id)
            || empty($request->is_admin)
            || empty($request->is_private)) {
            wp_send_json_error(null, 400);
        }

        $attendee = WebinarSysteemAttendees::get_attendee($request->webinar_id);

        if ($attendee === null) {
            wp_send_json_error(null, 422);
            return;
        }

        // TODO, replace this with UTC?
        // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
        $timestamp = date('Y-m-d H:i:s', WebinarSysteemWebinar::get_now_in_webinar_timezone($request->webinar_id));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $num = $wpdb->insert(
            WebinarSysteemTables::get_chats(),
            [
                // 'id' => (int)(microtime(true) * 1000),
                'webinar_id' => sanitize_text_field($request->webinar_id),
                'admin' => $request->is_admin === 'true',
                'private' => $request->is_private === 'true',
                'attendee_id' => $attendee->id,
                'content' => str_replace('\\', '', sanitize_textarea_field($request->message)),
                'timestamp' => $timestamp,
            ]
        );

        if ($num !== 1) {
            wp_send_json_error(null, 422);
            return;
        }

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function setEnabledChats()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!WebinarSysteemPermissions::user_is_team_member()
            || empty($request->webinar_id)
            || empty($request->active)) {
            wp_send_json_error(null, 400);
        }

        $page = self::get_live_webinar_page_prefix($request->webinar_id);

        $metaValue = ($request->active === 'true' ? 'yes' : '');
        $questionbox = get_post_meta($request->webinar_id, '_wswebinar_' . $page . 'askq_yn', true);

        if (!update_post_meta($request->webinar_id, '_wswebinar_' . $page . 'show_chatbox', $metaValue)) {
            wp_send_json_error(null, 422);
        }

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success([
            'show_chatbox' => $request->active === 'true',
            'show_questionbox' => $questionbox === 'yes'
        ]);
    }

    public static function setEnabledQuestions()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!WebinarSysteemPermissions::user_is_team_member()
            || empty($request->webinar_id)
            || empty($request->active)) {
            wp_send_json_error(null, 400);
        }

        $page = self::get_live_webinar_page_prefix($request->webinar_id);

        $meta_val = ($request->active === 'true' ? 'yes' : '');
        $chatbox = get_post_meta($request->webinar_id, '_wswebinar_' . $page . 'show_chatbox', true);

        if (!update_post_meta($request->webinar_id, '_wswebinar_' . $page . 'askq_yn', $meta_val)) {
            wp_send_json_error(null, 422);
        }

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success([
            'show_chatbox' => $chatbox === 'yes',
            'show_questionbox' => $request->active === 'true'
        ]);
    }

    protected static function get_live_webinar_page_prefix($webinar_id) {
        $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);
        return $webinar->get_live_page_prefix();
    }

    public static function set_attendees_tab_visible()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!WebinarSysteemPermissions::user_is_team_member()
            || empty($request->webinar_id)
            || empty($request->active)) {
            wp_send_json_error(null, 400);
        }

        $page = self::get_live_webinar_page_prefix($request->webinar_id);

        $meta_val = $request->active === 'yes'
            ? 'yes'
            : '';

        update_post_meta(
            $request->webinar_id,
            '_wswebinar_'.$page.'show_attendees_yn',
            $meta_val);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function set_cta_visible()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!WebinarSysteemPermissions::user_is_team_member()
            || empty($request->webinar_id)
            || empty($request->active)) {
            wp_send_json_error(null, 400);
        }

        $page = self::get_live_webinar_page_prefix($request->webinar_id);

        $meta_val = $request->active === 'yes'
            ? 'yes'
            : '';

        update_post_meta(
            $request->webinar_id,
            '_wswebinar_'.$page.'manual_show_cta',
            $meta_val);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    //oauth_token
    public static function set_webinar_status()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id) ||
            empty($request->status) ||
            !in_array($request->status, ['live', 'liv', 'cou', 'clo', 'rep']) ||
            !WebinarSysteemPermissions::user_is_team_member()) {
            wp_send_json_error(null, 400);
        }

        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);
        $webinar->set_status($request->status);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function set_media_source()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id) ||
            empty($request->type) ||
            empty($request->url) ||
            !WebinarSysteemPermissions::user_is_team_member()) {
            wp_send_json_error(null, 400);
        }

        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);

        // set the webinar
        $webinar->set_live_media_type($request->type);
        $webinar->set_live_media_url($request->url);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    /*
     * Check webinar status via AJAX
     */
    public static function ajaxCheckIfWebinarStatusLive()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;
        if (empty($request->post_id)) {
            wp_send_json_error(null, 400);
        }
        $wsWebinarGenerDuration = WebinarSysteem::getWebinarDuration($request->post_id);
        $attendee = WebinarSysteemAttendees::get_attendee($request->post_id);
        $webinerT = WebinarSysteem::get_webinar_time($request->post_id, $attendee);

        if (WebinarSysteem::is_recurring_webinar($request->post_id)) {
            wp_send_json_success([
                'status' => $webinerT <= WebinarSysteemWebinar::get_now_in_webinar_timezone($request->post_id) && WebinarSysteemWebinar::get_now_in_webinar_timezone($request->post_id) <= ($webinerT + $wsWebinarGenerDuration)
            ]);
        }

        if (WebinarSysteem::webinarAirType($request->post_id) === 'rec' && ($webinerT <= WebinarSysteemWebinar::get_now_in_webinar_timezone($request->post_id) && WebinarSysteemWebinar::get_now_in_webinar_timezone($request->post_id) <= ($webinerT + $wsWebinarGenerDuration))) {
            wp_send_json_success([
                'status' => true
            ]);
        }

        wp_send_json_success([
            'status' => get_post_meta($request->post_id, '_wswebinar_gener_webinar_status', true) === 'liv'
        ]);
    }

    public static function test_new_registration_webhook()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $webhook_url = $_POST['webhook_url'];
        $sent = WebinarSysteemWebHooks::test_new_registration($webhook_url);

        self::return_data(
            array('ok' => $sent)
        );
    }

    public static function test_attended_webinar_webhook()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $webhook_url = $_POST['webhook_url'];
        $sent = WebinarSysteemWebHooks::test_attended_webinar($webhook_url);

        self::return_data(
            array('ok' => $sent)
        );
    }

    static function format_form_response($form) {
        $webinar = $form->get_webinar();

        return [
            'id' => $form->id,
            'name' => $form->name,
            'created_at' => $form->created_at,
            'registrations' => $form->get_registration_count(),
            'webinar_name' => $webinar ? $webinar->name : ''
        ];
    }

    public static function get_registration_widgets()
    {
        WebinarSysteemJS::check_ajax_nonce();

        $forms = WebinarSysteemRegistrationWidget::get_widgets();
        $result = [];

        foreach ($forms as $form) {
            $result[] = self::format_form_response($form);
        };

        self::return_data($result);
    }

    public static function get_registration_widget_shortcode_html()
    {
        WebinarSysteemJS::check_ajax_nonce();

        $script = WebinarSysteemJS::get_js_path() . '?v=' . WebinarSysteemJS::get_version();

        // register local variables
        $translations = get_translations_for_domain(WebinarSysteem::$lang_slug);
        $data = array(
            'locale' => get_locale(),
            //'language' => $translations->get_header('Language'),
            'language' => get_locale(),
            'ajax' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce(WebinarSysteemJS::get_nonce_secret()),
            'base' => WebinarSysteemJS::get_asset_path(),
            'plugin' => WebinarSysteemJS::get_plugin_path()
        );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        $id = intval($_POST['id']);

        if ($id == 0) {
            wp_send_json_error(null, 400);
        }

        // get the form
        $params = WebinarSysteemRegistrationWidget::get_widget_params($id);
        if (!$params) {
            return '';
        }

        // get the webinar
        $webinar = WebinarSysteemWebinar::create_from_id($params->webinarId);
        if (!$webinar) {
            wp_send_json_error(null, 400);
        }

        $webinar_info = WebinarSysteemRegistrationWidget::get_webinar_info($webinar);
        ob_start();
        ?><div class="wpws_registration_widget" data-webinar='<?php echo esc_attr(str_replace('\'', '&apos;', wp_json_encode($webinar_info))) ?>' data-params='<?php echo esc_attr(str_replace('\'', '&apos;', wp_json_encode($params))) ?>' data-widgetId='<?php echo esc_attr($id) ?>'></div>
<script>if(!window.___wpws) { window.___wpws = JSON.parse('<?php echo wp_json_encode($data);?>'); }</script>
<?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
<script src="<?php echo esc_attr($script); ?>"></script><?php
        self::return_data(ob_get_clean());
    }

    public static function delete_registration_widget()
    {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        $id = intval($_POST['id']);

        if (empty($id) || $id == 0) {
            wp_send_json_error(null, 400);
        }

        if (!WebinarSysteemRegistrationWidget::delete_widget($id)) {
            wp_send_json_error(null, 400);
        }

        self::return_data();
    }

    public static function delete_webinar()
    {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        $id = intval($_POST['id']);

        if (empty($id) || $id == 0) {
            wp_send_json_error(null, 400);
        }

        if (!WebinarSysteemWebinar::delete_webinar($id)) {
            wp_send_json_error(null, 400);
        }

        self::return_data();
    }

    public static function save_registration_widget()
    {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $id = empty($_POST['id']) ? null : intval($_POST['id']);

        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $params_json = stripslashes($_POST["params"]);

        // decode the params
        $params = json_decode($params_json);

        if (empty($params)) {
            wp_send_json_error(null, 400);
            return;
        }

        $post_id = WebinarSysteemRegistrationWidget::add_or_update_widget($id, $params);

        if (empty($post_id)) {
            wp_send_json_error(null, 400);
            return;
        }

        // return the updated forms
        $forms = WebinarSysteemRegistrationWidget::get_widgets();
        $result = [];

        foreach ($forms as $form) {
            $result[] = self::format_form_response($form);
        };

        self::return_data(
            [
                'forms' => $result,
                'id' => $post_id,
            ]
        );
    }

    public static function get_translations()
    {
        WebinarSysteemJS::check_ajax_nonce();

        $domain_translations = get_translations_for_domain(WebinarSysteem::$lang_slug);

        /*$translations = (object) array_map(function ($entry) {
            return $entry->translations[0];
        }, $domain_translations->entries);*/

        $translations = array();
        foreach ($domain_translations->entries as $entry) {
            $singular = $entry->singular;
            $translation = $entry->translations[0] ?? '';
            $translations[$singular] = $translation;
        }

        self::return_data($translations);
    }

    public static function get_registration_widget_params()
    {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (empty($_POST['id'])) {
            wp_send_json_error(null, 400);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $id = intval($_POST['id']);

        if ($id == 0) {
            wp_send_json_error(null, 400);
        }

        self::return_data([
            'params' => WebinarSysteemRegistrationWidget::get_widget_params($id),
            'webinars' => WebinarSysteemRegistrationWidget::get_webinars()
        ]);
    }

    public static function get_webinars()
    {
        WebinarSysteemJS::check_ajax_nonce();

        $webinars = WebinarSysteemRegistrationWidget::get_webinars();

        self::return_data($webinars);
    }

    public static function get_pages_and_posts()
    {
        WebinarSysteemJS::check_ajax_nonce();

        function title_filter($where) {
            global $wpdb;

            // this is messy, there must be a way to pass $query into the filter?
            // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $query = empty($_POST['query']) && $_POST['query'] != '' ? null : strtolower($_POST['query']);

            if ($query != null) {
                $where .= ' AND '.$wpdb->posts.'.post_title LIKE \'%' . esc_sql($wpdb->esc_like($query)).'%\'';
            }
            return $where;
        }

        add_filter('posts_where', 'title_filter', 10, 2);

        $posts = get_posts([
            'post_type' => ['page', 'post'],
            'posts_per_page' => 10,
            'orderby' => 'post_title',
            'order' => 'ASC',
            'suppress_filters' => false
        ]);

        remove_filter('posts_where', 'title_filter', 10);

        $results = array_map(function ($row) {
            return [
                'id' => (int)$row->ID,
                'title' => $row->post_title
            ];
        }, $posts);

        // add the selected value if any
        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (isset($_POST['value']) && strlen($_POST['value']) > 0) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $selected_id = (int) sanitize_text_field($_POST['value']);

            // do we have this result already?
            $has_result = current(array_filter($results, function($element) use($selected_id) {
                return $element['id'] == $selected_id;
            }));

            if ($has_result == false) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $title =  get_the_title(sanitize_text_field($_POST['value']));

                if (strlen($title) > 0) {
                    array_unshift($results, [
                        'id' => $selected_id,
                        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                        'title' => get_the_title(sanitize_text_field($_POST['value']))
                    ]);
                }
            }
        }

        self::return_data($results);
    }

    public static function get_timezones()
    {
        WebinarSysteemJS::check_ajax_nonce();

        $query = self::get_query_param();

        $timezones = WebinarSysteem::get_available_timezones();
        $result = [];

        foreach ($timezones as $value => $label) {
            if ($query != null && strpos(strtolower($label), $query) === false) {
                continue;
            }
            $result[] = [
                'id' => $value,
                'label' => $label
            ];
        }

        self::return_data($result);
    }

    public static function login_attendee() {
        
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id) || empty($request->email)) {
            wp_send_json_error(null, 400);
        }

        $is_registered = WebinarSysteem::is_already_registered_for_webinar(
            sanitize_text_field($request->webinar_id),
            sanitize_email($request->email));

        if (!$is_registered) {
            wp_send_json_error(null, 401);
        }

        wp_send_json_success([
            'url' => get_permalink($request->webinar_id)
        ]);
    }

    /*
    This is needed when logging in/registering from an embedded HTML widget
    that's hosted on a different host because browsers don't set the cookies
    properly from ajax
    */
    public static function login_attendee_with_redirect() {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id) || empty($request->email)) {
            wp_send_json_error(null, 400);
        }

        $is_registered = WebinarSysteem::is_already_registered_for_webinar(
            sanitize_text_field($request->webinar_id),
            sanitize_email($request->email));

        if (!$is_registered) {
            wp_send_json_error(null, 401);
        }

        $redirect_url = isset($request->redirect)
            ? $request->redirect
            : get_permalink($request->webinar_id);

        wp_redirect($redirect_url);
        die();
    }

    public static function attempt_login_from_auth() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id) || empty($request->auth)) {
            wp_send_json_error(null, 400);
        }

        $auth_data = WebinarSysteemBase64::decode_array($request->auth);;

        if ($auth_data == null || !is_array($auth_data) || count($auth_data) != 2) {
            wp_send_json_error(null, 402);
        }

        $is_registered = WebinarSysteem::try_login_from_secret(
            $request->webinar_id,
            $auth_data[0],
            $auth_data[1]
        );

        if (!$is_registered) {
            wp_send_json_error(null, 401);
        }

        wp_send_json_success([
            'url' => get_permalink($request->webinar_id)
        ]);
    }

    public static function get_remaining_places_for_webinar() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        // don't allow registering for paid webinars
        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);

        if (!$webinar->get_maximum_attendee_enabled()) {
            wp_send_json_error(null, 401);
        }

        $exact_time = isset($request->exact_time)
            ? intval(filter_var(
                $request->exact_time,
                FILTER_SANITIZE_NUMBER_INT
            )) : null;

        $max_count = $webinar->get_maximum_attendee_count();
        $registered_count = $webinar->get_registration_count($exact_time);

        $remaining = max($max_count - $registered_count, 0);

        wp_send_json_success($remaining);
    }

    public static function register_attendee() {
        
        WebinarSysteemJS::check_ajax_nonce();
        
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;
        
        if (empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        // don't allow registering for paid webinars
        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);

        if ($webinar->get_registration_name_mode() == 'required' && empty($request->name)) {
            wp_send_json_error(null, 400);
        }

        if ($webinar->get_registration_email_mode() == 'required' && empty($request->email)) {
            wp_send_json_error(null, 400);
        }

        if ($webinar->is_email_confirmation_required() && empty($request->email)) {
            wp_send_json_error(null, 400);
        }

        $exact_time = isset($request->session_datetime)
            ? intval(filter_var(
                $request->session_datetime,
                FILTER_SANITIZE_NUMBER_INT
            )) : null;

        // If the attendee is already registered (with a valid session) don't
        // register them again
        // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
        $is_registered = WebinarSysteem::is_already_registered_for_webinar(sanitize_text_field($request->webinar_id), sanitize_email($request->email), date('Y-m-d H:i:s', $exact_time));

        if ($is_registered) {
            wp_send_json_success([
                'url' => get_permalink($request->webinar_id)
            ]);
            return;
        }

        if ($webinar == null) {
            wp_send_json_error(null, 401);
        }

        if ($webinar->is_paid() && !WebinarSysteemPermissions::user_is_team_member()) {
            wp_send_json_error(null, 401);
        }

        if ($webinar->is_registration_disabled()) {
            wp_send_json_error(null, 401);
        }

        if ($webinar->is_password_protected() && $request->password != $webinar->get_password()) {
            wp_send_json_error(null, 403);
        }

        // make sure we haven't passed the limit
        if ($webinar->get_maximum_attendee_enabled()) {
            $max_count = $webinar->get_maximum_attendee_count();

            if ($max_count > 0 && $webinar->get_registration_count($exact_time) >= $max_count) {
                wp_send_json_error(null, 402);
            }
        }

        // increment the stats
        if (isset($request->widget_id)) {
            $form = WebinarSysteemRegistrationWidget::create_from_id($request->widget_id);
            if ($form) {
                $form->increment_registration_count();
            }
        }

        $name = isset($request->name)
            ? sanitize_text_field($request->name)
            : null;

        $email = isset($request->email)
            ? sanitize_text_field($request->email)
            : null;

        // TODO, refactor this!
        $custom_fields = WebinarSysteem::get_custom_fields_from_request($request->webinar_id);

        // A required field was not provided
        if ($custom_fields == null) {
            wp_send_json_error(null, 403);
        }

        // send pending email
        if ($webinar->is_email_confirmation_required()) {
            $emails = $emails = new WebinarSysteemEmails();

            $emails->send_pending_email_confirmation((object) [
                'name' => $name,
                'email' => $email,
                'webinar_id' => $webinar->id,
                'exact_time' => $exact_time,
                'custom_fields' => $custom_fields,
            ]);

            if ($webinar->get_email_confirmation_required_action() === 'redirect-page') {
                wp_send_json_success([
                    'url' => get_permalink($webinar->get_email_confirmation_required_redirect_page_id()),
                ]);
                return;
            }

            wp_send_json_success([
                'status' => 'pending',
                'url' => null,
            ]);
        }

        // register the attendee
        WebinarSysteem::register_webinar_attendee(
            $request->webinar_id,
            $name,
            $email,
            $exact_time,
            null,
            null,
            false,
            false,
            false,
            [
                'custom_fields' => $custom_fields
            ]
        );

        $redirect_url = null;
        $key = WebinarSysteemWebinarSession::get_registration_key($webinar->id);
        $token = WebinarSysteemConfirmationTokenParser::generate_token($webinar->id, $key);

        if (isset($request->custom_thank_you_page_id) &&
            intval($request->custom_thank_you_page_id) > 0) {
            $redirect_url = get_permalink($request->custom_thank_you_page_id);
        }

        if (!isset($redirect_url)) {
            $redirect_url = get_permalink($request->webinar_id);
        }

        wp_send_json_success([
            'url' => WebinarSysteemHelperFunctions::add_param_to_url($redirect_url, 'token='.$token),
        ]);
    }

    public static function set_hand_raising_enabled()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id) || empty($request->enabled)) {
            wp_send_json_error(null, 400);
        }

        $page = self::get_live_webinar_page_prefix($request->webinar_id);

        // update status
        if (!update_post_meta($request->webinar_id, '_wswebinar_' . $page . 'hand_raising_yn', $request->enabled)) {
            wp_send_json_error(null, 422);
        }

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success([
            'status' => $request->enabled === 'yes',
        ]);
    }

    public static function get_webinar_params()
    {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);

        if ($webinar == null) {
            wp_send_json_error(null, 401);
        }
        wp_send_json_success($webinar->get_params());
    }

    public static function update_webinar_slug()
    {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);
        $webinar->set_slug($request->slug);

        wp_send_json_success([
            'url' => $webinar->get_url(),
            'slug' => $webinar->get_slug()
        ]);
    }

    public static function update_webinar_status()
    {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);
        if ($webinar == null) {
            wp_send_json_error(null, 401);
        }

        $webinar->set_post_status($request->status);

        wp_send_json_success();
    }

    public static function update_webinar_params()
    {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;
        $params_json = stripslashes($request->params);

        // decode the params
        $params = json_decode($params_json);

        if (empty($params)) {
            wp_send_json_error(null, 401);
            return;
        }

        if (empty($request->webinar_id)) {
            $general = $params->general;
            $webinar_id = WebinarSysteemWebinar::create_empty_webinar($general->name);
        } else {
            $webinar_id = $request->webinar_id;
        }

        $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);
        $webinar->update_from_params($params);

        // write the cache
        WebinarSysteemWebinarCache::write($webinar_id);

        wp_send_json_success([
            'id' => $webinar_id,
            'url' => $webinar->get_url(),
            'slug' => $webinar->get_slug()
        ]);
    }

    public static function get_mailinglist_accounts() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::user_is_team_member()) {
            wp_send_json_error(null, 400);
            return;
        }

        $query = self::get_query_param();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $provider = $_POST['provider'];
        $unfiltered = WebinarsysteemMailingListIntegrations::get_accounts_for_provider($provider);

        $result = [];

        foreach ($unfiltered as $value) {
            if ($query != null && strpos(strtolower($value->name), $query) === false) {
                continue;
            }
            $result[] = [
                'id' => $value->id,
                'name' => $value->name
            ];
        }

        self::return_data($result);
    }

    public static function get_mailinglist_lists() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::user_is_team_member()) {
            wp_send_json_error(null, 400);
            return;
        }

        $query = self::get_query_param();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $provider = $_POST['provider'];
        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $account_id = $_POST['account_id'];
        $unfiltered = WebinarsysteemMailingListIntegrations::get_mailinglist_lists_for_provider($provider, $account_id);
        
        $result = [];
        if($provider == 'mailchimp'){
            foreach ($unfiltered as $value) {
                if ($query != null && strpos(strtolower($value['name']), $query) === false) {
                    continue;
                }
                $result[] = [
                    'id' => $value['id'],
                    'name' => $value['name']
                ];
            }
        }else{
            foreach ($unfiltered as $value) {
                if ($query != null && strpos(strtolower($value->name), $query) === false) {
                    continue;
                }
                $result[] = [
                    'id' => $value->id,
                    'name' => $value->name
                ];
            }
        }
        
        self::return_data($result);
    }

    public static function get_wp_users() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::user_is_team_member()) {
            wp_send_json_error(null, 400);
            return;
        }

        $query = self::get_query_param();

        $unfiltered = get_users(['order' => 'ASC', 'number' => 100]);

        $result = [];

        foreach ($unfiltered as $value) {
            $name = "{$value->display_name} ({$value->user_email})";

            if ($query != null && strpos(strtolower($name), $query) === false) {
                continue;
            }

            $result[] = [
                'id' => $value->ID,
                'name' => $name
            ];
        }

        self::return_data($result);
    }

    public static function get_wp_roles() {
        global $wp_roles;

        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::user_is_team_member()) {
            wp_send_json_error(null, 400);
            return;
        }

        $query = self::get_query_param();

        $unfiltered = $wp_roles->get_names();
        $result = [];

        foreach ($unfiltered as $slug => $name) {
            if ($query != null && strpos(strtolower($name), $query) === false) {
                continue;
            }

            $result[] = [
                'id' => $slug,
                'name' => $name
            ];
        }

        self::return_data($result);
    }

    public static function get_woocommerce_roles() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::user_is_team_member()) {
            wp_send_json_error(null, 400);
            return;
        }

        if (!function_exists('wc_memberships_get_membership_plans')) {
            self::return_data([
                'is_installed' => false,
                'roles' => []
            ]);
            return;
        }

        $query = self::get_query_param();
        $result = [];

        $roles = wc_memberships_get_membership_plans();

        foreach ($roles as $membership) {
            if ($query != null && strpos(strtolower($membership->name), $query) === false) {
                continue;
            }

            $result[] = [
                'id' => (string) $membership->id,
                'name' => $membership->name
            ];
        }

        self::return_data([
            'is_installed' => true,
            'roles' => $result
        ]);
    }

    public static function get_upcoming_sessions() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (empty($request->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);

        if ($webinar == null) {
            wp_send_json_error(null, 400);
        }

        $timeslots_to_show = isset($request->timeslots_to_show)
            ? $request->timeslots_to_show
            : $webinar->get_timeslots_to_show(0);

        $days_to_offset = isset($request->days_to_offset)
            ? $request->days_to_offset
            : $webinar->get_recurring_offset_days(0);

        $sessions = WebinarSysteemSessions::get_upcoming_sessions_for_webinar(
            $webinar->id, $timeslots_to_show, $days_to_offset);

        wp_send_json_success([
            'sessions' => $sessions,
            'locale' => WebinarSysteem::get_locale(),
            'timezone_offset' => $webinar->get_timezone_offset()
        ]);
    }

    public static function enable_chat()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!WebinarSysteemPermissions::user_is_team_member() ||
            !isset($request->webinar_id) ||
            !isset($request->enabled)) {
            wp_send_json_error(null, 401);
        }

        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);
        $webinar->set_chat_enabled($request->enabled == 1);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function enable_questions()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!WebinarSysteemPermissions::user_is_team_member() ||
            !isset($request->webinar_id) ||
            !isset($request->enabled)) {
            wp_send_json_error(null, 401);
        }

        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);
        $webinar->set_questions_enabled($request->enabled == 1);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function enable_attendees_tab()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!WebinarSysteemPermissions::user_is_team_member() ||
            !isset($request->webinar_id) ||
            !isset($request->enabled)) {
            wp_send_json_error(null, 401);
        }

        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);
        $webinar->set_attendees_tab_enabled($request->enabled == 1);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function enable_hand_raising()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!WebinarSysteemPermissions::user_is_team_member() ||
            !isset($request->webinar_id) ||
            !isset($request->enabled)) {
            wp_send_json_error(null, 401);
        }

        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);
        $webinar->set_hand_raising_enabled($request->enabled == 1);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function enable_cta()
    {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!WebinarSysteemPermissions::user_is_team_member() ||
            !isset($request->webinar_id) ||
            !isset($request->enabled)) {
            wp_send_json_error(null, 401);
        }

        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);
        $webinar->set_cta_enabled($request->enabled == 1);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        wp_send_json_success();
    }

    public static function webinar_heartbeat() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        // get the webinar
        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);

        if (!$webinar) {
            return;
        }

        WebinarSysteemLog::log("Updating webinar last active time {$webinar->id}");

        // update the last active time for this webinar
        $webinar->update_last_active_time();

        wp_send_json_success();
    }

    public static function get_attendees()
    {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!isset($request->webinar_id) || !WebinarSysteemPermissions::user_is_team_member()) {
            wp_send_json_error(null, 401);
        }

        $attendees = array_map(
            function ($attendee) {
                $joined_at = isset($attendee->joined_at)
                    ? WebinarSysteemWebinar::get_now_in_webinar_timezone($attendee->webinar_id, strtotime($attendee->joined_at))
                    : '';

                return [
                    'id' => $attendee->id,
                    'name' => $attendee->name,
                    'email' => $attendee->email,
                    'attended' => $attendee->attended == '1',
                    'registered_at' => $attendee->time,
                    // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                    'joined_at' => ($joined_at != '' ? date('Y-m-d H:i:s', $joined_at) : ''),
                    'session' => $attendee->exact_time,
                    'seconds_attended' => (int) $attendee->seconds_attended,
                    'custom_fields' => json_decode($attendee->custom_fields),
                    'anonymous_email' => $attendee->anonymous_email == '1'
                ];
            },
            WebinarSysteemAttendees::get_webinar_attendees($request->webinar_id));

        self::return_data($attendees);
    }

    public static function delete_attendees() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!isset($request->webinar_id) || !WebinarSysteemPermissions::user_is_team_member() || !isset($request->attendee_ids)) {
            wp_send_json_error(null, 401);
        }

        $attendee_ids = explode(',', $request->attendee_ids);
        WebinarSysteemAttendees::delete_attendees($request->webinar_id, $attendee_ids);

        self::return_data([]);
    }

    public static function import_attendees()
    {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        $params_json = stripslashes($request->params);

        // decode the params
        $params = json_decode($params_json);

        if ($params == null || empty($params->webinar_id)) {
            wp_send_json_error(null, 400);
        }

        $webinar = WebinarSysteemWebinar::create_from_id($params->webinar_id);

        if ($webinar == null || ($webinar->is_recurring() && !isset($params->exact_time))) {
            wp_send_json_error(null, 401);
        }

        foreach ($params->attendees as $attendee) {
            $name = sanitize_text_field($attendee->name);
            $email = sanitize_email($attendee->email);

            WebinarSysteem::register_webinar_attendee(
                $webinar->id,
                $name,
                $email,
                $params->exact_time,
                null,
                null,
                true,
                true,
                !$params->send_confirmation_email,
                [
                    'login_current_browser' => false,
                    'force_paid_new_registration_email' => true,
                    'disable_resubscribe' => true
                ]
            );
        }

        wp_send_json_success([]);
    }

    public static function get_messages() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!isset($request->webinar_id) || !WebinarSysteemPermissions::user_is_team_member()) {
            wp_send_json_error(null, 401);
        }

        $messages = WebinarSysteemQuestions::get_messages($request->webinar_id);

        self::return_data($messages);
    }

    public static function delete_messages() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!isset($request->webinar_id) || !WebinarSysteemPermissions::user_is_team_member() || !isset($request->attendee_ids)) {
            wp_send_json_error(null, 401);
        }

        $attendee_ids = explode(',', $request->attendee_ids);
        WebinarSysteemQuestions::delete_messages($request->webinar_id, $attendee_ids);

        // update the cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        self::return_data([]);
    }

    public static function get_questions() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!isset($request->webinar_id) || !WebinarSysteemPermissions::user_is_team_member()) {
            wp_send_json_error(null, 401);
        }

        $messages = WebinarSysteemQuestions::get_questions($request->webinar_id);

        self::return_data($messages);
    }

    public static function delete_questions() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!isset($request->webinar_id) || !WebinarSysteemPermissions::user_is_team_member() || !isset($request->attendee_ids)) {
            wp_send_json_error(null, 401);
        }

        $attendee_ids = explode(',', $request->attendee_ids);
        WebinarSysteemQuestions::delete_questions($request->webinar_id, $attendee_ids);

        // update the cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        self::return_data([]);
    }

    public static function get_settings() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::can_manage_settings()) {
            wp_send_json_error(null, 401);
            return;
        }

        $settings = WebinarSysteemSettings::instance();

        self::return_data([
            'general' => [
            ],
            'webhooks' => [
                'new_registration' => $settings->get_new_registration_webhook(),
                'attended_webinar' => $settings->get_attended_webinar_webhook(),
            ],
            'zoomsdk' => [
                'clientId' => $settings->get_client_id_zoomsdk(),
                'clientSecret' => $settings->get_client_secret_zoomsdk(),
            ],
            'advanced' => [
                'use_theme_styles' => $settings->get_use_theme_styles(),
                'is_woocommerce_enabled' => $settings->is_woocommerce_enabled(),
                'woocommerce_add_to_cart_redirect_page' => $settings->get_woocommerce_add_to_cart_redirect_page(),
                'woocommerce_register_action' => $settings->get_woocommerce_register_action(),
                'webinar_slug' => $settings->get_webinar_slug(),
                'reduce_server_load' => $settings->get_reduce_server_load(),
                'enable_beta_updates' => $settings->get_enable_beta_updates(),
                'enable_optimized_zoom' => $settings->get_enable_optimized_zoom(),
                'enable_logging' => $settings->get_enable_logging(),
                'favicon_url' => $settings->get_favicon_url()
            ],
            'system_info' => [
                'report' => WPWS_System_Snapshot_Report::getInstance()->snapshot_data()
            ],
            'mailing_lists' => [
                'drip_api_key' => $settings->get_drip_api_key(),
                'mailchimp_api_key' => $settings->get_mailchimp_api_key(),
                'enormail_api_key' => $settings->get_enormail_api_key(),
                'getresponse_api_key' => $settings->get_getresponse_api_key(),
                'activecampaign_api_key' => $settings->get_activecampaign_api_key(),
                'activecampaign_api_url' => $settings->get_activecampaign_api_url(),
                'convertkit_api_key' => $settings->get_convertkit_api_key(),
                'is_aweber_connected' => WebinarsysteemMailingListIntegrations::is_aweber_connected(),
                'is_mautic_connected' => WebinarsysteemMailingListIntegrations::is_mautic_connected(),
                'mailrelay_host' => $settings->get_mailrelay_host(),
                'mailrelay_key' => $settings->get_mailrelay_key(),
                'mailerlite_key' => $settings->get_mailerlite_key(),
                'mailoctopus_key' => $settings->get_mailoctopus_key(),
            ],
            'permissions' => [
                'roles' => $settings->get_roles(),
                'role_permissions' => $settings->get_permissions(),
            ],
            'emails' => [
                'from_name' => $settings->get_email_from_name(),
                'from_email' => $settings->get_email_from_address(),
                'header_image' => $settings->get_email_header_image(),
                'image_height'=>$settings->get_email_image_height(),
                'footer_text' => $settings->get_email_footer_text(),
                'base_color' => $settings->get_email_base_color(),
                'background_color' => $settings->get_email_background_color(),
                'body_background_color' => $settings->get_email_body_background_color(),
                'body_text_color' => $settings->get_email_body_text_color(),
                'button_background_color' => $settings->get_email_button_background_color(),
                'button_text_color' => $settings->get_email_button_text_color(),
                'include_unsubscribe_links' => $settings->get_include_unsubscribe_links(),
                'resubscribe_attendees_on_register' => $settings->get_resubscribe_attendees_on_register(),
                'admin_email_address' => $settings->get_admin_email_address(),
                'types' => [
                    'new_registration' => $settings->get_email_template_options('newreg'),
                    'reg_confirmation' => $settings->get_email_template_options('regconfirm'),
                    'day_before' => $settings->get_email_template_options('24hrb4'),
                    'hour_before' => $settings->get_email_template_options('1hrb4'),
                    'starting' => $settings->get_email_template_options('wbnstarted'),
                    'replay' => $settings->get_email_template_options('wbnreplay'),
                    'order_complete' => $settings->get_email_template_options('order_complete'),
                    'double_optin' => $settings->get_email_template_options('double_optin')
                ]
            ],
            'translations' => $settings->get_translations(),
            'global_scripts' => '',
        ]);
    }

    public static function update_settings() {
        WebinarSysteemJS::check_ajax_nonce();
        if (!WebinarSysteemPermissions::can_manage_settings()) {
            wp_send_json_error(null, 401);
            return;
        }

        // save the settings
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;
        $params_json = stripslashes($request->params);
        // decode the params
        $params = json_decode($params_json);

        if (empty($params)) {
            wp_send_json_error(null, 401);
            return;
        }

        $settings = WebinarSysteemSettings::instance();

        // webhooks
        $webhooks = $params->webhooks;
        $settings->set_new_registration_webhook($webhooks->new_registration);
        $settings->set_attended_webinar_webhook($webhooks->attended_webinar);

        // ZoomSDK
        $zoomsdk = $params->zoomsdk;
        $settings->set_client_id_zoomsdk($zoomsdk->clientId);
        $settings->set_client_secret_zoomsdk($zoomsdk->clientSecret);

        // advanced
        $advanced = $params->advanced;
        $settings->set_use_theme_styles($advanced->use_theme_styles);
        $settings->set_woocommerce_is_enabled($advanced->is_woocommerce_enabled);
        $settings->set_woocommerce_add_to_cart_redirect_page($advanced->woocommerce_add_to_cart_redirect_page);
        $settings->set_woocommerce_register_action($advanced->woocommerce_register_action);
        $settings->set_webinar_slug($advanced->webinar_slug);
        $settings->set_reduce_server_load($advanced->reduce_server_load);
        $settings->set_enable_beta_updates($advanced->enable_beta_updates);
        $settings->set_enable_optimized_zoom($advanced->enable_optimized_zoom);
        $settings->set_enable_logging($advanced->enable_logging);
        $settings->set_favicon_url($advanced->favicon_url);

        // mailing lists
        $mailing_lists = $params->mailing_lists;
        $settings->set_drip_api_key($mailing_lists->drip_api_key);
        $settings->set_mailchimp_api_key($mailing_lists->mailchimp_api_key);
        $settings->set_enormail_api_key($mailing_lists->enormail_api_key);
        $settings->set_getresponse_api_key($mailing_lists->getresponse_api_key);
        $settings->set_activecampaign_api_key($mailing_lists->activecampaign_api_key);
        $settings->set_activecampaign_api_url($mailing_lists->activecampaign_api_url);
        $settings->set_convertkit_api_key($mailing_lists->convertkit_api_key);
        $settings->set_mailrelay_host($mailing_lists->mailrelay_host);
        $settings->set_mailrelay_key($mailing_lists->mailrelay_key);
        $settings->set_mailerlite_key($mailing_lists->mailerlite_key);
        $settings->set_mailoctopus_key($mailing_lists->mailoctopus_key);

        // update roles
        $permissions = $params->permissions;
        $settings->update_permissions($permissions->role_permissions);

        // emails
        $emails = $params->emails;
        $settings->set_email_from_name($emails->from_name);
        $settings->set_email_from_address($emails->from_email);
        $settings->set_email_header_image($emails->header_image);
        $settings->set_email_image_height($emails->image_height);
        $settings->set_email_footer_text($emails->footer_text);
        $settings->set_email_base_color($emails->base_color);
        $settings->set_email_background_color($emails->background_color);
        $settings->set_email_body_background_color($emails->body_background_color);
        $settings->set_email_body_text_color($emails->body_text_color);
        $settings->set_email_button_background_color($emails->button_background_color);
        $settings->set_email_button_text_color($emails->button_text_color);
        $settings->set_include_unsubscribe_links($emails->include_unsubscribe_links);
        $settings->set_resubscribe_attendees_on_register($emails->resubscribe_attendees_on_register);
        $settings->set_admin_email_address($emails->admin_email_address);

        // set email content
        $email_types = $emails->types;
        $settings->set_email_template_options('newreg', $email_types->new_registration);
        $settings->set_email_template_options('regconfirm', $email_types->reg_confirmation);
        $settings->set_email_template_options('24hrb4', $email_types->day_before);
        $settings->set_email_template_options('1hrb4', $email_types->hour_before);
        $settings->set_email_template_options('wbnstarted', $email_types->starting);
        $settings->set_email_template_options('wbnreplay', $email_types->replay);
        $settings->set_email_template_options('order_complete', $email_types->order_complete);
        $settings->set_email_template_options('double_optin', $email_types->double_optin);

        // update translations
        $settings->set_translations($params->translations);

        // update global scripts
        $settings->set_global_scripts($params->global_scripts);

        self::return_data([]);
    }

    public static function check_mailinglist_key() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::can_manage_settings()) {
            wp_send_json_error(null, 401);
            return;
        }

        // save the settings
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;
        $params_json = stripslashes($request->params);

        // decode the params
        $params = json_decode($params_json);

        if (empty($params)) {
            wp_send_json_error(null, 401);
            return;
        }

        $ok = false;

        switch ($params->type) {
            case 'drip':
                $ok = WebinarsysteemMailingListIntegrations::validate_drip_api_key($params->key);
                break;

            case 'enormail':
                $ok = WebinarsysteemMailingListIntegrations::validate_enormail_key($params->key);
                break;

            case 'getresponse':
                $ok = WebinarsysteemMailingListIntegrations::validate_getresponse_key($params->key);
                break;

            case 'activecampaign':
                $ok = WebinarsysteemMailingListIntegrations::validate_activecampaign_api_key($params->key, $params->url);
                break;

            case 'convertkit':
                $ok = WebinarsysteemMailingListIntegrations::validate_convertkit_api_key($params->key);
                break;

            case 'mailchimp':
                $ok = WebinarsysteemMailingListIntegrations::validate_mailchimp_api_key($params->key);
                break;

            case 'mailrelay':
                $ok = WebinarsysteemMailingListIntegrations::validate_mailrelay_api_key($params->key, $params->host);
                break;

            case 'mailerlite':
                $ok = WebinarsysteemMailingListIntegrations::validate_mailerlite_api_key($params->key);
                break;

            case 'mailoctopus':
                $ok = WebinarsysteemMailingListIntegrations::validate_mailoctopus_api_key($params->key) != null;
                break;
        }

        self::return_data([ 'ok' => $ok ]);
    }

    public static function send_email_preview() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (
            !WebinarSysteemPermissions::can_manage_settings() ||
            !isset($request->subject) ||
            !isset($request->content) ||
            !isset($request->email)) {
            wp_send_json_error(null, 401);
            return;
        }

        $mail = new WebinarSysteemEmails();
        $mail->send_preview(
            $request->email,
            stripslashes($request->subject),
            stripslashes($request->content)
        );

        self::return_data([]);
    }

    public static function get_admin_notices() {
        WebinarSysteemJS::check_ajax_nonce();

        if (
            !WebinarSysteemPermissions::can_manage_settings()) {
            wp_send_json_error(null, 401);
            return;
        }

        $notices = [];
        $notices[] = (object) ['id' => 'ui-issues-info'];
        // Only show the survey notice after 10 days
        $settings = WebinarSysteemSettings::instance();
        if ($settings->days_since_install() >= 10) {
            $notices[] = (object) ['id' => 'survey-free-aug-2022'];
        }

        self::return_data([
            'notices' => $notices,
        ]);
    }

    public static function get_default_email_template_options() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (
            !WebinarSysteemPermissions::can_create_webinars() ||
            !isset($request->type)) {
            wp_send_json_error(null, 401);
            return;
        }

        $settings = WebinarSysteemSettings::instance();

        $email_defaults = $settings->get_default_email_templates()[$request->type];
        self::return_data([
            'enabled' => true,
            'subject' => $email_defaults->subject,
            'content' => apply_filters('meta_content', $email_defaults->content)
        ]);
    }

    public static function subscribe_to_drip_course() {
        $current_user = wp_get_current_user();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        $subscribe = ($_POST['subscribe'] == '1') ? true : false;

        $settings = WebinarSysteemSettings::instance();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (!$settings->should_show_course_invite() || !wp_verify_nonce($_POST['nonce'], 'drip pointer subscribe')) {
            self::return_data(array(
                'success' => false,
                'error' => 'nonce failed'
            ));
            return false;
        }

        $settings->set_show_course_invite(false);

        if ($subscribe) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            if (!filter_var( $_POST['email'], FILTER_VALIDATE_EMAIL)) {
                self::return_data(array(
                    'success' => false,
                    'error' => 'Email is invalid'
                ));
                return false;
            }

            $data = array();
            $data['name'] = '';

            foreach (array('first_name', 'display_name', 'user_login', 'username') as $k) {
                if (empty($current_user->{$k}))
                    continue;

                $data['name'] = $current_user->{$k};
                break;
            }
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $data['email'] = $_POST['email'];
            $data['website'] = get_bloginfo('url');
            $data['gmt_offset'] = get_option('gmt_offset');

            wp_remote_post('https://www.getdrip.com/forms/799524708/submissions', [
                'body' => [
                    'fields[name]' => $data['name'],
                    'fields[email]' => $data['email'],
                    'fields[website]' => $data['website'],
                    'fields[gmt_offset]' => $data['gmt_offset']
                ]
            ]);
        }

        self::return_data([
            'success' => true
        ]);

        return true;
    }

    public static function get_webinar_recordings() {
        WebinarSysteemJS::check_ajax_nonce();
        if (!WebinarSysteemPermissions::can_create_webinars()) {
            wp_send_json_error(null, 401);
            return;
        }

        $recordings = WebinarSysteemMediaServer::get_webinar_recordings();
        self::return_data($recordings);
    }

    public static function delete_webinar_recording() {
        WebinarSysteemJS::check_ajax_nonce();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (
            !WebinarSysteemPermissions::can_create_webinars() ||
            !isset($request->recording_id)) {
            wp_send_json_error(null, 401);
            return;
        }

        $recordings = WebinarSysteemMediaServer::delete_webinar_recording($request->recording_id);
        self::return_data($recordings);
    }

    public static function resend_attendee_confirmation_email() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (
            !WebinarSysteemPermissions::can_create_webinars() ||
            empty($request->webinar_id) ||
            empty($request->template) ||
            empty($request->attendee_id)) {
            wp_send_json_error(null, 400);
        }

        // get the attendee
        $attendee = WebinarSysteemAttendees::get_attendee_by_id($request->attendee_id);
        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);

        if ($attendee == null || $attendee->webinar_id != $request->webinar_id || !$webinar) {
            wp_send_json_error(null, 400);
        }

        if ($attendee->anonymous_email) {
            return;
        }

        // send the email
        $mail = new WebinarSysteemEmails();

        switch ($request->template) {
            case 'order-complete':
                $mail->send_mail_to_reader_on_wc_order_complete($attendee, true);
                break;

            case 'new-registration':
                $mail->send_new_registration_email($attendee, true);
                break;
        }

        self::return_data([]);
    }

    public static function get_polls() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::can_create_webinars()) {
            wp_send_json_error(null, 400);
        }

        self::return_data(WebinarSysteemPolls::list());
    }

    public static function create_poll() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::can_create_webinars()) {
            wp_send_json_error(null, 400);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        // decode the params
        $config = json_decode(stripslashes($request->config));

        $poll_id = WebinarSysteemPolls::create_poll(
            sanitize_text_field($request->name),
            $config
        );

        self::return_data([
            'id' => $poll_id
        ]);
    }

    public static function update_poll() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::can_create_webinars()) {
            wp_send_json_error(null, 400);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        if (!isset($request->id) || !isset($request->name) || !isset($request->config)) {
            wp_send_json_error(null, 422);
        }

        // decode the params
        $config = json_decode(stripslashes($request->config));

        WebinarSysteemPolls::update_poll(
            sanitize_text_field($request->id),
            sanitize_text_field($request->name),
            $config
        );

        // Return the updated poll list
        self::return_data(WebinarSysteemPolls::list());
    }

    public static function poll_analytics_by_poll() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::can_create_webinars()) {
            wp_send_json_error(null, 400);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        $analytics = WebinarSysteemPolls::get_analytics_by_poll(
            sanitize_text_field($request->poll_id)
        );

        // Return the updated poll list
        self::return_data($analytics);
    }

    public static function poll_analytics_by_webinar() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::can_create_webinars()) {
            wp_send_json_error(null, 400);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        $analytics = WebinarSysteemPolls::get_analytics_by_webinar(
            sanitize_text_field($request->webinar_id)
        );

        // Return the updated poll list
        self::return_data($analytics);
    }

    public static function delete_poll() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::can_create_webinars()) {
            wp_send_json_error(null, 400);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;

        WebinarSysteemPolls::delete(
            sanitize_text_field($request->id)
        );

        self::return_data();
    }

    public static function submit_poll() {
        WebinarSysteemJS::check_ajax_nonce();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;
        $attendee = WebinarSysteemAttendees::get_attendee($request->webinar_id);

        if (empty($request->poll_id) ||
            empty($request->webinar_id) ||
            $attendee == null) {
            wp_send_json_error(null, 400);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $json = stripslashes($_POST["answers"]);

        // decode the params
        $answers = json_decode($json);

        WebinarSysteemPolls::submit(
            $request->poll_id,
            $request->webinar_id,
            $attendee->id,
            $answers
        );

        self::return_data();
    }

    public static function launch_poll() {
        WebinarSysteemJS::check_ajax_nonce();

        if (!WebinarSysteemPermissions::can_create_webinars()) {
            wp_send_json_error(null, 400);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;
        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);
        $webinar->set_enabled_poll_id($request->poll_id);

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        self::return_data();
    }

    public static function close_poll() {
        WebinarSysteemJS::check_ajax_nonce();
        
        if (!WebinarSysteemPermissions::can_create_webinars()) {
            wp_send_json_error(null, 400);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $request = (object)$_POST;
        $webinar = WebinarSysteemWebinar::create_from_id($request->webinar_id);
        $webinar->clear_enabled_poll_id();

        // update the webinar cache
        WebinarSysteemWebinarCache::write($request->webinar_id);

        self::return_data();
    }
}
