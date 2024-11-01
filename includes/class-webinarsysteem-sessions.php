<?php

class WebinarSysteemSessions {
    protected static $days_to_index = [
        'sun' =>0,
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6];

    protected static $index_to_days = [
        'sun',
        'mon',
        'tue',
        'wed',
        'thu',
        'fri',
        'sat'];

    /**
     * @param DateTime $today
     * @param array $times
     * @param array $days_map
     * @return object
     */

    protected static function try_tomorrow($today, $times, $days_map) {
        $today->add(DateInterval::createFromDateString('1 days'));
        $today->setTime(0, 0, 0);
        return self::get_next_session_from_date($today, $times, $days_map);
    }

    /**
     * @param DateTime $current_time
     * @param array $times
     * @param array $days_map
     * @return object
     */

    protected static function get_next_session_from_date($current_time, $times, $days_map) {
        $day = (int) $current_time->format('w');

        // if we don't have any sessions today, try again tomorrow
        if (!array_key_exists($day, $days_map)) {
            return self::try_tomorrow($current_time, $times, $days_map);
        }

        // go through each time slot and see if we are past
        for ($index = 0; $index < count($times); $index += 1) {
            $time = $times[$index];
            $hour_mins = explode(':', $time);

            $in_webinar_time = clone $current_time;
            $in_webinar_time->setTime((int) $hour_mins[0], (int) $hour_mins[1], 0);

            if ($current_time <= $in_webinar_time) {
                return (object) [
                    'date' => $in_webinar_time->getTimestamp(),
                    'day' => self::$index_to_days[$day],
                    'time' => $time,
                    'new_current_time' => $in_webinar_time
                ];
            }
        }

        return self::try_tomorrow($current_time, $times, $days_map);
    }

    /**
     * @param int $webinar_id
     * @param int $max_sessions
     * @param int $days_to_offset
     * @param bool $include_active_webinars
     * @return array
     */

    public static function get_upcoming_sessions_for_webinar(
        $webinar_id,
        $max_sessions = 10,
        $days_to_offset = 0,
        $include_active_webinars = false
    ) {
        try {
            $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);

            $current_date = new DateTime('@'.$webinar->get_now_in_timezone());

            // add the days offset
            $current_date->add(DateInterval::createFromDateString("$days_to_offset days"));

            // add the automated offset
            $seconds_to_offset = $webinar->get_automated_offset();
            if (is_int($seconds_to_offset) && $seconds_to_offset > 0) {
                $current_date->add(DateInterval::createFromDateString("$seconds_to_offset seconds"));
            }

            // If we include active webinars then subtract the webinar duration. This might give
            // unexpected results with JIT webinars because there could be multiple sessions started
            // in the webinar duration.

            if ($include_active_webinars) {
                $duration = $webinar->get_duration();
                $current_date->sub(DateInterval::createFromDateString("$duration seconds"));
            }

            if (!$webinar->is_recurring()) {
                return [(object)[
                    'date' => $webinar->get_one_time_datetime(),
                    'day' => null,
                    'time' => null
                ]];
            }

            if ($webinar->is_right_now()) {
                return [(object)[
                    'date' => $current_date->getTimestamp(),
                    'day' => 'rightnow',
                    'time' => 'rightnow',
                ]];
            }

            $settings = $webinar->get_timeslot_settings();

            $days = $settings['days'];
            $times = $webinar->get_recurring_times();

            if (count($days) === 0 || count($times) === 0) {
                return [];
            }

            // how do we get the next time from now?
            $days_map = [];
            foreach ($days as &$day) {
                $day_index = self::$days_to_index[$day];
                $days_map[$day_index] = true;
            }

            $sessions = [];
            $start_date = clone $current_date;

            while (count($sessions) < $max_sessions || $max_sessions == 0) {
                $session = self::get_next_session_from_date($current_date, $times, $days_map);

                if ($session == null) {
                    break;
                }

                // max_sessions == 0 means add one week so more than a week has passed we break
                if ($max_sessions == 0 &&
                    $current_date->diff($start_date)->days >= 6) {
                    break;
                }

                $current_date = $session->new_current_time;
                $current_date->add(DateInterval::createFromDateString('1 seconds'));

                $sessions[] = $session;
            }

            return $sessions;
        } catch (Exception $e) {
            WebinarSysteemLog::log($e->getMessage().PHP_EOL.$e->getTraceAsString());
            return [];
        }
    }
}