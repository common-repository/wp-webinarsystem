<?php

class WebinarSysteemPolls {
    public static function load($poll_id) {
        $data = get_post_meta($poll_id, '_wpws_meta', true);

        if (!$data) {
            return null;
        }

        return unserialize($data);
    }

    public static function list() {
        global $wpdb;

        $poll_table = WebinarSysteemTables::get_polls();
        $vote_table = WebinarSysteemTables::get_poll_votes();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $polls = $wpdb->get_results("SELECT p.`id`, p.`name`, p.`config`, COUNT(DISTINCT(v.attendee_id)) responses FROM {$poll_table} p LEFT JOIN {$vote_table} v ON p.id = v.poll_id GROUP BY p.id");

        return array_map(function ($row) {
            return (object) [
                'id' => (int) $row->id,
                'name' => $row->name,
                'config' => unserialize($row->config),
                'responses' => (int) $row->responses
            ];
        }, $polls);
    }

    public static function get_analytics_by_poll($poll_id)
    {
        global $wpdb;

        $vote_table = WebinarSysteemTables::get_poll_votes();

        $votes = array_map(function ($row) {
            return (object) [
                'poll_id' => (int) $row->poll_id,
                'webinar_id' => (int) $row->webinar_id,
                'answer_id' => $row->answer_id,
                'question_id' => $row->question_id,
                'votes' => (int) $row->votes
            ];
        }, 
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare("SELECT poll_id, webinar_id, answer_id, question_id, COUNT(id) votes FROM {$vote_table} WHERE poll_id=%d GROUP BY poll_id, webinar_id, answer_id", $poll_id)
        ));

        $webinars = array_map(function ($row) {
            return (object) [
                'id' => (int) $row->webinar_id,
                'name' => get_the_title($row->webinar_id),
                'votes' => (int) $row->votes
            ];
        }, 
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare("SELECT webinar_id, COUNT(id) votes FROM {$vote_table} WHERE poll_id=%d GROUP BY webinar_id", $poll_id)
        ));

        return [
            'votes' => $votes,
            'webinars' => $webinars
        ];
    }

    public static function get_analytics_by_webinar($webinar_id) {
        global $wpdb;

        $vote_table = WebinarSysteemTables::get_poll_votes();

        $votes = array_map(function ($row) {
            return (object) [
                'poll_id' => (int) $row->poll_id,
                'webinar_id' => (int) $row->webinar_id,
                'answer_id' => $row->answer_id,
                'question_id' => $row->question_id,
                'votes' => (int) $row->votes
            ];
        }, 
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare(" SELECT poll_id, webinar_id, answer_id, question_id, COUNT(id) votes FROM {$vote_table} WHERE webinar_id=%d GROUP BY poll_id, webinar_id, answer_id ", $webinar_id)
        ));

        $polls = array_map(function ($row) {
            return (object) [
                'id' => (int) $row->poll_id,
                'votes' => (int) $row->votes
            ];
        }, 
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare("SELECT poll_id, COUNT(DISTINCT (attendee_id)) votes FROM {$vote_table} WHERE webinar_id=%d GROUP BY poll_id ", $webinar_id)
        ));

        return [
            'votes' => $votes,
            'polls' => $polls
        ];
    }

    public static function delete($poll_id) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete(
            WebinarSysteemTables::get_poll_votes(), [
                'poll_id' => (int)$poll_id
            ]
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete(
            WebinarSysteemTables::get_polls(), [
                'id' => (int)$poll_id
            ]
        );
    }

    public static function create_poll($name, $config) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->insert(
            WebinarSysteemTables::get_polls(),
            [
                'name' => $name,
                'config' => serialize($config)
            ],
            ['%s', '%s']
        );

        return $wpdb->insert_id;
    }

    public static function update_poll($id, $name, $config) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            WebinarSysteemTables::get_polls(), [
                'name' => $name,
                'config' => serialize($config)
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
    }

    public static function submit($poll_id, $webinar_id, $attendee_id, $questions) {
        global $wpdb;

        // first delete all current answers for this attendee
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete(
            WebinarSysteemTables::get_poll_votes(), [
                'poll_id' => (int)$poll_id,
                'webinar_id' => (int)$webinar_id,
                'attendee_id' => (int)$attendee_id
            ]
        );

        // insert the new ones
        $db = WebinarSysteemDB::instance();

        foreach ($questions as $question_id => $answers) {
            $db->insert_multiple(
                WebinarSysteemTables::get_poll_votes(),
                array_map(function ($answer_id) use (
                    $poll_id, $webinar_id, $attendee_id, $question_id
                ) {
                    return [
                        'poll_id' => $poll_id,
                        'webinar_id' => $webinar_id,
                        'attendee_id' => $attendee_id,
                        'question_id' => $question_id,
                        'answer_id' => $answer_id
                    ];
                }, $answers),
                ['%d','%d','%s','%s','%s','%s']
            );
        }
    }
}
