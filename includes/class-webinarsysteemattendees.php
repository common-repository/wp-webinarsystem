<?php

class WebinarSysteemAttendees
{
    public static function get_webinar_attendees($webinar_id)
    {
        global $wpdb;
        $table = WSWEB_DB_TABLE_PREFIX . 'subscribers';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE webinar_id=%d ORDER BY id DESC", $webinar_id));
    }

    public static function get_by_webinar_id_and_key($webinar_id, $key)
    {
        global $wpdb;
        $table = WSWEB_DB_TABLE_PREFIX . 'subscribers';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE webinar_id = %d AND random_key = %s", $webinar_id, $key));
    }

    public static function get_by_session($webinar_id, $day, $time)
    {
        global $wpdb;
        $table = WSWEB_DB_TABLE_PREFIX . 'subscribers';

        $query = "SELECT *
            FROM $table
            WHERE webinar_id=%d AND watch_day=%s AND watch_time=%s
            ORDER BY id DESC";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_results($wpdb->prepare($query,
                $webinar_id,
                $day,
                $time
            )
        );
    }

    public static function get_attendee_by_id($id)
    {
        global $wpdb;
        $table = WSWEB_DB_TABLE_PREFIX . 'subscribers';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d LIMIT 1", $id));
    }

    public static function add_or_update_attendee($array, $format = array())
    {
        global $wpdb;

        $attendee_data = self::get_attendee_by_email($array['email'], $array['webinar_id']);

        if (!empty($attendee_data)) {
            self::modify_attendee($attendee_data->id, $array, $format);

            // delete previous notifications
            self::delete_notifications($attendee_data->webinar_id, [$attendee_data->id]);
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert(WSWEB_DB_TABLE_PREFIX . "subscribers", $array, $format);
        }
    }

    public static function get_attendee_by_email($email, $webinar_id)
    {
        global $wpdb;
        $table = WSWEB_DB_TABLE_PREFIX . 'subscribers';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE email=%s AND webinar_id=%d LIMIT 1", $email, $webinar_id));
    }

    public static function modify_attendee($row_id, $columns, $format = array('%d'))
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->update(WSWEB_DB_TABLE_PREFIX . 'subscribers', $columns, array('id' => $row_id), $format, array('%d'));
    }

    public static function update_joined_at($attendee_id) {
        // set the joined at date/time
        self::modify_attendee($attendee_id, ['joined_at' => gmdate('Y-m-d H:i:s')], ['%s']);
    }

    public static function update_last_seen($attendee_id, $seconds_attended = null) {

        // get the attendee
        $attendee = self::get_attendee_by_id($attendee_id);

        if ($attendee == null) {
            WebinarSysteemLog::log("Attendee not found, exiting");
            return false;
        }

        $attended = $attendee->attended;

        // if we have seconds attended (the page is live) and we have not yet
        // attended then fire events and set to attended
        if ($attendee->attended == 0 && $seconds_attended > 0) {
            WebinarSysteemLog::log("Firing attended flags for {$attendee_id}");
            WebinarSysteemWebHooks::send_attended($attendee->webinar_id, $attendee);
            WebinarSysteemActions::fire_attended($attendee->webinar_id, $attendee);

            // set the joined at date/time
            self::update_joined_at($attendee_id);

            $attended = 1;
        }

        // update the attendee
        if ($seconds_attended != null) {
            return self::modify_attendee(
                $attendee_id,
                [
                    'attended' => $attended,
                    'last_seen' => gmdate('Y-m-d H:i:s'),
                    'seconds_attended' => $seconds_attended
                ],
                ['%d', '%s', '%d']);
        }

        return self::modify_attendee(
            $attendee_id,
            [
                'attended' => $attended,
                'last_seen' => gmdate('Y-m-d H:i:s')
            ],
            ['%d', '%s']);
    }

    public static function update_last_seen_multiple($attendee_ids, $seconds_to_add = 0) {
        WebinarSysteemLog::log("Got multiple attendee last seen update: ".wp_json_encode($attendee_ids));
        foreach ($attendee_ids as $attendee_id) {
            self::update_last_seen($attendee_id, $seconds_to_add);
        };
    }

    public static function get_attendee($webinar_id)
    {
        $data = WebinarSysteemWebinarSession::get_session($webinar_id);

        if (!isset($data->email) || !isset($data->key)) {
            return array();
        }

        global $wpdb;
        $table = WSWEB_DB_TABLE_PREFIX . 'subscribers';

        $query = "
          SELECT *
          FROM {$table}
          WHERE webinar_id=%d AND
            email=%s AND
            random_key=%s
          LIMIT 1
        ";

        $attendee = null;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_row($wpdb->prepare($query, $webinar_id, $data->email, $data->key));
    }

    public static function delete_attendees($webinar_id, $attendee_ids)
    {
        global $wpdb;

        foreach ($attendee_ids as $attendee_id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->delete(
                WebinarSysteemTables::get_subscribers(), [
                    'webinar_id' => $webinar_id,
                    'id' => (int)$attendee_id
                ]
            );
        }

        // delete notifications
        self::delete_notifications($webinar_id, $attendee_ids);
    }

    public static function delete_notifications($webinar_id, $attendee_ids)
    {
        global $wpdb;

        foreach ($attendee_ids as $attendee_id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->delete(
                WebinarSysteemTables::get_notifications(), [
                    'attendee_id' => (int)$attendee_id
                ]
            );
        }
    }

    public static function update_attendee($row_id, $columns, $format = ['%d'])
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->update(
            WSWEB_DB_TABLE_PREFIX . 'subscribers',
            $columns,
            ['id' => $row_id], $format, ['%d']);
    }

    public static function set_attendee_is_not_newly_registered($attendee) {
        self::update_attendee($attendee->id, ['newly_registered' => '0'], ['%d']);
    }
}
