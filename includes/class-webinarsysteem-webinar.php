<?php

class WebinarSysteemWebinar extends WebinarSysteemPostTypeBase
{
    public static $post_type = 'wswebinars';

    public $name;
    public $link;

    public static function create_from_id($id) {
        $post = get_post($id);
        if (empty($post) || !$post) {
            return null;
        }

        // make sure it's a webinar
        if (get_post_type($id) !== self::$post_type) {
            return null;
        }

        return new WebinarSysteemWebinar($post);
    }

    public function __construct($post) {
        $this->id = $post->ID;
        $this->name = $post->post_title;
        $this->link = get_permalink($this->id);

        $this->load();
    }

    static function get_webinar_field($webinar_id, $field) {
        return get_post_meta($webinar_id, '_wswebinar_'.$field, true);
    }

    public function is_manual()
    {
        $type = $this->get_field('gener_air_type');
        return empty($type) || $type == 'live';
    }

    public function get_recurring_type() {
        $type = $this->get_field('gener_time_occur');

        if (empty($type)) {
            return 'recur';
        }

        return $type;
    }

    public function is_recurring() {
        if ($this->is_manual()) {
            return false;
        }

        switch ($this->get_recurring_type()) {
            case 'recur':
            case 'jit':
                return true;
        }

        return false;
    }

    public function is_jit() {
        return $this->get_recurring_type() == 'jit';
    }

    public function get_timezone_offset_for_date($dt)
    {
        $webinar_timezone = $this->get_selected_timezone();

        if (!empty($webinar_timezone)) {
            $offset = WebinarSysteemDateTime::get_timezone_offset(
                $webinar_timezone, $dt);
        } else {
            $offset = WebinarSysteemDateTime::get_timezone_offset(
                wp_timezone_string(),
                $dt
            );
        }

        return $offset;
    }

    public function get_timezone_offset() {
        return $this->get_timezone_offset_for_date($this->get_one_time_datetime());
    }

    public function get_timezone() {
        $offset = $this->get_timezone_offset();
        return WebinarSysteemDateTime::format_timezone_offset($offset);
    }

    public function get_selected_timezone() {
        return $this->get_field('timezoneidentifier');
    }

    public function set_selected_timezone($value) {
        return $this->set_field('timezoneidentifier', $value);
    }

    public function is_registration_disabled() {
        return $this->get_field('gener_regdisabled_yn') === 'yes';
    }

    public function set_registration_disabled($is_disabled) {
        return $this->set_field('gener_regdisabled_yn', $is_disabled ? 'yes' : '');
    }

    public function allow_anonymous_registration() {
        return $this->get_field('gener_allow_anonymous_registration_yn') === 'yes';
    }

    public function set_allow_anonymous_registration($value) {
        return $this->set_field('gener_allow_anonymous_registration_yn', $value ? 'yes' : '');
    }

    public function allow_standard_registration() {
        return $this->get_field('gener_allow_standard_registration_yn') != 'no';
    }

    public function set_allow_standard_registration($value) {
        return $this->set_field('gener_allow_standard_registration_yn', $value ? 'yes' : 'no');
    }

    public function get_custom_fields() {
        return [];
    }

    public function set_custom_fields($fields) {
    }

    public function is_paid() {
        return false;
    }

    public function set_is_paid($value) {
        return;
    }

    public function get_price() {
        return $this->get_field('ticket_price', 0);
    }

    public function is_password_protected() {
        return $this->get_field('gener_password_protected') == 'on';
    }

    public function set_is_password_protected($value) {
        return $this->set_field('gener_password_protected', $value ? 'on' : '');
    }

    public function get_password() {
        return $this->get_field('gener_password');
    }

    public function set_password($value) {
        return $this->set_field('gener_password', $value);
    }

    public function set_price($value) {
        return $this->set_field('ticket_price', $value);
    }

    public function is_gdpr_enabled() {
        $field = $this->is_paid()
            ? 'regp_wc_gdpr_optin_yn'
            : 'regp_gdpr_optin_yn';

        return $this->get_field($field) == 'yes';
    }

    public function set_gdpr_enabled($enabled) {
        $value = $enabled ? 'yes' : '';
        $this->set_field('regp_wc_gdpr_optin_yn', $value);
        $this->set_field('regp_gdpr_optin_yn', $value);
    }

    public function is_email_confirmation_required() {
        return $this->get_field('email_confirmation_required') == true;
    }

    public function set_email_confirmation_required($enabled) {
        $this->set_field('email_confirmation_required', $enabled);
    }

    public function get_email_confirmation_required_action() {
        return $this->get_field('email_confirmation_required_action');
    }

    public function set_email_confirmation_required_action($action) {
        $this->set_field('email_confirmation_required_action', $action);
    }

    public function get_email_confirmation_required_redirect_page_id() {
        return (int) $this->get_field('email_confirmation_required_redirect_page_id');
    }

    public function set_email_confirmation_required_redirect_page_id($action) {
        $this->set_field('email_confirmation_required_redirect_page_id', $action);
    }

    public function set_gdpr_optin_text($value) {
        $this->set_field('regp_gdpr_optin_text', $value);
    }

    public function get_gdpr_optin_text() {
        return $this->get_field('regp_gdpr_optin_text');
    }

    public function set_after_webinar_action($value) {
        $this->set_field('after_webinar_action', $value);
    }

    public function get_after_webinar_action() {
        return $this->get_field('after_webinar_action');
    }

    public function set_after_webinar_redirect_url($value) {
        $this->set_field('after_webinar_redirect_url', $value);
    }

    public function get_after_webinar_redirect_url() {
        return $this->get_field('after_webinar_redirect_url');
    }

    public function set_meta_keywords($value) {
        $this->set_field('meta_keywords', $value);
    }

    public function get_meta_keywords() {
        return $this->get_field('meta_keywords');
    }

    public function set_meta_description($value) {
        $this->set_field('meta_description', $value);
    }

    public function get_meta_description() {
        return $this->get_field('meta_description');
    }

    public function set_meta_title($value) {
        $this->set_field('meta_title', $value);
    }

    public function get_meta_title() {
        return $this->get_field('meta_title');
    }

    public function set_open_graph_title($value) {
        $this->set_field('open_graph_title', $value);
    }

    public function get_open_graph_title() {
        return $this->get_field('open_graph_title');
    }

    public function set_open_graph_description($value) {
        $this->set_field('open_graph_description', $value);
    }

    public function get_open_graph_description() {
        return $this->get_field('open_graph_description');
    }

    public function set_open_graph_image_url($value) {
        $this->set_field('open_graph_image_url', $value);
    }

    public function get_open_graph_image_url() {
        return $this->get_field('open_graph_image_url');
    }

    public function get_timeslots_to_show($default = 3) {

        $value = intval(filter_var(
            $this->get_field('gener_timeslot_count'),
            FILTER_SANITIZE_NUMBER_INT
        ));

        if ($value == null || $value <= 0 || $value > 60) {
            $value = $default;
        }

        return $value;
    }

    public function set_timeslots_to_show($value) {
        $value = intval(filter_var(
            $value,
            FILTER_SANITIZE_NUMBER_INT
        ));

        $this->set_field('gener_timeslot_count', $value);
    }

    public function get_recurring_offset_days($default = 0) {
        $value = intval(filter_var(
            $this->get_field('gener_offset_count'),
            FILTER_SANITIZE_NUMBER_INT
        ));

        if ($value == null || $value <= 0 || $value > 60) {
            $value = $default;
        }

        return $value;
    }

    public function get_jit_settings() {
        $value = intval(filter_var(
            $this->get_field('gener_jit_times'),
            FILTER_SANITIZE_NUMBER_INT
        ));

        if ($value == null || $value <= 0 || $value > 60) {
            $value = 15;
        }

        return [
            'days' => $this->get_json('gener_jit_days', []),
            'interval' => $value
        ];
    }

    public function get_recurring_settings() {
        return [
            'days' => $this->get_json('gener_rec_days', []),
            'times' => $this->get_json('gener_rec_times', [])
        ];
    }

    public function is_right_now() {
        if (!$this->is_recurring() || $this->get_recurring_type() != 'recur') {
            return false;
        }

        $times = $this->get_json('gener_rec_times', []);

        return in_array('rightnow', $times);
    }

    public function get_one_time_datetime() {
        if ($this->is_recurring()) {
            return null;
        }

        $date = $this->get_field('gener_date');
        $hour = $this->get_field('gener_hour');
        $minute = $this->get_field('gener_min');

        // return $date.' '.$hour.':'.$minute;
        return strtotime($date.' '.$hour.':'.$minute);
    }

    public function set_exit_url($value){
        $pattern = '/^(https?:\/\/)?'.
      '((([a-z\d]([a-z\d-]*[a-z\d])*)\.)+[a-z]{2,}|'.
      '((\d{1,3}\.){3}\d{1,3}))'.
      '(\:\d+)?(\/[-a-z\d%_.~+]*)*'.
      '(\?[;&a-z\d%_.~+=-]*)?'.
      '(\#[-a-z\d_]*)?$/';

       if((boolean)preg_match($pattern,$value)|| empty($value)){
           return $this->set_field('exit_url_link', $value);
       } else {
           return false;
       }
    }

    public function get_exit_url(){
        return $this->get_field('exit_url_link');
    }

    public function set_one_time_date($value) {
        $this->set_field('gener_date', $value);
    }

    public function set_one_time_hour($value) {
        $this->set_field('gener_hour', $value);
    }

    public function set_one_time_minute($value) {
        $this->set_field('gener_min', $value);
    }

    public function get_one_time_date() {
        return $this->get_field('gener_date');
    }

    public function get_one_time_hour() {
        return (int) $this->get_field('gener_hour');
    }

    public function get_one_time_minute() {
        return (int) $this->get_field('gener_min');
    }

    public function has_custom_registration_page() {
        return $this->get_field('regp_custom_reg_page_yn') == 'yes';
    }

    public function get_custom_registration_page_id() {
        return (int) $this->get_field('regp_custom_reg_page_id');
    }

    public function set_custom_registration_page_id($value) {
        return $this->set_field('regp_custom_reg_page_id', $value);
    }

    public function enable_custom_registration_page($enabled) {
        return $this->set_field('regp_custom_reg_page_yn', $enabled ? 'yes' : '');
    }

    public function has_custom_confirmation_page() {
        return $this->get_field('tnxp_custom_page_yn') == 'yes';
    }

    public function enable_custom_confirmation_page($enabled) {
        return $this->set_field('tnxp_custom_page_yn', $enabled ? 'yes' : '');
    }

    public function get_custom_confirmation_page_id() {
        return (int) $this->get_field('tnxp_custom_page_id');
    }

    public function set_custom_confirmation_page_id($value) {
        return $this->set_field('tnxp_custom_page_id', $value);
    }

    public function get_host() {
        return $this->get_field('hostmetabox_hostname', '');
    }

    public function set_host($value) {
        $this->set_field('hostmetabox_hostname', $value);
    }

    public function set_woo_product_id($product_id) {
        $this->set_field('ticket_id', $product_id);
    }

    public function get_woo_product_id() {
        return $this->get_field('ticket_id');
    }

    public function unlink_woo_product_id() {
        $this->delete_field('ticket_id');
    }

    public function reset_cache_key() {
        $this->delete_field('cache_key');
    }

