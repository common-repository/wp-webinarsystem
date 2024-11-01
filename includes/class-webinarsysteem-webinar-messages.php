<?php

class WebinarSysteemWebinarMessages
{
    /**
     * Process a pending webinar message
     * @param WebinarSysteemWebinar $webinar
     * @param object $message
     **/

    private static function process_message($webinar, $message) {
        global $wpdb;
        $data = $message->data;
        $attendee = isset($message->attendee)
            ? $message->attendee
            : null;

        switch ($message->type) {
            case 'send-chat':
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                $timestamp = date('Y-m-d H:i:s', $webinar->get_now_in_timezone($data->createdAt / 1000));

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->replace(
                    WebinarSysteemTables::get_chats(), [
                        'id' => $data->id,
                        'webinar_id' => $webinar->id,
                        'admin' => $data->isTeamMember,
                        'private' => false,
                        'attendee_id' => $data->attendeeId,
                        'content' => str_replace('\\', '', sanitize_textarea_field($data->content)),
                        'timestamp' => $timestamp,
                    ]
                );
                break;

            case 'delete-chat':
                // team members can delete anyone's chats
                if ($attendee->isTeamMember) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                    $wpdb->delete(
                        WebinarSysteemTables::get_chats(),
                        ['id' => $data->id],
                        ['%d']
                    );
                    return;
                }

                // make sure that this attendee sent the message or they are a team member
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->delete(
                    WebinarSysteemTables::get_chats(),
                    ['id' => $data->id, 'attendee_id' => $attendee->id],
                    ['%d', '%d']
                );
                break;

            case 'send-question':
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                $timestamp = date('Y-m-d H:i:s', $webinar->get_now_in_timezone($data->createdAt / 1000));

                $asking_attendee = WebinarSysteemAttendees::get_attendee_by_id($data->askedBy);

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->replace(
                    WebinarSysteemTables::get_questions(), [
                        'id' => $data->id,
                        'name' => sanitize_text_field($asking_attendee->name),
                        'email' => sanitize_text_field($asking_attendee->email),
                        'question' => str_replace('\\', '', sanitize_textarea_field($data->question)),
                        'time' => $timestamp,
                        'webinar_id' => $webinar->id,
                        'asked_by' => $asking_attendee->id,
                        'is_private' => $data->isPrivate
                    ]
                );

                // email to host if setup
                (new WebinarSysteemEmails())
                    ->send_question_to_host(
                        $webinar->id,
                        $asking_attendee->name,
                        $asking_attendee->email,
                        $data->question);
                break;

            case 'delete-question':
                // team members can delete anyone's chats
                if ($attendee->isTeamMember) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                    $wpdb->delete(
                        WebinarSysteemTables::get_questions(),
                        ['id' => $data->id],
                        ['%d']
                    );
                    return;
                }

                // make sure that this attendee sent the message or they are a team member
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->delete(
                    WebinarSysteemTables::get_questions(),
                    ['id' => $data->id, 'asked_by' => $attendee->id],
                    ['%d', '%d']
                );
                break;


            case 'set-webinar-answer':
                // only team members can set answers
                if (!$attendee->isTeamMember) {
                    return;
                }

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                 $wpdb->update(
                    WebinarSysteemTables::get_questions(), [
                    'answered_at' => current_time('mysql', 1),
                    'answered_by' => $attendee->id,
                    'answer' => str_replace('\\', '', sanitize_textarea_field($data->answer)),
                ], [
                    'id' => $data->questionId,
                    'webinar_id' => $webinar->id
                ]);
                break;

            case 'clear-webinar-answer':
                // only team members can set answers
                if (!$attendee->isTeamMember) {
                    return;
                }

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->update(
                    WebinarSysteemTables::get_questions(), [
                    'answered_at' => null,
                    'answered_by' => null,
                    'answer' => null,
                ], [
                    'id' => $data->questionId,
                    'webinar_id' => $webinar->id
                ]);
                break;

            case 'set-question-visibility':
                // only team members can set answers
                if (!$attendee->isTeamMember) {
                    return;
                }

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->update(
                    WebinarSysteemTables::get_questions(), [
                    'is_private' => $data->isPrivate
                ], [
                    'id' => $data->questionId,
                    'webinar_id' => $webinar->id
                ]);
                break;

            case 'enable-chat':
                // only team members can set answers
                if (!$attendee->isTeamMember) {
                    return;
                }
                $webinar->set_chat_enabled($data->enabled);
                break;

            case 'enable-questions':
                // only team members can set answers
                if (!$attendee->isTeamMember) {
                    return;
                }
                $webinar->set_questions_enabled($data->enabled);
                break;

            case 'enable-attendees-tab':
                // only team members can set answers
                if (!$attendee->isTeamMember) {
                    return;
                }
                $webinar->set_attendees_tab_enabled($data->enabled);
                break;

            case 'enable-hand-raising':
                // only team members can set answers
                if (!$attendee->isTeamMember) {
                    return;
                }
                $webinar->set_hand_raising_enabled($data->enabled);
                break;

            case 'enable-cta':
                // only team members can set answers
                if (!$attendee->isTeamMember) {
                    return;
                }
                $webinar->set_cta_enabled($data->enabled);
                break;

            case 'set-webinar-status':
                // only team members can set answers
                if (!$attendee->isTeamMember) {
                    return;
                }
                $webinar->set_status($data->status);
                $webinar->set_went_live_at_timestamp($data->wentLiveAt / 1000);
                break;

            case 'update-last-seen':
                // From v2.22.1 updates are handled in js so we can check that
                // the attendee still has a valid session
                break;

            case 'set-media-source':
                if (!$attendee->isTeamMember) {
                    return;
                }
                $webinar->set_live_media_type($data->type);
                $webinar->set_live_media_url($data->url);
                break;

            case 'launch-poll':
                if (!$attendee->isTeamMember) {
                    return;
                }
                $webinar->set_enabled_poll_id($data->poll_id);
                break;

            case 'close-poll':
                if (!$attendee->isTeamMember) {
                    return;
                }
                $webinar->clear_enabled_poll_id();
                break;

            default:
                break;
        }
    }

    /**
     * Process an array of pending webinar messages
     * @param WebinarSysteemWebinar $webinar
     * @param array $messages
     **/

    static function process_messages($webinar, $messages) {
        WebinarSysteemLog::log("Processing ".count($messages)." messages for {$webinar->id}");

        foreach ($messages as $message) {
            self::process_message($webinar, $message);
        }

        // update the cache
        WebinarSysteemWebinarCache::write($webinar->id);
    }
}
