<?php

class WebinarSysteemWebinarCache {
    public static function write_v2($webinar_id) {
        if (!$webinar_id) {
            return;
        }

        $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);
        $page = $webinar->get_live_page_prefix();

        $result = [
            'general' => [
                'is_manual' => $webinar->is_manual(),
                'status' => $webinar->get_status(),
                'went_live_at' => $webinar->get_went_live_at_now(),
                'exit_url' => $webinar->get_exit_url(),
                'after_webinar_action' => $webinar->get_after_webinar_action(),
                'after_webinar_redirect_url' => $webinar->get_after_webinar_redirect_url(),
            ],
            'webinar_id' => (int)$webinar_id,
            'attendees' => WebinarSysteemAjax::get_online_attendees($webinar_id)['attendees'],
            'chats' => WebinarSysteemAjax::get_webinar_chats($webinar_id),
            'questions' => WebinarSysteemAjax::get_webinar_questions($webinar_id),
            'settings' => [
                'theme' => [
                    'banner_background_color' => WebinarSysteemHelperFunctions::add_hash_to_color(
                        $webinar->get_field($page.'banner_background_color'))
                ],
                'media' => [
                    'type' => $webinar->get_field($page.'vidurl_type'),
                    'source' => $webinar->get_field($page.'vidurl'),
                    'autoplay' => $webinar->get_field($page.'video_auto_play_yn') == 'yes',
                    'show_controls' => $webinar->get_field($page.'video_controls_yn') == 'yes',
                    'allow_fullscreen' => $webinar->get_field($page.'fullscreen_control') == 'yes',
                    'show_big_play_button' => $webinar->get_field($page.'bigplaybtn_yn') == 'yes',
                    'simulate_live_video' => $webinar->get_field($page.'simulate_video_yn') == 'yes',
                ],
                'chat' => [
                    'enabled' => $webinar->get_field($page.'show_chatbox') == 'yes',
                ],
                'questions' => [
                    'enabled' => $webinar->get_field($page.'askq_yn') == 'yes',
                    'visibility' => $webinar->get_field($page.'askq_question_visibility'),
                ],
                'attendees' => [
                    'enabled' => $webinar->get_field($page.'show_attendees_yn') == 'yes',
                ],
                'call_to_action' => [
                    'is_manual' => $webinar->get_field($page.'call_action') == 'manual',
                    'enabled' => $webinar->get_field($page.'manual_show_cta') == 'yes',
                    'show_after_mins' => (int) $webinar->get_field($page.'cta_show_after'),
                    'countdown_mins' => $webinar->get_field($page.'cta_countdown'),
                    'headline' => $webinar->get_field($page.'cta_headline'),
                    'subheading' => $webinar->get_field($page.'cta_subheading'),
                    'type' => $webinar->get_field($page.'call_action_ctatype') === 'button'
                        ? 'button' : 'html',
                    'button_text' => $webinar->get_field($page.'ctabtn_txt'),
                    'button_url' => $webinar->get_field($page.'ctabtn_url'),
                    'button_background_color' => WebinarSysteemHelperFunctions::add_hash_to_color(
                        $webinar->get_field($page.'ctabtn_clr')),
                    'button_text_color' => WebinarSysteemHelperFunctions::add_hash_to_color(
                        $webinar->get_field($page.'ctabtn_txt_clr')),
                    'time_limited' => $webinar->get_field($page.'cta_time_limited') == 'yes',
                    'show_for_minutes' => (int) $webinar->get_field($page.'cta_show_for_minutes'),
                    'html_content' => apply_filters('the_content', $webinar->get_field($page.'ctatxt_txt')),
                    'html_background_color' => WebinarSysteemHelperFunctions::add_hash_to_color(
                        $webinar->get_field($page.'ctatxt_fld_bckg_clr')),
                    'html_border_color' => WebinarSysteemHelperFunctions::add_hash_to_color(
                        $webinar->get_field($page.'ctatxt_fld_border_clr')),
                    'html_text_color' => WebinarSysteemHelperFunctions::add_hash_to_color(
                        $webinar->get_field($page.'ctatxt_fld_content_clr')),
                ],
                'details' => [
                    'title' => get_the_title($webinar_id)
                ],
                'hand_raising' => [
                    'enabled' => $webinar->get_field($page.'hand_raising_yn') == 'yes',
                ],
                'page_text' => [
                    'webinar_starting_text' => $webinar->get_field($page.'webinar_starting_text'),
                    'webinar_closed_text' => $webinar->get_field($page.'webinar_closed_text')
                ],
                'countdown_page' => $webinar->get_countdown_page_settings(),
                'page_params' => $webinar->get_object($page.'page_params'),
                'enabled_poll_id' => $webinar->get_enabled_poll_id()
            ]
        ];

        WebinarSysteemUtils::write_cache(
            self::get_cache_path($webinar_id, 2),
            $result
        );
    }

    public static function write($webinar_id) {

        if (!$webinar_id) {
            return;
        }

        $status = get_post_meta($webinar_id, '_wswebinar_gener_webinar_status', true);
        $page = ($status == 'live' || $status == 'liv') ? 'livep_': 'replayp_';

        $result = [
            'last_seen' => true,
            'webinar_id' => (int)$webinar_id,
            'online_attendees' => WebinarSysteemAjax::get_online_attendees($webinar_id),
            'chats' => WebinarSysteemAjax::get_chats($webinar_id, $page),
            'incentive_status' => WebinarSysteemAjax::incentiveStatus($webinar_id, $status),
            'cta_status' =>  WebinarSysteemAjax::get_cta_status($webinar_id, $page),
            'hostdesc_status' => WebinarSysteemAjax::getHostDescStatus($webinar_id, $page),
            'actionbox_status' => WebinarSysteemAjax::getActionBoxStatus($webinar_id, $page)
        ];

        $is_cta_shown_after = get_post_meta($webinar_id, '_wswebinar_' . $page . 'call_action', true) == 'aftertimer';

        if ($is_cta_shown_after) {
            $result['cta_show_after'] = intval(get_post_meta($webinar_id, '_wswebinar_' . $page . 'cta_show_after', true));
        }

        WebinarSysteemUtils::write_cache(
            self::get_cache_path($webinar_id),
            $result
        );

        self::write_v2($webinar_id);
    }

    public static function get_cache_key($webinar_id) {
        // get the cache key for this webinar
        $cache_key = get_post_meta($webinar_id, '_wswebinar_cache_key', true);

        if (!$cache_key) {
            $cache_key = WebinarSysteemHelperFunctions::generate_uuid();
            update_post_meta($webinar_id, '_wswebinar_cache_key', $cache_key);
        }

        return $cache_key;
    }

    public static function get_cache_path($webinar_id, $version = null) {
        $cache_key = self::get_cache_key($webinar_id);

        return WebinarSysteemUtils::get_cache_path($version != null
            ? 'webinar_'.$cache_key.'_v'.$version
            : 'webinar_'.$cache_key);
    }

    public static function get_cache_url($webinar_id, $version = null) {
        $cache_key = self::get_cache_key($webinar_id);

        return WebinarSysteemUtils::get_cache_url($version != null
            ? 'webinar_'.$cache_key.'_v'.$version
            : 'webinar_'.$cache_key);
    }
}
