<?php

class WebinarSysteemQuestions extends WebinarSysteem {
    public static function get_questions($webinar_id) {
        global $wpdb;
        $table = WebinarSysteemTables::get_questions();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE webinar_id = %d ORDER BY id ASC", $webinar_id));

        return array_map(function ($question) {
            $attendees = new WebinarSysteemAttendees();

            return (object) [
                'id' => (int) $question->id,
                'asked_by' => $attendees->get_attendee_by_id($question->asked_by),
                'question' => $question->question,
                'answered_by' => $question->answered_by != null
                    ? $attendees->get_attendee_by_id($question->answered_by)
                    : null,
                'answer' => $question->answer,
                'created_at' => $question->time,
                'is_private' => $question->is_private == '1'
            ];
        }, $questions);
    }
    
    public static function get_messages($webinar_id) {
        global $wpdb;
        $table = WebinarSysteemTables::get_chats();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $chats = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE webinar_id = %d AND private='0' ORDER BY id ASC", $webinar_id));

        return array_map(function ($message) {
            $attendees = new WebinarSysteemAttendees();

            return (object) [
                'id' => (int) $message->id,
                'attendee_id' => $message->attendee_id,
                'attendee' => $attendees->get_attendee_by_id($message->attendee_id),
                'content' => $message->content,
                'created_at' => $message->timestamp
            ];
        }, $chats);
    }

    static function delete_from_table_with_ids($table, $webinar_id, $ids) {
        global $wpdb;

        foreach ($ids as $attendee_id) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete(
                $table, [
                    'webinar_id' => $webinar_id,
                    'id' => (int)$attendee_id
                ]
            );
        }
    }

    public static function delete_messages($webinar_id, $ids) {
        self::delete_from_table_with_ids(WebinarSysteemTables::get_chats(), $webinar_id, $ids);
    }

    public static function delete_questions($webinar_id, $ids) {
        self::delete_from_table_with_ids(WebinarSysteemTables::get_questions(), $webinar_id, $ids);
    }
}
