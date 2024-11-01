<?php

class WebinarSysteemRegistrationWidget extends WebinarSysteemPostTypeBase
{
    public $name;
    public $id;
    public $created_at;

    public function __construct($post) {
        $this->id = $post->ID;
        $this->name = $post->post_title;
        $this->created_at = $post->post_date;
    }

    public static function create_from_id($id) {
        $post = get_post($id);
        if (empty($post) || !$post) {
            return null;
        }
        return new WebinarSysteemRegistrationWidget($post);
    }

    public function get_registration_count() {
        $stats = $this->get_json('stats', []);

        if (!array_key_exists('total_registrations', $stats)) {
            return 0;
        }

        return $stats['total_registrations'];
    }

    public function increment_registration_count() {
        $stats = $this->get_json('stats', []);

        if (!array_key_exists('total_registrations', $stats)) {
            $stats['total_registrations'] = 0;
        }

        $stats['total_registrations'] += 1;

        return $this->set_json('stats', $stats);
    }

    public function get_webinar() {
        $params = self::get_widget_params($this->id);

        if (!$params) {
            return null;
        }

        return WebinarSysteemWebinar::create_from_id($params->webinarId);
    }

    public static $post_type = 'wpws_reg_widget';

    public static function register_post_type()
    {
        register_post_type(WebinarSysteemRegistrationWidget::$post_type, [
            'labels' => [
                'name' => __('Registration Widgets', '_wswebinar'),
                'singular_name' => __('Registration Widget', '_wswebinar'),
                'name_admin_bar' => __('Registration Widget', '_wswebinar'),
                'add_new' => __('Add Registration Widget', '_wswebinar'),
                'add_new_item' => __('Add Registration Widget', '_wswebinar'),
                'new_item' => __('New Registration Widget', '_wswebinar'),
                'edit_item' => __('Edit Registration Widget', '_wswebinar'),
                'view_item' => __('View Registration Widget', '_wswebinar'),
			],
            'public' => true,
            'has_archive' => false,
            'show_in_menu' => false,
            'rewrite' => ['slug' => 'wpws_forms', 'with_front' => false],
            'show_in_admin_bar' => true,
            'supports' => ['title', 'editor'],
            'capibility_type' => ['wswebinar', 'wswebinars'],
            'capabilities' => [
                'read_post' => 'read_wswebinar',
                'edit_post' => 'edit_wswebinar',
                'delete_post' => 'delete_wswebinar',
                'publish_posts' => 'publish_wswebinars',
                'edit_posts' => 'edit_wswebinars',
                'edit_others_posts' => 'edit_others_wswebinars',
                'read_private_posts' => 'read_private_wswebinars',
                'delete_posts' => 'delete_wswebinars',
			],
        ]);
    }

    public static function get_widgets() {
        global $post;

        $args = array(
            'posts_per_page' => -1,
            'orderby' => 'post_title',
            'order' => 'ASC',
            'post_type' => WebinarSysteemRegistrationWidget::$post_type,
            'post_status' => 'publish',
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters
            'suppress_filters' => true
        );

        $post_data = get_posts($args);
        $widgets = [];

        foreach ($post_data as $post) {
            $widgets[] = new WebinarSysteemRegistrationWidget($post);
        }

        return $widgets;
    }

    public static function delete_widget($id) {

        if (get_post_type($id) !== WebinarSysteemRegistrationWidget::$post_type) {
            return false;
        }

        // TODO Add specific permission?
        if (!current_user_can('_wswebinar_createwebinars')) {
            return false;
        };

        wp_delete_post($id, true);

        return true;
    }

    public static function quick_store_widgets_with_triggers() {
        // iterate all widgets
        $widgets = self::get_widgets();

        // find all widgets that have a display trigger
        $ids = [];

        foreach ($widgets as $widget) {
            $params = self::get_widget_params($widget->id);
            
            if ($params->popupTriggerOnClick) {
                $ids[] = $widget->id;
            }
        };

        // store them as a list of numbers in options
        WebinarSysteemSettings::instance()->set_registration_widgets_with_triggers($ids);
    }

    public static function get_registration_widgets_with_triggers() {
        $ids = WebinarSysteemSettings::instance()->get_registration_widgets_with_triggers();

        return array_map(function ($id) {
            // get the widget
            $params = WebinarSysteemRegistrationWidget::get_widget_params($id);

            if (!$params) {
                return null;
            }

            // get the webinar
            $webinar = WebinarSysteemWebinar::create_from_id($params->webinarId);
    
            if (!$webinar) {
                return null;
            }

            return [
                'webinar' => WebinarSysteemRegistrationWidget::get_webinar_info($webinar),
                'params' => $params,
                'widget_id' => $id,
            ];
        }, $ids);
    }