    public function get_cache_key() {
        return $this->get_field('cache_key');
    }

    public function get_purchase_url() {
        return null;
    }

    public function get_add_to_cart_url() {
        return null;
    }

    /**
     * Uplift current time to webinar's timezone.
     *
     * @param integer $webinar_id
     * @param integer $dt
     * @return integer
     */
    public static function get_now_in_webinar_timezone($webinar_id, $dt = null)
    {
        $timezone = self::get_webinar_field($webinar_id, 'timezoneidentifier');

        if (!$timezone || empty($timezone)) {
            if ($dt !== null) {
                return (int) $dt + (get_option('gmt_offset') * HOUR_IN_SECONDS);
            }
            return current_time('timestamp');
        }

        try {
            $default_timezone = date_default_timezone_get();
            // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            $dt = $dt == null ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $dt);


            $adjusted = date_create($dt, timezone_open($default_timezone));

            date_timezone_set($adjusted, timezone_open($timezone));
            $formatted = date_format($adjusted, 'Y-m-d H:i:s');

            return strtotime($formatted);
        } catch (Exception $e) {
            return current_time('timestamp');
        }
    }

    public function get_now_in_timezone($date = null) {
        return self::get_now_in_webinar_timezone($this->id, $date);
    }

    public function get_duration()
    {
        $duration = $this->get_field('gener_duration');

        if (empty($duration)) {
            return 3600;
        }

        return floatval($duration);
    }

    public function get_status() {
        return $this->get_field('gener_webinar_status', 'cou');
    }

    public function set_went_live_at_now() {
        return $this->set_field('went_live_at', gmdate('Y-m-d\TH:i:s\Z'));
    }

    public function set_went_live_at_timestamp($timestamp) {
        return $this->set_field('went_live_at', gmdate('Y-m-d\TH:i:s\Z', $timestamp));
    }

    public function get_went_live_at_now() {
        return $this->get_field('went_live_at');
    }

    public function set_status($value) {
        if ($value == 'liv') {
            $this->set_went_live_at_now();
        }
        return $this->set_field('gener_webinar_status', $value);
    }

    public function get_live_page_prefix() {
        if ($this->is_manual()) {
            switch ($this->get_status()) {
                case 'cou':
                case 'live':
                case 'liv':
                case 'clo':
                    return 'livep_';

                default:
                    break;
            }
        }
        return 'replayp_';
    }

    public function set_chat_enabled($value) {
        $page = $this->get_live_page_prefix();
        $this->set_field("{$page}show_chatbox", $value ? 'yes' : '');
    }

    public function set_questions_enabled($value) {
        $page = $this->get_live_page_prefix();
        $this->set_field("{$page}askq_yn", $value ? 'yes' : '');
    }

    public function set_attendees_tab_enabled($value) {
        $page = $this->get_live_page_prefix();
        $this->set_field("{$page}show_attendees_yn", $value ? 'yes' : '');
    }

    public function set_hand_raising_enabled($value) {
        $page = $this->get_live_page_prefix();
        $this->set_field("{$page}hand_raising_yn", $value ? 'yes' : '');
    }

    public function set_cta_enabled($value) {
        $page = $this->get_live_page_prefix();
        $this->set_field("{$page}manual_show_cta", $value ? 'yes' : '');
    }

    public function get_live_page_template() {
        $page_status = $this->get_live_page_prefix();
        return $this->get_field($page_status.'page_template');
    }

    public function allow_auto_registration() {
        return $this->get_field('gener_auto_register_yn') == 'yes';
    }

    public function set_allow_auto_registration($value) {
        return $this->set_field('gener_auto_register_yn', $value ? 'yes' : '');
    }

    public function auto_register_wp_users() {
        return $this->get_field('gener_auto_register_wp_users_yn') == 'yes';
    }

    public function set_auto_register_wp_users($value) {
        return $this->set_field('gener_auto_register_wp_users_yn', $value ? 'yes' : '');
    }

    public function auto_register_for_currently_running_webinar() {
        return $this->get_field('auto_register_for_currently_running_webinar');
    }

    public function set_auto_register_for_currently_running_webinar($value) {
        return $this->set_field('auto_register_for_currently_running_webinar', $value);
    }

    public function get_live_media_type() {
        return $this->get_field('livep_vidurl_type');
    }

    public function get_live_media_url() {
        return $this->get_field('livep_vidurl');
    }

    public function set_live_media_type($value) {
        return $this->set_field('livep_vidurl_type', $value);
    }

    public function set_live_media_url($value) {
        return $this->set_field('livep_vidurl', $value);
    }

    public function get_timeslot_settings() {
        return $this->is_jit()
            ? $this->get_jit_settings()
            : $this->get_recurring_settings();
    }

    public function get_recurring_times() {
        $is_jit = $this->is_jit();
        $timeslot_settings = $this->get_timeslot_settings();

        if ($is_jit) {
            $interval = $timeslot_settings['interval'];
            $times = [];

            for ($hour = 0; $hour < 24; $hour += 1) {
                for ($minute = 0; $minute < 60; $minute += $interval) {
                    $minute_padded = $minute < 10
                        ? "0{$minute}" : $minute;
                    $times[] = "{$hour}:{$minute_padded}";
                }
            }

            return $times;
        }

        return $timeslot_settings['times'];
    }

