<?php

class WebinarPress
{

    /**
     * @return array|null
     */
    public static function get_webinars()
    {
        return WebinarSysteemRegistrationWidget::get_webinars();
    }

    /**
     * @param $id
     * @return array
     */
    public static function get_upcoming_sessions($id)
    {
        return WebinarSysteemSessions::get_upcoming_sessions_for_webinar($id);
    }

    /**
     * @param  $attendee
     * @return object|null
     */
    public static function register_attendee($attendee)
    {
        if (!isset($attendee['webinar_id'])
            || !isset($attendee['name'])
            || !isset($attendee['email'])) {
            return null;
        }
        $defaults = [
            'exact_time' => null,
            'day' => null,
            'time' => null,
            'disable_notifications' => false,
            'disable_admin_email' => false,
            'disable_attendee_email' => false,
            'args' =>
                [
                    'login_current_browser' => true,
                    'custom_fields' => null,
                    'force_paid_new_registration_email' => false,
                    'disable_resubscribe' => false
                ]
        ];

        $attendeeWithDefaults = array_merge($attendee, $defaults);

        return WebinarSysteem::register_webinar_attendee
        (
            $attendeeWithDefaults['webinar_id'],
            $attendeeWithDefaults['name'],
            $attendeeWithDefaults['email'],
            $attendeeWithDefaults['exact_time'],
            $attendeeWithDefaults['day'],
            $attendeeWithDefaults['time'],
            $attendeeWithDefaults['disable_notifications'],
            $attendeeWithDefaults['disable_admin_email'],
            $attendeeWithDefaults['disable_attendee_email'],
            $attendeeWithDefaults['args']
        );
    }
}