    public static function add_or_update_widget($id, $params) {

        // TODO Add specific permission?
        if (!current_user_can('_wswebinar_createwebinars')) {
            return false;
        };

        if (empty($params) || empty($params->name)) {
            return false;
        }

        // allow unicode
        $json = wp_json_encode($params);

        if (!empty($id)) {
            if (get_post_type($id) !== WebinarSysteemRegistrationWidget::$post_type) {
                return false;
            }

            wp_update_post([
                'ID' => $id,
                'post_title' => wp_strip_all_tags($params->name),
                'post_content' => base64_encode($json),
            ]);

            self::quick_store_widgets_with_triggers();

            return $id;
        }

        $post_id = wp_insert_post([
            'post_title' => wp_strip_all_tags($params->name),
            'post_content' => base64_encode($json),
            'post_status' => 'publish',
            'post_type' => WebinarSysteemRegistrationWidget::$post_type,
        ]);

        self::quick_store_widgets_with_triggers();

        return $post_id;
    }

    public static function get_widget_params($id) {
        if (get_post_type($id) !== WebinarSysteemRegistrationWidget::$post_type) {
            return false;
        }

        $json = get_post_field('post_content', $id);
        return WebinarSysteemBase64::decode_base64_or_json($json);
    }

    /**
     *
     * @param WebinarSysteemWebinar $webinar
     * @return array
     *
     */

    public static function get_webinar_info($webinar) {
        global $wpdb;

        $is_recurring = $webinar->is_recurring();
        $is_jit = $webinar->is_jit();

        $timeslot_settings = $is_jit
            ? $webinar->get_jit_settings()
            : $webinar->get_recurring_settings();

        return [
            'id' => $webinar->id,
            'name' => $webinar->name,
            'is_paid' => $webinar->is_paid(),
            'is_recurring' => $is_recurring,
            'is_jit' => $webinar->is_jit(),
            'recurring_type' => $webinar->get_recurring_type(),
            'is_right_now' => $webinar->is_right_now(),
            'timeslot_settings' => $is_recurring
                ? $timeslot_settings
                : null,
            'timeslots_to_show' => $webinar->get_timeslots_to_show(),
            'recurring_days_offset' => $webinar->get_recurring_offset_days(),
            'timezone' => $webinar->get_timezone(),
            'timezone_offset' => $webinar->get_timezone_offset() * 60,
            'custom_fields' => $webinar->get_custom_fields(),
            'one_time_datetime' => $webinar->get_one_time_datetime(),
            'registration_disabled' => $webinar->is_registration_disabled(),
            'is_gdpr_enabled' => $webinar->is_gdpr_enabled(),
            'gdpr_optin_text' => $webinar->get_gdpr_optin_text(),
            'purchase_url' => $webinar->get_purchase_url(),
            'registration_count' => $webinar->get_registration_count(),
            'question_count' => $webinar->get_question_count(),
            'chat_count' => $webinar->get_chat_count(),
            'is_automated' => $webinar->is_automated(),
            'status' => $webinar->get_status(),
            'is_published' => $webinar->is_published(),
            'url' => $webinar->get_url(),
            'duration' => $webinar->get_duration(),
            'max_hosted_attendee_count' => $webinar->get_maximum_attendee_count(),
            'maximum_attendee_enabled' => $webinar->get_maximum_attendee_enabled(),
            'automated_offset' => $webinar->get_automated_offset(),
            'allow_anonymous_registration' => $webinar->allow_anonymous_registration(),
            'allow_standard_registration' => $webinar->allow_standard_registration(),
            'is_password_protected' => $webinar->is_password_protected(),
            'name_mode' => $webinar->get_registration_name_mode(),
            'email_mode' => $webinar->get_registration_email_mode()
        ];
    }

    public static function get_webinars() {
        global $post;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $post_status = isset($_POST['include_draft']) ? 'any' : 'publish';

        $args = [
            'posts_per_page' => -1,
            'orderby' => 'post_title',
            'order' => 'ASC',
            'post_type' => 'wswebinars',
            'post_status' => $post_status,
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters
            'suppress_filters' => true
        ];

        $post_data = get_posts($args);
        $webinars = [];

        foreach ($post_data as $post) {
            $webinar = new WebinarSysteemWebinar($post);
            $webinars[] = self::get_webinar_info($webinar);
        }

        return $webinars;
    }
}