    protected function get_table_count($table) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return (int) $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare("SELECT count(id) FROM {$table} WHERE webinar_id=%d", $this->id)
        );
    }

    public function get_registration_count($exact_time = null) {
        global $wpdb;
        $table = WebinarSysteemTables::get_subscribers();

        if ($exact_time == null || $exact_time == 0) {
            return $this->get_table_count($table);
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DateTime.RestrictedFunctions.date_date
        $query = $wpdb->prepare("SELECT count(id) FROM {$table} WHERE webinar_id=%d AND exact_time=%s", $this->id, date('Y-m-d H:i:s', $exact_time));

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return (int) $wpdb->get_var($query);
    }

    public function get_question_count() {
        return self::get_table_count(WebinarSysteemTables::get_questions());
    }

    public function get_chat_count() {
        return self::get_table_count(WebinarSysteemTables::get_chats());
    }

    public function get_post_status() {
        return get_post_status($this->id);
    }

    public function is_published() {
        return self::get_post_status() == 'publish';
    }

    public function set_post_status($status) {
        wp_update_post([
            'ID' => $this->id,
            'post_status' => $status
        ]);
    }

    public function is_automated() {
        return $this->get_field('gener_air_type') === 'rec';
    }

    protected static function delete_webinar_data($id) {
        global $wpdb;

        // delete questions
        $table = WebinarSysteemTables::get_questions();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE webinar_id = %d", $id));

        // delete chats
        $table = WebinarSysteemTables::get_chats();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE webinar_id = %d", $id));

        // delete attendees
        $table = WebinarSysteemTables::get_subscribers();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE webinar_id = %d", $id));

        // delete attendees
        $table = WebinarSysteemTables::get_notifications();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE webinar_id = %d", $id));

        // delete attendees
        $table = WebinarSysteemTables::get_poll_votes();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE webinar_id = %d", $id));
    }

    public static function delete_webinar($id) {

        if (get_post_type($id) !== self::$post_type) {
            return false;
        }

        if (!current_user_can('_wswebinar_createwebinars')) {
            return false;
        };

        // delete chats, questions, attendees
        self::delete_webinar_data($id);

        // delete the post
        wp_delete_post($id, true);

        return true;
    }

    public function get_url() {
        $post = get_post($this->id);

        if (in_array($post->post_status, array( 'draft', 'pending', 'auto-draft'))) {
            $post_copy = clone $post;
            $post_copy->post_status = 'publish';
            $post_copy->post_name = sanitize_title(
                $post_copy->post_name
                    ? $post_copy->post_name
                    : $post_copy->post_title,
                $post_copy->ID
            );
            return get_permalink($post_copy);
        }

        return get_permalink($this->id);
    }

    public function get_url_with_auth($email, $secret) {
        $token = WebinarSysteemBase64::encode_array([$secret, $email]);
        return WebinarSysteemHelperFunctions::add_param_to_url($this->get_url(), 'auth='.$token);
    }

    public function get_confirmation_link($attendee) {
        // generate the nonce key based on name, email and time
        $data = [
            $attendee->name,
            $attendee->email,
            $attendee->exact_time,
            $attendee->custom_fields
        ];

        // generate the token
        $token = WebinarSysteemBase64::encode_array($data);
        $verify = WebinarSysteemHashes::get_secure_hash($token);
        return WebinarSysteemHelperFunctions::add_param_to_url($this->get_url(), 'confirm='.$token.'&verify='.$verify);
    }

    public function get_slug() {
        return get_post_field('post_name', $this->id);
    }

    public function set_slug($slug) {
        wp_update_post([
            'ID' => $this->id,
            'post_name' => sanitize_title_with_dashes($slug)
        ]);
    }

    public function get_name() {
        return get_the_title($this->id);
    }

    public function get_description() {
        $content = get_post_field('post_content', $this->id);
        return do_shortcode($content);
    }

    public function get_type() {
        if ($this->is_manual()) {
            return 'manual';
        }

        if ($this->is_jit()) {
            return 'jit';
        }

        if ($this->is_right_now()) {
            return 'now';
        }

        return $this->is_recurring()
            ? 'recurring'
            : 'onetime';
    }

     public function get_mail_account_id() {
        switch ($this->get_mail_provider()) {
            case 'drip':
                return $this->get_field('drip_accounts');

            case 'none':
            default:
                return null;
        }
    }

    public function get_mail_list_id() {
        $provider = $this->get_mail_provider();

        switch ($provider) {
            case 'none':
                return null;

            case 'drip':
                return $this->get_field('drip_campaigns');

            case 'convertkit':
                return $this->get_field('convertkit_form');

            default:
                return $this->get_field($provider.'_list');
        }
    }

    public function get_access_type() {
        return $this->get_field('accesstab_parent');
    }

    public function set_access_type($value) {
        return $this->set_field('accesstab_parent', $value);
    }

    public function get_access_roles() {
        $roles = $this->get_field('selected_user_role');
        return explode(',', $roles);
    }

    public function set_access_roles($roles) {
        return $this->set_field('selected_user_role', join(',', $roles));
    }

    public function get_access_redirect_page_id() {
        return (int) $this->get_field('ws_actab_redirect_page');
    }

    public function set_access_redirect_page_id($value) {
        return $this->set_field('ws_actab_redirect_page', (string) $value);
    }

    public function get_access_user_ids() {
        $ids = $this->get_field('filter_user_ids');
        return array_map(function ($val) {
            return (int) $val;
        }, explode(',', $ids));
    }

    public function set_access_user_ids($user_ids) {
        return $this->set_field('filter_user_ids', join(',', $user_ids));
    }

    public function get_access_wc_membership_level_ids() {
        $val = $this->get_field('selected_member_level');;
        if (is_array($val)) {
            return $val;
        }

        if ($val == null) {
            return [];
        }

        return [$val];
    }

    public function set_access_wc_membership_level_ids($value) {
        return $this->set_field('selected_member_level', $value);
    }

    public function get_countdown_background_image_url() {
        return $this->get_field('cntdwnp_bckg_img');
    }

    public function set_countdown_background_image_url($value) {
        return $this->set_field('cntdwnp_bckg_img', $value);
    }

    public function get_countdown_background_color() {
        return $this->get_color('cntdwnp_bckg_clr');
    }

    public function set_countdown_background_color($value) {
        return $this->set_field('cntdwnp_bckg_clr', $value);
    }

    public function get_countdown_title_color() {
        return $this->get_color('cntdwnp_title_clr');
    }

    public function set_countdown_title_color($value) {
        return $this->set_field('cntdwnp_title_clr', $value);
    }

    public function get_countdown_tagline_color() {
        return $this->get_color('cntdwnp_tagline_clr');
    }

    public function set_countdown_tagline_color($value) {
        return $this->set_field('cntdwnp_tagline_clr', $value);
    }

    public function get_countdown_description_color() {
        return $this->get_color('cntdwnp_desc_clr');
    }

    public function set_countdown_description_color($value) {
        return $this->set_field('cntdwnp_desc_clr', $value);
    }

    public function is_countdown_timer_visible() {
        return $this->get_field('cntdwnp_timershow_yn') == 'yes';
    }

    public function set_countdown_timer_visible($value) {
        return $this->set_field('cntdwnp_timershow_yn', $value ? 'yes' : '');
    }

    public function get_countdown_header_script_tag() {
        return '';
    }

    public function get_countdown_body_script_tag() {
        return '';
    }

    public function set_countdown_header_script_tag($value) {
    }

    public function set_countdown_body_script_tag($value) {
    }

    public function set_countdown_page_settings($value) {
        return $this->set_field('cntdwnp_page_settings', $value);
    }

    public function get_countdown_page_settings() {
        return $this->get_field('cntdwnp_page_settings');
    }

    public function get_registration_background_image_url() {
        return $this->get_field('regp_bckg_img');
    }

    public function set_registration_background_image_url($value) {
        return $this->set_field('regp_bckg_img', $value);
    }

    public function get_registration_background_color() {
        return $this->get_color('regp_bckg_clr');
    }

    public function set_registration_background_color($value) {
        $this->set_field('regp_bckg_clr', $value);
    }

    public function get_registration_header_script_tag() {
        return '';
    }

    public function get_registration_body_script_tag() {
        return '';
    }

    public function set_registration_header_script_tag($value) {
    }

    public function set_registration_body_script_tag($value) {
    }

    public function get_registration_show_page_section() {
        return $this->get_field('regp_show_content_setion') == 'yes';
    }

    public function set_registration_show_page_section($value) {
        return $this->set_field('regp_show_content_setion', $value ? 'yes' : '');
    }

    public function get_registration_media_type() {
        return $this->get_field('regp_vidurl_type');
    }

    public function set_registration_media_type($value) {
        $this->set_field('regp_vidurl_type', $value);
    }

    public function get_registration_media_url() {
        return $this->get_field('regp_vidurl');
    }

    public function set_registration_media_url($value) {
        $this->set_field('regp_vidurl', $value);
    }

    // Registration Layout
    public function get_registration_title_color() {
        return $this->get_color('regp_regtitle_clr');
    }

    public function set_registration_title_color($value) {
        $this->set_field('regp_regtitle_clr', $value);
    }

    public function get_registration_date_time_color() {
        return $this->get_color('regp_regmeta_clr');
    }

    public function set_registration_date_time_color($value) {
        $this->set_field('regp_regmeta_clr', $value);
    }

    public function is_registration_register_form_hidden() {
        return $this->get_field('regp_hide_regtab') === 'yes';
    }

    public function set_registration_register_form_hidden($value) {
        $this->set_field('regp_hide_regtab', $value ? 'yes': '');
    }

    public function get_registration_register_title() {
        return $this->get_field('regp_regformtitle');
    }

    public function set_registration_register_title($value) {
        $this->set_field('regp_regformtitle', $value);
    }

    public function get_registration_register_text() {
        return $this->get_field('regp_regformtxt');
    }

    public function set_registration_register_text($value) {
        $this->set_field('regp_regformtxt', $value);
    }

    public function get_registration_register_footer() {
        return $this->get_field('regp_regformfooter');
    }

    public function set_registration_register_footer($value) {
        $this->set_field('regp_regformfooter', $value);
    }

    public function get_registration_register_button_text() {
        return $this->get_field('regp_ctatext');
    }

    public function set_registration_register_button_text($value) {
        $this->set_field('regp_ctatext', $value);
    }

    public function get_registration_register_button_background_color() {
        return $this->get_color('regp_regformbtn_clr');
    }

    public function set_registration_register_button_background_color($value) {
        $this->set_field('regp_regformbtn_clr', $value);
    }

    public function get_registration_register_button_border_color() {
        return $this->get_color('regp_regformbtnborder_clr');
    }

    public function set_registration_register_button_border_color($value) {
        $this->set_field('regp_regformbtnborder_clr', $value);
    }

    public function get_registration_register_button_text_color() {
        return $this->get_color('regp_regformbtntxt_clr');
    }

    public function set_registration_register_button_text_color($value) {
        $this->set_field('regp_regformbtntxt_clr', $value);
    }

    public function is_registration_login_form_hidden() {
        return $this->get_field('regp_hide_logintab') === 'yes';
    }

    public function set_registration_login_form_hidden($value) {
        $this->set_field('regp_hide_logintab', $value ? 'yes': '');
    }

    public function get_registration_login_title() {
        return $this->get_field('regp_loginformtitle');
    }

    public function set_registration_login_title($value) {
        $this->set_field('regp_loginformtitle', $value);
    }

    public function get_registration_login_text() {
        return $this->get_field('regp_loginformtxt');
    }

    public function set_registration_login_text($value) {
        $this->set_field('regp_loginformtxt', $value);
    }

    public function get_registration_login_button_text() {
        return $this->get_field('regp_loginctatext');
    }

    public function set_registration_login_button_text($value) {
        return $this->set_field('regp_loginctatext', $value);
    }

    public function get_registration_login_button_background_color() {
        return $this->get_color('regp_loginformbtn_clr');
    }

    public function set_registration_login_button_background_color($value) {
        $this->set_field('regp_loginformbtn_clr', $value);
    }

    public function get_registration_login_button_border_color() {
        return $this->get_color('regp_loginformbtnborder_clr');
    }

    public function set_registration_login_button_border_color($value) {
        $this->set_field('regp_loginformbtnborder_clr', $value);
    }

    public function get_registration_login_button_text_color() {
        return $this->get_color('regp_loginformbtntxt_clr');
    }

    public function set_registration_login_button_text_color($value) {
        $this->set_field('regp_loginformbtntxt_clr', $value);
    }

    public function is_registration_show_description() {
        return $this->get_field('regp_show_description') === 'yes';
    }

    // note, this uses yes or no from old codebase to support default values
    public function set_registration_show_description($value) {
        $this->set_field('regp_show_description', $value ? 'yes' : 'no');
    }

    public function get_registration_description_text_color() {
        return $this->get_color('regp_wbndesc_clr');
    }

    public function set_registration_description_text_color($value) {
        $this->set_field('regp_wbndesc_clr', $value);
    }

    public function get_registration_description_background_color() {
        return $this->get_color('regp_wbndescbck_clr');
    }

    public function set_registration_description_background_color($value) {
        return $this->set_field('regp_wbndescbck_clr', $value);
    }

    public function get_registration_description_border_color() {
        return $this->get_color('regp_wbndescborder_clr');
    }

    public function set_registration_description_border_color($value) {
        $this->set_field('regp_wbndescborder_clr', $value);
    }

    public function get_registration_reg_form_background_color() {
        return $this->get_color('regp_regformbckg_clr');
    }

    public function set_registration_reg_form_background_color($value) {
        $this->set_field('regp_regformbckg_clr', $value);
    }

    public function get_registration_reg_form_border_color() {
        return $this->get_color('regp_regformborder_clr');
    }

    public function set_registration_reg_form_border_color($value) {
        $this->set_field('regp_regformborder_clr', $value);
    }

    public function get_registration_reg_form_font_color() {
        return $this->get_color('regp_regformfont_clr');
    }

    public function set_registration_reg_form_font_color($value) {
        $this->set_field('regp_regformfont_clr', $value);
    }

    public function get_registration_reg_form_tab_background_color() {
        return $this->get_color('regp_tabbg_clr');
    }

    public function set_registration_reg_form_tab_background_color($value) {
        $this->set_field('regp_tabbg_clr', $value);
    }

    public function get_registration_reg_form_tab_text_color() {
        return $this->get_color('regp_tabtext_clr');
    }

    public function set_registration_reg_form_tab_text_color($value) {
        $this->set_field('regp_tabtext_clr', $value);
    }

    public function get_registration_reg_form_tab_registration_text() {
        return $this->get_field('regp_tabone_text');
    }

    public function set_registration_reg_form_tab_registration_text($value) {
        $this->set_field('regp_tabone_text', $value);
    }

    public function get_registration_reg_form_tab_login_text() {
        return $this->get_field('regp_tabtwo_text');
    }

    public function set_registration_reg_form_tab_login_text($value) {
        return $this->set_field('regp_tabtwo_text', $value);
    }

    public function get_registration_ticket_title_text() {
        return $this->get_field('ticketp_buyformtitle');
    }

    public function set_registration_ticket_title_text($value) {
        return $this->set_field('ticketp_buyformtitle', $value);
    }

    public function get_registration_ticket_link_text() {
        return $this->get_field('ticketp_buy_link_text');
    }

    public function set_registration_ticket_link_text($value) {
        return $this->set_field('ticketp_buy_link_text', $value);
    }

    public function get_registration_ticket_description() {
        return $this->get_field('ticketp_buyformtxt');
    }

    public function set_registration_ticket_description($value) {
        return $this->set_field('ticketp_buyformtxt', $value);
    }

    public function get_registration_ticket_thank_you_message() {
        return $this->get_field('ticket_thank_you_message');
    }

    public function set_registration_ticket_thank_you_message($value) {
        return $this->set_field('ticket_thank_you_message', $value);
    }

    // confirmation page

    public function is_confirmation_page_disabled() {
        return $this->get_field('tnxp_disabled_yn') == 'yes';
    }

    public function set_confirmation_page_disabled($value) {
        $this->set_field('tnxp_disabled_yn', $value ? 'yes' : '');
    }

    public function get_confirmation_title_text() {
        return $this->get_field('tnxp_pagetitle');
    }

    public function set_confirmation_title_text($value) {
        $this->set_field('tnxp_pagetitle', $value);
    }

    public function get_confirmation_title_color() {
        return $this->get_color('tnxp_pagetitle_clr');
    }

    public function set_confirmation_title_color($value) {
        return $this->set_field('tnxp_pagetitle_clr', $value);
    }

    public function get_confirmation_background_color() {
        return $this->get_color('tnxp_bckg_clr');
    }

    public function set_confirmation_background_color($value) {
        return $this->set_field('tnxp_bckg_clr', $value);
    }

    public function get_confirmation_background_image_url() {
        return $this->get_field('tnxp_bckg_img');
    }

    public function set_confirmation_background_image_url($value) {
        $this->set_field('tnxp_bckg_img', $value);
    }

    public function get_confirmation_media_type() {
        return $this->get_field('tnxp_vidurl_type');
    }

    public function set_confirmation_media_type($value) {
        $this->set_field('tnxp_vidurl_type', $value);
    }

    public function get_confirmation_media_url() {
        return $this->get_field('tnxp_vidurl');
    }

    public function set_confirmation_media_url($value) {
        $this->set_field('tnxp_vidurl', $value);
    }

    public function get_confirmation_media_autoplay() {
        return $this->get_field('tnxp_video_auto_play_yn') == 'yes';
    }

    public function set_confirmation_media_autoplay($value) {
        $this->set_field('tnxp_video_auto_play_yn', $value ? 'yes' : '');
    }

    public function get_confirmation_media_show_controls() {
        return $this->get_field('tnxp_video_controls_yn') == 'yes';
    }

    public function set_confirmation_media_show_controls($value) {
        $this->set_field('tnxp_video_controls_yn', $value ? 'yes' : '');
    }

    public function get_confirmation_media_show_big_button() {
        return $this->get_field('tnxp_bigplaybtn_yn') == 'yes';
    }

    public function set_confirmation_media_show_big_button($value) {
        $this->set_field('tnxp_bigplaybtn_yn', $value ? 'yes' : '');
    }

    public function get_confirmation_header_script_tag() {
        return '';
    }

    public function set_confirmation_header_script_tag($value) {
    }

    public function get_confirmation_body_script_tag() {
        return '';
    }

    public function set_confirmation_body_script_tag($value) {
    }

    public function get_confirmation_link_above_text_color() {
        return $this->get_color('tnxp_link_above_clr');
    }

    public function set_confirmation_link_above_text_color($value) {
        return $this->set_field('tnxp_link_above_clr', $value);
    }

    public function get_confirmation_link_below_text_color() {
        return $this->get_color('tnxp_link_below_clr');
    }

    public function set_confirmation_link_below_text_color($value) {
        return $this->set_field('tnxp_link_below_clr', $value);
    }

    public function get_confirmation_ticket_border_color1() {
        return $this->get_color('tnxp_tktbckg_clr');
    }

    public function set_confirmation_ticket_border_color1($value) {
        return $this->set_field('tnxp_tktbckg_clr', $value);
    }

    public function get_confirmation_ticket_border_color2() {
        return $this->get_color('tnxp_tktbdr_clr');
    }

    public function set_confirmation_ticket_border_color2($value) {
        return $this->set_field('tnxp_tktbdr_clr', $value);
    }

    public function get_confirmation_ticket_body_text_color() {
        return $this->get_color('tnxp_tkttxt_clr');
    }

    public function set_confirmation_ticket_body_text_color($value) {
        return $this->set_field('tnxp_tkttxt_clr', $value);
    }

    public function get_confirmation_ticket_body_background_color() {
        return $this->get_color('tnxp_tktbodybckg_clr');
    }

    public function set_confirmation_ticket_body_background_color($value) {
        return $this->set_field('tnxp_tktbodybckg_clr', $value);
    }

    public function get_confirmation_ticket_header_background_color() {
        return $this->get_color('tnxp_tkthdrbckg_clr');
    }

    public function set_confirmation_ticket_header_background_color($value) {
        return $this->set_field('tnxp_tkthdrbckg_clr', $value);
    }

    public function get_confirmation_ticket_header_text_color() {
        return $this->get_color('tnxp_tkthdrtxt_clr');
    }

    public function set_confirmation_ticket_header_text_color($value) {
        return $this->set_field('tnxp_tkthdrtxt_clr', $value);
    }

    public function get_confirmation_ticket_button_color() {
        return $this->get_color('tnxp_tktbtn_clr');
    }

    public function set_confirmation_ticket_button_color($value) {
        return $this->set_field('tnxp_tktbtn_clr', $value);
    }

    public function get_confirmation_ticket_button_text_color() {
        return $this->get_color('tnxp_tktbtntxt_clr');
    }

    public function set_confirmation_ticket_button_text_color($value) {
        return $this->set_field('tnxp_tktbtntxt_clr', $value);
    }

    public function get_confirmation_is_social_sharing_enabled() {
        return $this->get_field('tnxp_socialsharing_enabled_yn') != 'no';
    }

    public function set_confirmation_is_social_sharing_enabled($value) {
        return $this->set_field('tnxp_socialsharing_enabled_yn', $value ? 'yes' : 'no');
    }

    public function get_confirmation_social_sharing_border_color() {
        return $this->get_color('tnxp_socialsharing_border_clr');
    }

    public function set_confirmation_social_sharing_border_color($value) {
        return $this->set_field('tnxp_socialsharing_border_clr', $value);
    }

    public function get_confirmation_social_sharing_background_color() {
        return $this->get_color('tnxp_socialsharing_bckg_clr');
    }

    public function set_confirmation_social_sharing_background_color($value) {
        return $this->set_field('tnxp_socialsharing_bckg_clr', $value);
    }

    public function get_confirmation_calendar_border_color() {
        return $this->get_color('tnxp_calendar_border_clr');
    }

    public function set_confirmation_calendar_border_color($value) {
        return $this->set_field('tnxp_calendar_border_clr', $value);
    }

    public function get_confirmation_calendar_background_color() {
        return $this->get_color('tnxp_calendar_bckg_clr');
    }

    public function set_confirmation_calendar_background_color($value) {
        return $this->set_field('tnxp_calendar_bckg_clr', $value);
    }

    public function get_confirmation_calendar_text_color() {
        return $this->get_color('tnxp_calendartxt_clr');
    }

    public function set_confirmation_calendar_text_color($value) {
        return $this->set_field('tnxp_calendartxt_clr', $value);
    }

    public function get_confirmation_calendar_button_text_color() {
        return $this->get_color('tnxp_calendarbtntxt_clr');
    }

    public function set_confirmation_calendar_button_text_color($value) {
        return $this->set_field('tnxp_calendarbtntxt_clr', $value);
    }

    public function get_confirmation_calendar_button_background_color() {
        return $this->get_color('tnxp_calendarbtnbckg_clr');
    }

    public function set_confirmation_calendar_button_background_color($value) {
        return $this->set_field('tnxp_calendarbtnbckg_clr', $value);
    }

    public function get_confirmation_calendar_button_border_color() {
        return $this->get_color('tnxp_calendarbtnborder_clr');
    }

    public function set_confirmation_calendar_button_border_color($value) {
        return $this->set_field('tnxp_calendarbtnborder_clr', $value);
    }

    public function get_email_questions_to_host() {
        $page = $this->get_live_page_prefix();
        return $this->get_field("{$page}askq_send_email_yn") == 'yes';
    }

    public function get_email_questions_address() {
        $page = $this->get_live_page_prefix();
        return $this->get_field("{$page}askq_send_email");
    }

    public function get_live_page_header_script_tag() {
        return '';
    }

    public function get_live_page_body_script_tag() {
        return '';
    }

    public function get_registration_page_head_script_tag() {
    }

    public function get_registration_page_body_script_tag() {
    }

    public function get_registration_page_template() {
        return $this->get_field('regp_page_layout', 'classic');
    }

    public function set_registration_page_template($value) {
        return $this->set_field('regp_page_layout', $value);
    }

    public function get_automated_replay_enabled() {
        return (bool) $this->get_field('automated_replay_enabled', false);
    }

    public function set_automated_replay_enabled($value) {
        $this->set_field('automated_replay_enabled', $value);
    }

    public function get_automated_replay_available_duration() {
        return (int) $this->get_field('automated_replay_available_duration', 0);
    }

    public function set_automated_replay_available_duration($value) {
        $this->set_field('automated_replay_available_duration', $value);
    }

    public function get_featured_image_url() {
        return get_the_post_thumbnail_url($this->id);
    }

    public function get_featured_image_id() {
        return get_post_thumbnail_id($this->id);
    }

    public function set_featured_image_id($image_id) {
        if ($image_id == null) {
            delete_post_thumbnail($this->id);
        } else {
            set_post_thumbnail($this->id, $image_id);
        }
    }

    public function get_email_from_name() {
        return $this->get_field('email_from_name', '');
    }

    public function set_email_from_name($value) {
        return $this->set_field('email_from_name', $value);
    }

    public function get_email_from_address() {
        return $this->get_field('email_from_address', '');
    }

    public function set_email_from_address($value) {
        return $this->set_field('email_from_address', $value);
    }

    public function get_admin_email_address() {
        return $this->get_field('admin_email_address', '');
    }

    public function set_admin_email_address($value) {
        return $this->set_field('admin_email_address', $value);
    }

    public function set_registration_name_mode($value) {
        return $this->set_config('registration_name_mode', $value);
    }

    public function get_registration_name_mode() {
        return $this->get_config('registration_name_mode', 'required');
    }

    public function set_registration_email_mode($value) {
        return $this->set_config('registration_email_mode', $value);
    }

    public function get_registration_email_mode() {
        return $this->get_config('registration_email_mode', 'required');
    }

    protected function get_webinar_page_settings($page)
    {
        return [
            'page_template' => $this->get_field("{$page}p_page_template"),
            'media_type' => $this->get_field("{$page}p_vidurl_type"),
            'media_url' => $this->get_field("{$page}p_vidurl"),
            'media_settings' => [
                'autoplay' => $this->get_field("{$page}p_video_auto_play_yn") == 'yes',
                'show_controls' => $this->get_field("{$page}p_video_controls_yn") == 'yes',
                'show_big_button' => $this->get_field("{$page}p_fullscreen_control") == 'yes',
                'simulate_live_video' => $this->get_field("{$page}p_simulate_video_yn") == 'yes'
            ],
            'hide_title' => $this->get_field("{$page}p_title_show_yn") == 'yes',
            'title_color' => $this->get_color("{$page}p_title_clr"),
            'background_color' => $this->get_color("{$page}p_bckg_clr"),
            'background_image_url' => $this->get_color("{$page}p_bckg_img"),
            'banner_background_color' => $this->get_color("{$page}p_banner_background_color"),
            'host_background_color' => $this->get_color("{$page}p_leftbox_bckg_clr"),
            'host_border_color' => $this->get_color("{$page}p_leftbox_border_clr"),
            'show_host_box' => $this->get_field("{$page}p_hostbox_yn") == 'yes',
            'host_title_background' => $this->get_color("{$page}p_hostbox_title_bckg_clr"),
            'host_title_text_color' => $this->get_color("{$page}p_hostbox_title_text_clr"),
            'host_text_color' => $this->get_color("{$page}p_hostbox_content_text_clr"),
            'show_description_box' => $this->get_field("{$page}p_webdes_yn") == 'yes',
            'description_title_background' => $this->get_color("{$page}p_descbox_title_bckg_clr"),
            'description_title_text_color' => $this->get_color("{$page}p_descbox_title_text_clr"),
            'description_text_color' => $this->get_color("{$page}p_descbox_content_text_clr"),
            'header_script_tag' => $this->get_field("{$page}p_script_head"),
            'body_script_tag' => $this->get_field("{$page}p_script_body"),
            'show_question_box' => $this->get_field("{$page}p_askq_yn") == 'yes',
            'email_questions' => $this->get_field("{$page}p_askq_send_email_yn") == 'yes',
            'email_questions_address' => $this->get_field("{$page}p_askq_send_email"),
            'question_visibility' => $this->get_field("{$page}p_askq_question_visibility"),
            'question_background_color' => $this->get_color("{$page}p_askq_bckg_clr"),
            'question_border_color' => $this->get_color("{$page}p_askq_border_clr"),
            'question_title_text_color' => $this->get_color("{$page}p_askq_title_text_clr"),
            'question_border_radius' => $this->get_field("{$page}p_button_radius"),
            'question_button_background_color' => $this->get_color("{$page}p_button_bg_clr"),
            'question_button_border_color' => $this->get_color("{$page}p_button_border_clr"),
            'question_button_text_color' => $this->get_color("{$page}p_button_text_clr"),
            'question_button_hover_background_color' => $this->get_color("{$page}p_buttonhover_bg_clr"),
            'question_button_hover_border_color' => $this->get_color("{$page}p_buttonhover_border_clr"),
            'question_button_hover_text_color' => $this->get_color("{$page}p_buttonhover_text_clr"),
            'enable_chat' => $this->get_field("{$page}p_show_chatbox") == 'yes',
            'chat_background_color' => $this->get_color("{$page}p_chtb_bckg_clr"),
            'chat_border_color' => $this->get_color("{$page}p_chtb_border_clr"),
            'chat_title_text_color' => $this->get_color("{$page}p_chtb_title_text_clr"),
            'chat_show_timestamps' => $this->get_field("{$page}p_show_chatbox_timestmp") == 'yes',
            'chat_button_background_color' => $this->get_color("{$page}p_bgclr_chatbtn"),
            'chat_button_text_color' => $this->get_color("{$page}p_txtclr_chatbtn"),
            'question_tab_title' => $this->get_field("{$page}p_chtb_quebox_title"),
            'question_tab_title_text_color' => $this->get_color("{$page}p_chtb_quebox_title_text_clr"),
            'question_tab_background_color' => $this->get_color("{$page}p_chtb_quebox_bkg_text_clr"),
            'question_tab_border_color' => $this->get_color("{$page}p_chtb_quebox_border_clr"),
            'chat_tab_title' => $this->get_field("{$page}p_chtb_chat_title"),
            'chat_tab_title_text_color' => $this->get_color("{$page}p_chtb_chat_title_text_clr"),
            'chat_tab_background_color' => $this->get_color("{$page}p_chtb_chat_bkg_text_clr"),
            'chat_tab_border_color' => $this->get_color("{$page}p_chtb_chat_border_clr"),
            'enable_incentive' => $this->get_field("{$page}p_incentive_yn"),
            'incentive_background_color' => $this->get_color("{$page}p_incentive_bckg_clr"),
            'incentive_border_color' => $this->get_color("{$page}p_incentive_border_clr"),
            'incentive_title' => $this->get_color("{$page}p_incentive_title"),
            'incentive_title_text_color' => $this->get_color("{$page}p_incentive_title_clr"),
            'incentive_title_background_color' => $this->get_color("{$page}p_incentive_title_bckg_clr"),
            'incentive_content_text_color' => $this->get_color("{$page}p_incentive_content_clr"),
            'incentive_content' => $this->get_field("{$page}p_incentive_content"),
            'enable_action_box' => $this->get_field("{$page}p_show_actionbox"),
            'action_box_hand_color' => $this->get_color("{$page}p_action_raise_hand_clr"),
            'action_box_hand_hover_color' => $this->get_color("{$page}p_action_raise_hand_hover_clr"),
            'action_box_hand_active_color' => $this->get_color("{$page}p_action_raise_hand_act_clr"),
            'action_box_background_color' => $this->get_color("{$page}p_action_bckg_clr"),
            'action_box_border_color' => $this->get_color("{$page}p_action_box_border_clr"),
            'cta_activation_type' => $this->get_field("{$page}p_call_action"),
            'enable_cta' => $this->get_field("{$page}p_manual_show_cta") == 'yes',
            'cta_show_after_mins' => (int) $this->get_field("{$page}p_cta_show_after"),
            'cta_time_limited' => $this->get_field("{$page}p_cta_time_limited") == 'yes',
            'cta_show_for_minutes' => (int) $this->get_field("{$page}p_cta_show_for_minutes"),
            'cta_type' => $this->get_field("{$page}p_call_action_ctatype"),
            'cta_html_background_color' => $this->get_color("{$page}p_ctatxt_fld_bckg_clr"),
            'cta_html_border_color' => $this->get_color("{$page}p_ctatxt_fld_border_clr"),
            'cta_html_text_color' => $this->get_color("{$page}p_ctatxt_fld_content_clr"),
            'cta_html_content' => $this->get_field("{$page}p_ctatxt_txt"),
            'cta_button_background_color' => $this->get_color("{$page}p_ctabtn_clr"),
            'cta_button_text_color' => $this->get_color("{$page}p_ctabtn_txt_clr"),
            'cta_headline' => $this->get_field("{$page}p_cta_headline"),
            'cta_subheading' => $this->get_field("{$page}p_cta_subheading"),
            'cta_button_url' => $this->get_field("{$page}p_ctabtn_url"),
            'cta_button_text' => $this->get_field("{$page}p_ctabtn_txt"),
            'enable_attendees' => $this->get_field("{$page}p_show_attendees_yn") == 'yes',
            'enable_hand_raising' => $this->get_field("{$page}p_hand_raising_yn") == 'yes',
            'page_params' =>  $this->get_object("{$page}p_page_params"),
            'custom_css' => $this->get_field("{$page}p_page_css"),
        ];
    }

    protected function set_webinar_page_settings($page, $settings) {
        $this->set_field("{$page}p_page_template", $settings->page_template);
        $this->set_field("{$page}p_vidurl_type", $settings->media_type);
        $this->set_field("{$page}p_vidurl", $settings->media_url);
        $this->set_field("{$page}p_vidurl", $settings->media_url);

        $this->set_field("{$page}p_title_show_yn", $settings->hide_title ?  'yes' : '');
        $this->set_field("{$page}p_title_clr", $settings->title_color);
        $this->set_field("{$page}p_bckg_clr", $settings->background_color);
        $this->set_field("{$page}p_bckg_img", $settings->background_image_url);
        $this->set_field("{$page}p_banner_background_color", $settings->banner_background_color);

        $this->set_field("{$page}p_leftbox_bckg_clr", $settings->host_background_color);
        $this->set_field("{$page}p_leftbox_border_clr", $settings->host_border_color);

        $this->set_field("{$page}p_hostbox_yn", $settings->show_host_box ? 'yes' : '');
        $this->set_field("{$page}p_hostbox_title_bckg_clr", $settings->host_title_background);
        $this->set_field("{$page}p_hostbox_title_text_clr", $settings->host_title_text_color);
        $this->set_field("{$page}p_hostbox_content_text_clr", $settings->host_background_color);

        $this->set_field("{$page}p_webdes_yn", $settings->show_description_box ? 'yes' : '');
        $this->set_field("{$page}p_descbox_title_bckg_clr", $settings->description_title_background);
        $this->set_field("{$page}p_descbox_title_text_clr", $settings->description_title_text_color);
        $this->set_field("{$page}p_descbox_content_text_clr", $settings->description_text_color);

        $this->set_field("{$page}p_script_head", $settings->header_script_tag);
        $this->set_field("{$page}p_script_body", $settings->body_script_tag);

        $media_settings = $settings->media_settings;
        $this->set_field("{$page}p_video_auto_play_yn", $media_settings->autoplay ? 'yes' : '');
        $this->set_field("{$page}p_video_controls_yn", $media_settings->show_controls ? 'yes' : '');
        $this->set_field("{$page}p_fullscreen_control", $media_settings->show_big_button ? 'yes' : '');
        $this->set_field("{$page}p_simulate_video_yn", $media_settings->simulate_live_video ? 'yes' : '');

        $this->set_field("{$page}p_askq_yn", $settings->show_question_box ? 'yes' : '');
        $this->set_field("{$page}p_askq_send_email_yn", $settings->email_questions ? 'yes' : '');
        $this->set_field("{$page}p_askq_send_email", $settings->email_questions_address);
        $this->set_field("{$page}p_askq_question_visibility", $settings->question_visibility);
        $this->set_field("{$page}p_askq_bckg_clr", $settings->question_background_color);
        $this->set_field("{$page}p_askq_border_clr", $settings->question_border_color);
        $this->set_field("{$page}p_askq_title_text_clr", $settings->question_title_text_color);
        $this->set_field("{$page}p_button_radius", $settings->question_border_radius);
        $this->set_field("{$page}p_button_bg_clr", $settings->question_button_background_color);
        $this->set_field("{$page}p_button_border_clr", $settings->question_button_border_color);
        $this->set_field("{$page}p_button_text_clr", $settings->question_button_text_color);
        $this->set_field("{$page}p_buttonhover_bg_clr", $settings->question_button_hover_background_color);
        $this->set_field("{$page}p_buttonhover_border_clr", $settings->question_button_hover_border_color);
        $this->set_field("{$page}p_buttonhover_text_clr", $settings->question_button_hover_text_color);

        $this->set_field("{$page}p_show_chatbox", $settings->enable_chat ? 'yes' : '');
        $this->set_field("{$page}p_chtb_bckg_clr", $settings->chat_background_color);
        $this->set_field("{$page}p_chtb_border_clr", $settings->chat_border_color);
        $this->set_field("{$page}p_chtb_title_text_clr", $settings->chat_title_text_color);
        $this->set_field("{$page}p_show_chatbox_timestmp", $settings->chat_show_timestamps ? 'yes' : '');
        $this->set_field("{$page}p_bgclr_chatbtn", $settings->chat_button_background_color);
        $this->set_field("{$page}p_txtclr_chatbtn", $settings->chat_button_text_color);

        $this->set_field("{$page}p_chtb_quebox_title", $settings->question_tab_title);
        $this->set_field("{$page}p_chtb_quebox_title_text_clr", $settings->question_tab_title_text_color);
        $this->set_field("{$page}p_chtb_quebox_bkg_text_clr", $settings->question_tab_background_color);
        $this->set_field("{$page}p_chtb_quebox_border_clr", $settings->question_tab_border_color);

        $this->set_field("{$page}p_chtb_chat_title", $settings->chat_tab_title);
        $this->set_field("{$page}p_chtb_chat_title_text_clr", $settings->chat_tab_title_text_color);
        $this->set_field("{$page}p_chtb_chat_bkg_text_clr", $settings->chat_tab_background_color);
        $this->set_field("{$page}p_chtb_chat_border_clr", $settings->chat_tab_border_color);

        $this->set_field("{$page}p_incentive_yn", $settings->enable_incentive ? 'yes' : '');
        $this->set_field("{$page}p_incentive_bckg_clr", $settings->incentive_background_color);
        $this->set_field("{$page}p_incentive_border_clr", $settings->incentive_border_color);
        $this->set_field("{$page}p_incentive_title", $settings->incentive_title);
        $this->set_field("{$page}p_incentive_title_clr", $settings->incentive_title_text_color);
        $this->set_field("{$page}p_incentive_title_bckg_clr", $settings->incentive_title_background_color);
        $this->set_field("{$page}p_incentive_content_clr", $settings->incentive_content_text_color);
        $this->set_field("{$page}p_incentive_content", $settings->incentive_content);

        $this->set_field("{$page}p_show_actionbox", $settings->enable_action_box ? 'yes' : '');
        $this->set_field("{$page}p_action_raise_hand_clr", $settings->action_box_hand_color);
        $this->set_field("{$page}p_action_raise_hand_hover_clr", $settings->action_box_hand_hover_color);
        $this->set_field("{$page}p_action_raise_hand_act_clr", $settings->action_box_hand_active_color);
        $this->set_field("{$page}p_action_bckg_clr", $settings->action_box_background_color);
        $this->set_field("{$page}p_action_box_border_clr", $settings->action_box_border_color);

        $this->set_field("{$page}p_call_action", $settings->cta_activation_type);
        $this->set_field("{$page}p_manual_show_cta", $settings->enable_cta ? 'yes' : '');
        $this->set_field("{$page}p_cta_show_after", $settings->cta_show_after_mins);
        $this->set_field("{$page}p_cta_time_limited", $settings->cta_time_limited ? 'yes' : '');
        $this->set_field("{$page}p_cta_show_for_minutes", $settings->cta_show_for_minutes);
        $this->set_field("{$page}p_call_action_ctatype", $settings->cta_type);
        $this->set_field("{$page}p_ctatxt_fld_bckg_clr", $settings->cta_html_background_color);
        $this->set_field("{$page}p_ctatxt_fld_border_clr", $settings->cta_html_border_color);
        $this->set_field("{$page}p_ctatxt_fld_content_clr", $settings->cta_html_text_color);
        $this->set_field("{$page}p_ctatxt_txt", $settings->cta_html_content);
        $this->set_field("{$page}p_ctabtn_clr", $settings->cta_button_background_color);
        $this->set_field("{$page}p_ctabtn_txt_clr", $settings->cta_button_text_color);
        $this->set_field("{$page}p_cta_headline", $settings->cta_headline);
        $this->set_field("{$page}p_cta_subheading", $settings->cta_subheading);
        $this->set_field("{$page}p_ctabtn_url", $settings->cta_button_url);
        $this->set_field("{$page}p_ctabtn_txt", $settings->cta_button_text);
        $this->set_field("{$page}p_show_attendees_yn", $settings->enable_attendees ? 'yes' : '');
        $this->set_field("{$page}p_hand_raising_yn", $settings->enable_hand_raising ? 'yes' : '');
        $this->set_field("{$page}p_page_params", $settings->page_params);
        $this->set_field("{$page}p_page_css",$settings->custom_css);
    }

    public function get_params() {
        $recurring_settings = $this->get_recurring_settings();
        $jit_settings = $this->get_jit_settings();
        return [
            // temp
            'config' => $this->config,
            'id' => $this->id,
            'general' => [
                'name' => get_the_title($this->id),
                'description' => $this->get_description(),
                'hosts' => $this->get_host(),
                'type' => $this->get_type(),
                'url' => $this->get_url(),
                'slug' => $this->get_slug(),
                'exit_url' => $this->get_exit_url(),
                'purchase_url' => $this->get_purchase_url(),
                'is_published' => $this->is_published(),
                'status' => $this->get_status(),
                'is_automated' => $this->is_automated(),
                'is_recurring' => $this->is_recurring(),
                'duration' => $this->get_duration(),
                'timezone_offset' => $this->get_timezone_offset(),
                'timezone' => $this->get_selected_timezone(),
                'is_paid' => $this->is_paid(),
                'price' => $this->get_price(),
                'is_registration_disabled' => $this->is_registration_disabled(),
                'allow_anonymous_registration' => $this->allow_anonymous_registration(),
                'allow_standard_registration' => $this->allow_standard_registration(),
                'is_password_protected' => $this->is_password_protected(),
                'password' => $this->get_password(),
                'is_manual' => $this->is_manual(),
                'is_jit' => $this->is_jit(),
                'is_right_now' => $this->is_right_now(),
                'recurring_days' => $recurring_settings['days'],
                'recurring_times' => $recurring_settings['times'],
                'jit_days' => $jit_settings['days'],
                'jit_interval' => $jit_settings['interval'],
                'one_time_date' => $this->get_one_time_date(),
                'automated_offset' => $this->get_automated_offset(),
                'one_time_hour' => $this->get_one_time_hour(),
                'one_time_minute' => $this->get_one_time_minute(),
                'live_media_type' => $this->get_live_media_type(),
                'live_media_url' => $this->get_live_media_url(),
                'allow_auto_registration' => $this->allow_auto_registration(),
                'auto_register_for_currently_running_webinar' => $this->auto_register_for_currently_running_webinar(),
                'auto_register_wp_users' => $this->auto_register_wp_users(),
                'is_gdpr_optin_enabled' => $this->is_gdpr_enabled(),
                'gdpr_optin_text' => $this->get_gdpr_optin_text(),
                'timeslots_to_show' => $this->get_timeslots_to_show(0),
                'edit_post_url' => get_edit_post_link($this->id, 'json'),
                'after_webinar_action' => $this->get_after_webinar_action(),
                'after_webinar_redirect_url' => $this->get_after_webinar_redirect_url(),
                'is_email_confirmation_required' => $this->is_email_confirmation_required(),
                'featured_image_id' => $this->get_featured_image_id(),
                'featured_image_url' => $this->get_featured_image_url(),
                'email_confirmation_required_action' => $this->get_email_confirmation_required_action(),
                'email_confirmation_required_redirect_page_id' => $this->get_email_confirmation_required_redirect_page_id(),
                'meta_keywords' => $this->get_meta_keywords(),
                'meta_description' => $this->get_meta_description(),
                'meta_title' => $this->get_meta_title(),
                'open_graph_title' => $this->get_open_graph_title(),
                'open_graph_description' => $this->get_open_graph_description(),
                'open_graph_image_url' => $this->get_open_graph_image_url(),
            ],
            'registration' => [
                'page_template' => $this->get_registration_page_template(),
                'custom_fields' => $this->get_custom_fields(),
                'background_image_url' => $this->get_registration_background_image_url(),
                'background_color' => $this->get_registration_background_color(),
                'header_script_tag' => $this->get_registration_header_script_tag(),
                'body_script_tag' => $this->get_registration_body_script_tag(),
                'show_content_section' => $this->get_registration_show_page_section(),
                'content_media_type' => $this->get_registration_media_type(),
                'content_media_url' => $this->get_registration_media_url(),
                'content_media_settings' => [
                    'autoplay' => $this->get_field('regp_video_auto_play_yn') == 'yes',
                    'show_controls' => $this->get_field('regp_video_controls_yn') == 'yes',
                    'show_big_button' => $this->get_field('regp_bigplaybtn_yn') == 'yes',
                ],
                'has_custom_registration_page' => $this->has_custom_registration_page(),
                'custom_page_id' => $this->get_custom_registration_page_id(),
                'title_color' => $this->get_registration_title_color(),
                'date_time_color' => $this->get_registration_date_time_color(),
                'hide_registration_form' => $this->is_registration_register_form_hidden(),
                'register_title' => $this->get_registration_register_title(),
                'register_text' => $this->get_registration_register_text(),
                'register_footer' => $this->get_registration_register_footer(),
                'register_button_text' => $this->get_registration_register_button_text(),
                'register_button_background_color' => $this->get_registration_register_button_background_color(),
                'register_button_border_color' => $this->get_registration_register_button_border_color(),
                'register_button_text_color' => $this->get_registration_register_button_text_color(),
                'hide_login_form' => $this->is_registration_login_form_hidden(),
                'login_title' => $this->get_registration_login_title(),
                'login_text' => $this->get_registration_login_text(),
                'login_button_text' => $this->get_registration_login_button_text(),
                'login_button_background_color' => $this->get_registration_login_button_background_color(),
                'login_button_border_color' => $this->get_registration_login_button_border_color(),
                'login_button_text_color' => $this->get_registration_login_button_text_color(),
                'show_description' => $this->is_registration_show_description(),
                'description_text_color' => $this->get_registration_description_text_color(),
                'description_background_color' => $this->get_registration_description_background_color(),
                'description_border_color' => $this->get_registration_description_border_color(),
                'reg_form_background_color' => $this->get_registration_reg_form_background_color(),
                'reg_form_border_color' => $this->get_registration_reg_form_border_color(),
                'reg_form_font_color' => $this->get_registration_reg_form_font_color(),
                'reg_form_tab_background_color' => $this->get_registration_reg_form_tab_background_color(),
                'reg_form_tab_text_color' => $this->get_registration_reg_form_tab_text_color(),
                'reg_form_tab_registration_text' => $this->get_registration_reg_form_tab_registration_text(),
                'reg_form_tab_login_text' => $this->get_registration_reg_form_tab_login_text(),
                'ticket_title_text' => $this->get_registration_ticket_title_text(),
                'ticket_link_text' => $this->get_registration_ticket_link_text(),
                'ticket_description' => $this->get_registration_ticket_description(),
                'ticket_thank_you_message' => $this->get_registration_ticket_thank_you_message(),
                'registration_page_params' => $this->get_registration_page_params(),
                'maximum_attendee_enabled' => $this->get_maximum_attendee_enabled(),
                'maximum_attendee_count' => $this->get_maximum_attendee_count(),
                'name_mode' => $this->get_registration_name_mode(),
                'email_mode' => $this->get_registration_email_mode()
            ],
            'mailinglist' => [
                'provider' => $this->get_mail_provider(),
                'account_id' => $this->get_mail_account_id(),
                'list_id' => $this->get_mail_list_id(),
            ],
            'access' => [
                'type' => $this->get_access_type(),
                'roles' => $this->get_access_roles(),
                'redirect_page_id' => $this->get_access_redirect_page_id(),
                'user_ids' => $this->get_access_user_ids(),
                'wc_membership_level_id' => $this->get_access_wc_membership_level_ids()
            ],
            'countdown' => [
                'background_image_url' => $this->get_countdown_background_image_url(),
                'background_color' => $this->get_countdown_background_color(),
                'title_color' => $this->get_countdown_title_color(),
                'tagline_color' => $this->get_countdown_tagline_color(),
                'description_color' => $this->get_countdown_description_color(),
                'is_timer_visible' => $this->is_countdown_timer_visible(),
                'header_script_tag' => $this->get_countdown_header_script_tag(),
                'body_script_tag' => $this->get_countdown_body_script_tag(),
                'page_settings' => $this->get_countdown_page_settings(),
            ],
            'confirmation' => [
                'is_disabled' => $this->is_confirmation_page_disabled(),
                'has_custom_page' => $this->has_custom_confirmation_page(),
                'custom_page_id' => $this->get_custom_confirmation_page_id(),
                'title_text' => $this->get_confirmation_title_text(),
                'title_color' => $this->get_confirmation_title_color(),
                'background_color' => $this->get_confirmation_background_color(),
                'background_image_url' => $this->get_confirmation_background_image_url(),
                'media_type' => $this->get_confirmation_media_type(),
                'media_url' => $this->get_confirmation_media_url(),
                'media_settings' => [
                    'autoplay' => $this->get_confirmation_media_autoplay(),
                    'show_controls' => $this->get_confirmation_media_show_controls(),
                    'show_big_button' => $this->get_confirmation_media_show_big_button(),
                ],
                'header_script_tag' => $this->get_confirmation_header_script_tag(),
                'body_script_tag' => $this->get_confirmation_body_script_tag(),
                'link_above_text_color' => $this->get_confirmation_link_above_text_color(),
                'link_below_text_color' => $this->get_confirmation_link_below_text_color(),
                'ticket_border_color1' => $this->get_confirmation_ticket_border_color1(),
                'ticket_border_color2' => $this->get_confirmation_ticket_border_color2(),
                'ticket_body_text_color' => $this->get_confirmation_ticket_body_text_color(),
                'ticket_body_background_color' => $this->get_confirmation_ticket_body_background_color(),
                'ticket_header_background_color' => $this->get_confirmation_ticket_header_background_color(),
                'ticket_header_text_color' => $this->get_confirmation_ticket_header_text_color(),
                'ticket_button_color' => $this->get_confirmation_ticket_button_color(),
                'ticket_button_text_color' => $this->get_confirmation_ticket_button_text_color(),
                'is_social_sharing_enabled' => $this->get_confirmation_is_social_sharing_enabled(),
                'social_sharing_border_color' => $this->get_confirmation_social_sharing_border_color(),
                'social_sharing_background_color' => $this->get_confirmation_social_sharing_background_color(),
                'calendar_border_color' => $this->get_confirmation_calendar_border_color(),
                'calendar_background_color' => $this->get_confirmation_calendar_background_color(),
                'calendar_text_color' => $this->get_confirmation_calendar_text_color(),
                'calendar_button_text_color' => $this->get_confirmation_calendar_button_text_color(),
                'calendar_button_background_color' => $this->get_confirmation_calendar_button_background_color(),
                'calendar_button_border_color' => $this->get_confirmation_calendar_button_border_color(),
                'page_params' => $this->get_confirmation_page_params(),
                'page_template' => $this->get_confirmation_page_template(),
            ],
            'live' => $this->get_webinar_page_settings('live'),
            'replay' => $this->get_webinar_page_settings('replay'),
            'emails' => [
                'from_name' => $this->get_email_from_name(),
                'from_email' => $this->get_email_from_address(),
                'admin_email_address' => $this->get_admin_email_address(),
                'types' => [
                    'new_registration' => $this->get_email_template_options('newreg'),
                    'reg_confirmation' => $this->get_email_template_options('regconfirm'),
                    'day_before' => $this->get_email_template_options('24hrb4'),
                    'hour_before' => $this->get_email_template_options('1hrb4'),
                    'starting' => $this->get_email_template_options('wbnstarted'),
                    'replay' => $this->get_email_template_options('wbnreplay'),
                    'order_complete' => $this->get_email_template_options('order_complete'),
                    'double_optin' => $this->get_email_template_options('double_optin')
                ]
            ],
            'automated_replay' => [
                'enabled' => $this->get_automated_replay_enabled(),
                'available_duration' => $this->get_automated_replay_available_duration()
            ]
        ];
    }

    // get email settings
    public function get_email_template_options($type) {
        $emails = $this->get_field('emails', []);

        if (!isset($emails[$type])) {
            $settings = WebinarSysteemSettings::instance();
            $defaults = $settings->get_default_email_templates()[$type];

            return (object) [
                'inherit' => true,
                'enabled' => true,
                'subject' => $defaults->subject,
                'content' => apply_filters('meta_content', $defaults->content),
            ];
        }

        return (object) $emails[$type];
    }

    public function set_email_template_options($type, $email_settings) {
        $emails = $this->get_field('emails', []);
        $emails[$type] = $email_settings;
        $this->set_field('emails', $emails);
    }

    public function set_type($type) {
        // set the type
        switch ($type) {
            case 'manual':
                $this->set_field('gener_air_type', 'live');
                break;

            case 'onetime':
                $this->set_field('gener_air_type', 'rec');
                $this->set_field('gener_time_occur', 'one');
                $this->set_status('cou');
                break;

            case 'recurring':
                $this->set_field('gener_air_type', 'rec');
                $this->set_field('gener_time_occur', 'recur');
                $this->set_status('cou');
                break;

            case 'jit':
                $this->set_field('gener_air_type', 'rec');
                $this->set_field('gener_time_occur', 'jit');
                $this->set_status('cou');
                break;

            case 'now':
                $this->set_field('gener_air_type', 'rec');
                $this->set_field('gener_time_occur', 'recur');
                $this->set_json('gener_rec_times', ['rightnow']);
                $this->set_status('cou');
                break;
        }
    }

    public function set_duration($duration) {
        $this->set_field('gener_duration', $duration);
    }

    public function set_recurring_days($days) {
        $this->set_json('gener_rec_days', $days);
    }

    public function set_recurring_times($times) {
        $this->set_json('gener_rec_times', $times);
    }

    public function set_jit_days($days) {
        $this->set_json('gener_jit_days', $days);
    }

    public function set_jit_interval($times) {
        $this->set_json('gener_jit_times', $times);
    }

    public function set_automated_offset($offset) {
        $this->set_field('automated_offset', $offset);
    }

    public function get_automated_offset() {
        return (int) $this->get_field('automated_offset', 0);
    }

    public function get_mail_provider() {
        return $this->get_field('default_mail_provider', 'none');
    }

    public function set_mail_provider($value) {
        return $this->set_field('default_mail_provider', $value);
    }

    public function set_mail_account_id($provider, $value) {
        switch ($provider) {
            case 'drip':
                return $this->set_field('drip_accounts', $value);

            case 'none':
            default:
                return null;
        }
    }

    public function set_mail_list_id($provider, $value) {
        switch ($provider) {
            case 'none':
                return null;

            case 'drip':
                return $this->set_field('drip_campaigns', $value);

            case 'convertkit':
                return $this->set_field('convertkit_form', $value);

            default:
                return $this->set_field($provider.'_list', $value);
        }
    }

    function set_registration_content_autoplay($value) {
        $this->set_field('regp_video_auto_play_yn', $value ? 'yes' : '');
    }

    function get_registration_content_autoplay() {
        return $this->get_field('regp_video_auto_play_yn') == 'yes';
    }

    function set_registration_content_controls($value) {
        $this->set_field('regp_video_controls_yn', $value ? 'yes' : '');
    }

    function get_registration_content_controls() {
        return $this->get_field('regp_video_controls_yn') == 'yes';
    }

    function set_registration_content_big_button($value) {
        $this->set_field('regp_bigplaybtn_yn', $value ? 'yes' : '');
    }

    function get_registration_content_big_button() {
        return $this->get_field('regp_bigplaybtn_yn') == 'yes';
    }

    public static function create_empty_webinar($name) {
        $post_id = wp_insert_post([
            'post_title' => $name,
            'post_content' => 'dummy-content',
            'post_name' => sanitize_title_with_dashes($name),
            'post_status' => 'draft',
            'post_type' => self::$post_type,
        ]);

        return $post_id;
    }

    public function get_registration_page_params() {
        $params = $this->get_field('regp_registration_page_params', null);

        return $params == ''
            ? (object) []
            : $params;
    }

    public function set_registration_page_params($params) {
        $this->set_field('regp_registration_page_params', $params);
    }

    public function get_maximum_attendee_enabled() {
        return (bool) $this->get_field('regp_maximum_attendee_enabled', false);
    }

    public function set_maximum_attendee_enabled($value) {
        $this->set_field('regp_maximum_attendee_enabled', $value);
    }

    public function get_maximum_attendee_count() {
        return (int) $this->get_field('regp_maximum_attendee_count', 0);
    }

    public function set_maximum_attendee_count($value) {
        $this->set_field('regp_maximum_attendee_count', $value);
    }

    public function get_confirmation_page_params() {
        $params = $this->get_field('tnxp_page_params', null);

        return $params == ''
            ? (object) []
            : $params;
    }

    public function set_confirmation_page_params($params) {
        $this->set_field('tnxp_page_params', $params);
    }

    public function get_confirmation_page_template() {
        return $this->get_field('tnxp_page_layout', 'classic');
    }

    public function set_confirmation_page_template($value) {
        return $this->set_field('tnxp_page_layout', $value);
    }

    public function update_from_params($params) {
        $general = $params->general;
        $registration = $params->registration;
        $mailinglist = $params->mailinglist;
        $access = $params->access;
        $countdown = $params->countdown;
        $confirmation = $params->confirmation;
        // update main settings
        $updates = array(
            'ID' => $this->id,
            'post_title' => $general->name,
            'post_content' => $general->description,
        );
        wp_update_post($updates);

        // general settings
        $this->set_host($general->hosts);
        $this->set_duration($general->duration);
        $this->set_recurring_days($general->recurring_days);
        $this->set_recurring_times($general->recurring_times);
        $this->set_jit_days($general->jit_days);
        $this->set_jit_interval($general->jit_interval);
        $this->set_timeslots_to_show($general->timeslots_to_show);
        $this->set_automated_offset($general->automated_offset);

        $this->set_one_time_date($general->one_time_date);
        $this->set_one_time_hour($general->one_time_hour);
        $this->set_one_time_minute($general->one_time_minute);

        $this->set_selected_timezone($general->timezone);
        $this->set_status($general->status);

        $this->set_is_paid($general->is_paid);
        $this->set_price($general->price);

        $this->set_exit_url($general->exit_url);

        $this->set_live_media_type($general->live_media_type);
        $this->set_live_media_url($general->live_media_url);
        $this->set_registration_disabled($general->is_registration_disabled);
        $this->set_allow_anonymous_registration($general->allow_anonymous_registration);
        $this->set_allow_standard_registration($general->allow_standard_registration);
        $this->set_allow_auto_registration($general->allow_auto_registration);
        $this->set_auto_register_wp_users($general->auto_register_wp_users);
        $this->set_auto_register_for_currently_running_webinar($general->auto_register_for_currently_running_webinar);
        $this->set_is_password_protected($general->is_password_protected);
        $this->set_password($general->password);
        

        // After the webinar finishes
        $this->set_after_webinar_action($general->after_webinar_action);
        $this->set_after_webinar_redirect_url($general->after_webinar_redirect_url);

        $this->set_custom_fields($registration->custom_fields);

        $this->set_mail_provider($mailinglist->provider);
        $this->set_mail_account_id($mailinglist->provider, $mailinglist->account_id);
        $this->set_mail_list_id($mailinglist->provider, $mailinglist->list_id);

        $this->set_access_type($access->type);
        $this->set_access_redirect_page_id($access->redirect_page_id);
        $this->set_access_roles($access->roles);
        $this->set_access_user_ids($access->user_ids);
        $this->set_access_wc_membership_level_ids($access->wc_membership_level_id);

        $this->set_gdpr_enabled($general->is_gdpr_optin_enabled);
        $this->set_gdpr_optin_text($general->gdpr_optin_text);
        $this->set_email_confirmation_required($general->is_email_confirmation_required);
        $this->set_email_confirmation_required_action($general->email_confirmation_required_action);
        $this->set_email_confirmation_required_redirect_page_id($general->email_confirmation_required_redirect_page_id);

        // SEO
        $this->set_meta_keywords($general->meta_keywords);
        $this->set_meta_description($general->meta_description);
        $this->set_meta_title($general->meta_title);
        $this->set_open_graph_title($general->open_graph_title);
        $this->set_open_graph_description($general->open_graph_description);
        $this->set_open_graph_image_url($general->open_graph_image_url);

        // set the featured image
        $this->set_featured_image_id($general->featured_image_id);

        // countdown page
        $this->set_countdown_background_image_url($countdown->background_image_url);
        $this->set_countdown_background_color($countdown->background_color);
        $this->set_countdown_description_color($countdown->description_color);
        $this->set_countdown_title_color($countdown->title_color);
        $this->set_countdown_tagline_color($countdown->tagline_color);
        $this->set_countdown_timer_visible($countdown->is_timer_visible);
        $this->set_countdown_header_script_tag($countdown->header_script_tag);
        $this->set_countdown_body_script_tag($countdown->body_script_tag);
        $this->set_countdown_page_settings($countdown->page_settings);

        // registration
        $this->set_registration_page_template($registration->page_template);
        $this->set_registration_background_image_url($registration->background_image_url);
        $this->set_registration_background_color($registration->background_color);
        $this->set_registration_header_script_tag($registration->header_script_tag);
        $this->set_registration_body_script_tag($registration->body_script_tag);
        $this->set_registration_show_page_section($registration->show_content_section);
        $this->set_registration_media_type($registration->content_media_type);
        $this->set_registration_media_url($registration->content_media_url);

        //set content media settings
        $content_media_settings = $registration->content_media_settings;

        $this->set_registration_content_autoplay($content_media_settings->autoplay);
        $this->set_registration_content_controls($content_media_settings->show_controls);
        $this->set_registration_content_big_button($content_media_settings->show_big_button);

        $this->enable_custom_registration_page($registration->has_custom_registration_page);
        $this->set_custom_registration_page_id($registration->custom_page_id);

        $this->set_registration_title_color($registration->title_color);
        $this->set_registration_date_time_color($registration->date_time_color);
        $this->set_registration_register_form_hidden($registration->hide_registration_form);
        $this->set_registration_register_title($registration->register_title);
        $this->set_registration_register_text($registration->register_text);
        $this->set_registration_register_footer($registration->register_footer);
        $this->set_registration_register_button_text($registration->register_button_text);
        $this->set_registration_register_button_background_color($registration->register_button_background_color);
        $this->set_registration_register_button_border_color($registration->register_button_border_color);
        $this->set_registration_register_button_text_color($registration->register_button_text_color);
        $this->set_registration_login_form_hidden($registration->hide_login_form);
        $this->set_registration_login_title($registration->login_title);
        $this->set_registration_login_text($registration->login_text);
        $this->set_registration_login_button_text($registration->login_button_text);
        $this->set_registration_login_button_background_color($registration->login_button_background_color);
        $this->set_registration_login_button_border_color($registration->login_button_border_color);
        $this->set_registration_login_button_text_color($registration->login_button_text_color);
        $this->set_registration_show_description($registration->show_description);
        $this->set_registration_description_text_color($registration->description_text_color);
        $this->set_registration_description_background_color($registration->description_background_color);
        $this->set_registration_description_border_color($registration->description_border_color);
        $this->set_registration_reg_form_background_color($registration->reg_form_background_color);
        $this->set_registration_reg_form_border_color($registration->reg_form_border_color);
        $this->set_registration_reg_form_font_color($registration->reg_form_font_color);
        $this->set_registration_reg_form_tab_background_color($registration->reg_form_tab_background_color);
        $this->set_registration_reg_form_tab_text_color($registration->reg_form_tab_text_color);
        $this->set_registration_reg_form_tab_registration_text($registration->reg_form_tab_registration_text);
        $this->set_registration_reg_form_tab_login_text($registration->reg_form_tab_login_text);

        $this->set_registration_name_mode($registration->name_mode);
        $this->set_registration_email_mode($registration->email_mode);

        $this->set_registration_ticket_title_text($registration->ticket_title_text);
        $this->set_registration_ticket_link_text($registration->ticket_link_text);
        $this->set_registration_ticket_description($registration->ticket_description);
        $this->set_registration_ticket_thank_you_message($registration->ticket_thank_you_message);
        $this->set_registration_page_params($registration->registration_page_params);
        $this->set_maximum_attendee_enabled($registration->maximum_attendee_enabled);
        $this->set_maximum_attendee_count($registration->maximum_attendee_count);

        // confirmation page
        $this->set_confirmation_page_disabled($confirmation->is_disabled);
        $this->enable_custom_confirmation_page($confirmation->has_custom_page);
        $this->set_custom_confirmation_page_id($confirmation->custom_page_id);
        $this->set_confirmation_title_text($confirmation->title_text);
        $this->set_confirmation_title_color($confirmation->title_color);
        $this->set_confirmation_background_color($confirmation->background_color);
        $this->set_confirmation_background_image_url($confirmation->background_image_url);
        $this->set_confirmation_media_type($confirmation->media_type);
        $this->set_confirmation_media_url($confirmation->media_url);
        $this->set_confirmation_header_script_tag($confirmation->header_script_tag);
        $this->set_confirmation_body_script_tag($confirmation->body_script_tag);
        $this->set_confirmation_page_template($confirmation->page_template);
        $this->set_confirmation_page_params($confirmation->page_params);

        $confirmation_media_settings = $registration->content_media_settings;

        $this->set_confirmation_media_autoplay($confirmation_media_settings->autoplay);
        $this->set_confirmation_media_show_controls($confirmation_media_settings->show_controls);
        $this->set_confirmation_media_show_big_button($confirmation_media_settings->show_big_button);

        $this->set_confirmation_link_above_text_color($confirmation->link_above_text_color);
        $this->set_confirmation_link_below_text_color($confirmation->link_below_text_color);

        $this->set_confirmation_link_above_text_color($confirmation->link_above_text_color);
        $this->set_confirmation_link_below_text_color($confirmation->link_below_text_color);
        $this->set_confirmation_ticket_border_color1($confirmation->ticket_border_color1);
        $this->set_confirmation_ticket_border_color2($confirmation->ticket_border_color2);
        $this->set_confirmation_ticket_body_text_color($confirmation->ticket_body_text_color);
        $this->set_confirmation_ticket_body_background_color($confirmation->ticket_body_background_color);
        $this->set_confirmation_ticket_header_background_color($confirmation->ticket_header_background_color);
        $this->set_confirmation_ticket_header_text_color($confirmation->ticket_header_text_color);
        $this->set_confirmation_ticket_button_color($confirmation->ticket_button_color);
        $this->set_confirmation_ticket_button_text_color($confirmation->ticket_button_text_color);

        $this->set_confirmation_is_social_sharing_enabled($confirmation->is_social_sharing_enabled);
        $this->set_confirmation_social_sharing_border_color($confirmation->social_sharing_border_color);
        $this->set_confirmation_social_sharing_background_color($confirmation->social_sharing_background_color);

        $this->set_confirmation_calendar_border_color($confirmation->calendar_border_color);
        $this->set_confirmation_calendar_background_color($confirmation->calendar_background_color);
        $this->set_confirmation_calendar_text_color($confirmation->calendar_text_color);
        $this->set_confirmation_calendar_button_text_color($confirmation->calendar_button_text_color);
        $this->set_confirmation_calendar_button_background_color($confirmation->calendar_button_background_color);
        $this->set_confirmation_calendar_button_border_color($confirmation->calendar_button_border_color);

        $this->set_webinar_page_settings('live', $params->live);
        $this->set_webinar_page_settings('replay', $params->replay);

        $emails = $params->emails;

        $this->set_email_from_name($emails->from_name);
        $this->set_email_from_address($emails->from_email);
        $this->set_admin_email_address($emails->admin_email_address);

        $this->set_email_template_options('newreg', $emails->types->new_registration);
        $this->set_email_template_options('regconfirm', $emails->types->reg_confirmation);
        $this->set_email_template_options('24hrb4', $emails->types->day_before);
        $this->set_email_template_options('1hrb4', $emails->types->hour_before);
        $this->set_email_template_options('wbnstarted', $emails->types->starting);
        $this->set_email_template_options('wbnreplay', $emails->types->replay);
        $this->set_email_template_options('order_complete', $emails->types->order_complete);
        $this->set_email_template_options('double_optin', $emails->types->double_optin);

        $automated_replay = $params->automated_replay;
        $this->set_automated_replay_enabled($automated_replay->enabled);
        $this->set_automated_replay_available_duration($automated_replay->available_duration);

        // We have to set the type after setting recurring times because
        // some types have fixed times (Right now)
        $this->set_type($general->type);

        // Set the current version and save
        $this->set_config('version', WPWS_PLUGIN_VERSION);
        $this->save();
    }

    public function get_session_date_and_time($exact_time, $day, $time)
    {
        $time_format = WebinarSysteem::get_wp_datetime_formats(WebinarSysteem::$WP_TIME_FORMAT);
        $date_format = WebinarSysteem::get_wp_datetime_formats(WebinarSysteem::$WP_DATE_FORMAT);

        if ($this->is_recurring()) {
            if ($exact_time != null) {
                $dt = $exact_time;
            } else if ($this->is_right_now()) {
                $dt = $this->get_now_in_timezone();
            } else {
                $dt = strtotime("$day $time");
            }

            return (object) array(
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                'date' => date($date_format, $dt),
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                'time' => date($time_format, $dt)
            );
        }

        $date = $this->get_field('gener_date');
        $hour = $this->get_field('gener_hour');
        $minute = $this->get_field('gener_min');

        return (object) [
            // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            'date' => date($date_format, strtotime($date)),
            // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            'time' => date($time_format, strtotime($hour . ':' . $minute))
        ];
    }

    public function get_team_member_key() {
        $key = $this->get_field('team_member_key');

        if (empty($key)) {
            $key = WebinarSysteemHelperFunctions::generate_uuid();
            $this->set_field('team_member_key', $key);
        }

        return $key;
    }

    public function get_access_key() {
        $key = $this->get_field('access_key');

        if (empty($key)) {
            $key = WebinarSysteemHelperFunctions::generate_uuid();
            $this->set_field('access_key', $key);
        }

        return $key;
    }

    public function get_pending_messages_key() {
        $key = $this->get_field('pending_messages_key');

        if (empty($key)) {
            $key = WebinarSysteemHelperFunctions::generate_uuid();
            $this->set_field('pending_messages_key', $key);
        }

        return $key;
    }

    public function set_media_server_id($id) {
        $json = wp_json_encode((object) [
            'created_at' => time(),
            'id' => $id
        ]);
        $this->set_field('media_server_id', $json);
    }

    public function get_media_server_id() {
        $json = $this->get_field('media_server_id');
        $info = json_decode($json);

        if ($info == null) {
            return null;
        }

        // make sure it's not out of date
        $valid_hours = 1;
        if (time() - $info->created_at > $valid_hours * 60 * 60) {
            return null;
        }

        return $info->id;
    }

    public function update_last_active_time() {
        $this->set_field('last_active', time());
    }

    public function was_active_within($minutes_ago = 30) {
        $last_active_at = $this->get_field('last_active');

        if ($last_active_at == null) {
            return false;
        }

        return time() - (int) $last_active_at < (60 * $minutes_ago);
    }

    public function is_using_webinarpress_live() {
        return $this->get_live_media_type() === 'webinarpress-live';
    }

    public function is_using_zoom() {
        return $this->get_live_media_type() === 'zoom';
    }

    public function get_secure_room_name() {
        return $this->get_secure_field('secure_room_name');
    }

    public function get_secure_room_key() {
        return $this->get_secure_field('secure_room_key');
    }

    public function set_enabled_poll_id($poll_id) {
        $page = $this->get_live_page_prefix();
        $this->set_field("{$page}enabled_poll", $poll_id);
    }

    public function clear_enabled_poll_id() {
        $page = $this->get_live_page_prefix();
        $this->delete_field("{$page}enabled_poll");
    }

    public function get_enabled_poll_id() {
        $page = $this->get_live_page_prefix();
        $poll_id = $this->get_field("{$page}enabled_poll");
        return !$poll_id ? null : (int) $poll_id;
    }
}
