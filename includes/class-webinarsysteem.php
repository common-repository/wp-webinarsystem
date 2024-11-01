<?php

/**
 * @package WebinarSysteem
 */
class WebinarSysteem
{
    public $_FILE_;
    public $_DIRECTORY_;
    public $post_slug;
    public $db_version;
    public $plugin_version;

    public static $lang_slug;
    private static $actions_have_been_added = false;

    public static $WP_DATE_FORMAT = 1;
    public static $WP_TIME_FORMAT = 2;
    public static $WP_DATE_TIME_FORMAT = 3;

    private static $ACCESS_USER_ROLES = 'user_roles';
    private static $ACCESS_MEMBER_LEVELS = 'member_levels';
    private static $ACCESS_USER_IDS = 'user_ids';

    private static function has_setup_actions_and_filters() {
        return self::$actions_have_been_added;
    }

    private static function set_has_setup_actions_and_filters() {
        self::$actions_have_been_added = true;
    }

    private function setup_actions_and_filters() {
        register_activation_hook($this->_FILE_, [$this, 'install']);

        register_activation_hook($this->_FILE_, [$this, 'createRoles']);
        register_deactivation_hook($this->_FILE_, [$this, 'purgeRoles']);

        /* This breaks email shortocdes when WPML is enabled converting "value" to « value »
        add_filter('meta_content', 'wptexturize');
        */
        add_filter('meta_content', 'convert_smilies');
        add_filter('meta_content', 'convert_chars');
        add_filter('meta_content', 'wpautop');
        add_filter('meta_content', 'shortcode_unautop');
        add_filter('meta_content', 'prepend_attachment');

        add_filter('option_active_plugins', [$this, 'webinarExcludePlugins']);
        add_filter('option_page_capability_wswebinar_options', [$this, 'wswebinarOptionsPageCapability']);

        WebinarSysteemShortCodes::init();

        new WebinarSysteemEmails;

        add_action('init', [$this, 'register_webinar_post_type']);
        add_action('init', ['WebinarSysteemUserPages', 'register_post_type']);
        add_action('init', [$this, 'run_email_schedule']);
        add_action('init', ['WebinarsysteemMailingListIntegrations', 'aweber_connect']);
        add_action('init', [$this, 'render_zoom_frame_content']);

        add_action('wp_before_admin_bar_render', [$this, 'wpwsAdminBarRender']);
        //add_action('network_admin_menu', array($this, 'network_menu'));
        add_action('template_include', [$this, 'handle_webinar_template']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_front_end_scripts'], 1000);
        add_action('wp_enqueue_scripts', [$this, 'loadPageScripts'], 1100);
        add_action('wp_enqueue_scripts', [$this, 'deregisterCurrentThemeScripts'], 5000);

        add_action('admin_init', [$this, 'register_webinar_settings']);
        add_action('admin_init', ['WebinarSysteemExports', 'handle_exports']);
        add_action('admin_init', [$this, 'run_database_migrations']);
        add_action('admin_menu', [$this, 'register_menus']);

        add_action('wp_footer', [$this, 'liveControlBar']);

        // questions
        add_action('wp_ajax_wpws-save-question', ['WebinarSysteemAjax', 'post_question']);
        add_action('wp_ajax_nopriv_wpws-save-question', ['WebinarSysteemAjax', 'post_question']);
        add_action('wp_ajax_wpws-delete-question', ['WebinarSysteemAjax', 'delete_webinar_question']);
        add_action('wp_ajax_wpws-clear-question-answer', ['WebinarSysteemAjax', 'clear_question_answer']);
        add_action('wp_ajax_nopriv_wpws-clear-question-answer', ['WebinarSysteemAjax', 'clear_question_answer']);
        add_action('wp_ajax_nopriv_wpws-delete-question', ['WebinarSysteemAjax', 'delete_webinar_question']);
        add_action('wp_ajax_wpws-save-question-answer', ['WebinarSysteemAjax', 'post_question_answer']);
        add_action('wp_ajax_nopriv_wpws-save-question-answer', ['WebinarSysteemAjax', 'post_question_answer']);
        add_action('wp_ajax_set-enabled-questions', ['WebinarSysteemAjax', 'setEnabledQuestions']);
        add_action('wp_ajax_nopriv_set-enabled-questions', ['WebinarSysteemAjax', 'setEnabledQuestions']);
        add_action('wp_ajax_toggle-attendees-tab', ['WebinarSysteemAjax', 'set_attendees_tab_visible']);
        add_action('wp_ajax_nopriv_toggle-attendees-tab', ['WebinarSysteemAjax', 'set_attendees_tab_visible']);
        add_action('wp_ajax_toggle-cta', ['WebinarSysteemAjax', 'set_cta_visible']);
        add_action('wp_ajax_nopriv_toggle-cta', ['WebinarSysteemAjax', 'set_cta_visible']);
        add_action('wp_ajax_nopriv_wpws-set-question-visibility', ['WebinarSysteemAjax', 'update_question_visibility']);
        add_action('wp_ajax_wpws-set-question-visibility', ['WebinarSysteemAjax', 'update_question_visibility']);

        add_action('wp_ajax_wpws-enable-chat', ['WebinarSysteemAjax', 'enable_chat']);
        add_action('wp_ajax_nopriv_wpws-enable-chat', ['WebinarSysteemAjax', 'enable_chat']);
        add_action('wp_ajax_wpws-enable-questions', ['WebinarSysteemAjax', 'enable_questions']);
        add_action('wp_ajax_nopriv_wpws-enable-questions', ['WebinarSysteemAjax', 'enable_questions']);
        add_action('wp_ajax_wpws-enable-attendees-tab', ['WebinarSysteemAjax', 'enable_attendees_tab']);
        add_action('wp_ajax_nopriv_wpws-enable-attendees-tab', ['WebinarSysteemAjax', 'enable_attendees_tab']);
        add_action('wp_ajax_wpws-enable-hand-raising', ['WebinarSysteemAjax', 'enable_hand_raising']);
        add_action('wp_ajax_nopriv_wpws-enable-hand-raising', ['WebinarSysteemAjax', 'enable_hand_raising']);
        add_action('wp_ajax_wpws-enable-cta', ['WebinarSysteemAjax', 'enable_cta']);
        add_action('wp_ajax_nopriv_wpws-enable-cta', ['WebinarSysteemAjax', 'enable_cta']);

        // chats
        add_action('wp_ajax_wpws-send-chat', ['WebinarSysteemAjax', 'post_chat_message']);
        add_action('wp_ajax_nopriv_wpws-send-chat', ['WebinarSysteemAjax', 'post_chat_message']);
        add_action('wp_ajax_set-enabled-chats', ['WebinarSysteemAjax', 'setEnabledChats']);
        add_action('wp_ajax_nopriv_set-enabled-chats', ['WebinarSysteemAjax', 'setEnabledChats']);

        add_action('wp_ajax_nopriv_raise-hand', ['WebinarSysteemAjax', 'raise_attendee_hand']);
        add_action('wp_ajax_raise-hand', ['WebinarSysteemAjax', 'raise_attendee_hand']);
        add_action('wp_ajax_unraise-hands', ['WebinarSysteemAjax', 'unraise_attendee_hands']);
        add_action('wp_ajax_nopriv_unraise-hands', ['WebinarSysteemAjax', 'unraise_attendee_hands']);
        add_action('wp_ajax_show-cta', ['WebinarSysteemAjax', 'set_cta_status']);
        add_action('wp_ajax_nopriv_show-cta', ['WebinarSysteemAjax', 'set_cta_status']);
        add_action('wp_ajax_action-box-status', ['WebinarSysteemAjax', 'setActionBox']);
        add_action('wp_ajax_nopriv_action-box-status', ['WebinarSysteemAjax', 'setActionBox']);
        add_action('wp_ajax_update-webinar-cache', ['WebinarSysteemAjax', 'updateWebinarCache']);
        add_action('wp_ajax_wpws-update-last-seen', ['WebinarSysteemAjax', 'updateLastSeen']);
        add_action('wp_ajax_nopriv_wpws-update-last-seen', ['WebinarSysteemAjax', 'updateLastSeen']);
        add_action('wp_ajax_set_hand_raising_enabled', ['WebinarSysteemAjax', 'set_hand_raising_enabled']);
        add_action('wp_ajax_nopriv_set_hand_raising_enabled', ['WebinarSysteemAjax', 'set_hand_raising_enabled']);

        // shortcode
        add_action('wp_ajax_wpws_login_attendee', ['WebinarSysteemAjax', 'login_attendee']);
        add_action('wp_ajax_nopriv_wpws_login_attendee', ['WebinarSysteemAjax', 'login_attendee']);

        add_action('wp_ajax_wpws_login_attendee_redirect', ['WebinarSysteemAjax', 'login_attendee_with_redirect']);
        add_action('wp_ajax_nopriv_wpws_login_attendee_redirect', ['WebinarSysteemAjax', 'login_attendee_with_redirect']);

        add_action('wp_ajax_wpws_register_attendee', ['WebinarSysteemAjax', 'register_attendee']);
        add_action('wp_ajax_nopriv_wpws_register_attendee', ['WebinarSysteemAjax', 'register_attendee']);

        // Remaining seat count
        add_action('wp_ajax_wpws_get_remaining_places_for_webinar', ['WebinarSysteemAjax', 'get_remaining_places_for_webinar']);
        add_action('wp_ajax_nopriv_wpws_get_remaining_places_for_webinar', ['WebinarSysteemAjax', 'get_remaining_places_for_webinar']);

        // Resend attendee confirmation email
        add_action('wp_ajax_wpws_resend_confirmation_email', ['WebinarSysteemAjax', 'resend_attendee_confirmation_email']);
        add_action('wp_ajax_nopriv_wpws_resend_confirmation_email', ['WebinarSysteemAjax', 'resend_attendee_confirmation_email']);

        // register
        add_action('wp_ajax_wpws_attempt_login_from_auth', ['WebinarSysteemAjax', 'attempt_login_from_auth']);
        add_action('wp_ajax_nopriv_wpws_attempt_login_from_auth', ['WebinarSysteemAjax', 'attempt_login_from_auth']);

        // other ajax
        add_action('wp_ajax_quickchangestatus', ['WebinarSysteemAjax', 'set_webinar_status']);
        add_action('wp_ajax_wpws_send_email_preview', ['WebinarSysteemAjax', 'send_email_preview']);
        add_action('wp_ajax_check-webinar-status', ['WebinarSysteemAjax', 'ajaxCheckIfWebinarStatusLive']);
        add_action('wp_ajax_nopriv_check-webinar-status', ['WebinarSysteemAjax', 'ajaxCheckIfWebinarStatusLive']);
        add_action('wp_ajax_sync-import-imgs', ['WebinarSysteemAjax', 'sync_import_images']);
        add_action('wp_ajax_get-drip-campaigns', ['WebinarsysteemMailingListIntegrations', 'getDripCampaigns']);
        add_action('wp_ajax_nopriv_get-drip-campaigns', ['WebinarsysteemMailingListIntegrations', 'getDripCampaigns']);
        add_action('wp_ajax_revoke-aweber-config', ['WebinarsysteemMailingListIntegrations', 'revokeAweberConfig']);
        add_action('wp_ajax_update-incentive', ['WebinarSysteemAjax', 'update_incentive']);
        add_action('wp_ajax_host-desc-boxes', ['WebinarSysteemAjax', 'setHostUpdateBox']);
        add_action('wp_ajax_nopriv_host-desc-boxes', ['WebinarSysteemAjax', 'setHostUpdateBox']);
        add_action('wp_ajax_delete-chats', ['WebinarSysteemAjax', 'deleteChats']);
        add_action('wp_ajax_nopriv_delete-chats', ['WebinarSysteemAjax', 'deleteChats']);
        add_action('wp_ajax_delete-questions', ['WebinarSysteemAjax', 'deleteQuestions']);
        add_action('wp_ajax_wpws_set_media_source', ['WebinarSysteemAjax', 'set_media_source']);
        add_action('wp_ajax_wpws_render_cta_content', ['WebinarSysteemAjax', 'render_cta_content']);
        add_action('wp_ajax_nopriv_wpws_render_cta_content', ['WebinarSysteemAjax', 'render_cta_content']);

        add_action('wp_ajax_wpws_webinar_heartbeat', ['WebinarSysteemAjax', 'webinar_heartbeat']);
        add_action('wp_ajax_nopriv_wpws_webinar_heartbeat', ['WebinarSysteemAjax', 'webinar_heartbeat']);

        add_action('wp_ajax_wpws-get-upcoming-sessions', ['WebinarSysteemAjax', 'get_upcoming_sessions']);
        add_action('wp_ajax_nopriv_wpws-get-upcoming-sessions', ['WebinarSysteemAjax', 'get_upcoming_sessions']);

        // translations
        add_action('wp_ajax_wpws_get_translations', ['WebinarSysteemAjax', 'get_translations']);
        add_action('wp_ajax_nopriv_wpws_get_translations', ['WebinarSysteemAjax', 'get_translations']);

        // registration Widgets
        add_action('wp_ajax_wpws_get_registration_widgets', ['WebinarSysteemAjax', 'get_registration_widgets']);
        add_action('wp_ajax_wpws_delete_registration_widget', ['WebinarSysteemAjax', 'delete_registration_widget']);
        add_action('wp_ajax_wpws_save_registration_widget', ['WebinarSysteemAjax', 'save_registration_widget']);
        add_action('wp_ajax_wpws_get_registration_widget_params', ['WebinarSysteemAjax', 'get_registration_widget_params']);
        add_action('wp_ajax_wpws_get_webinars', ['WebinarSysteemAjax', 'get_webinars']);
        add_action('wp_ajax_wpws_delete_webinar', ['WebinarSysteemAjax', 'delete_webinar']);
        add_action('wp_ajax_wpws_get_registration_widget_shortcode_html', ['WebinarSysteemAjax', 'get_registration_widget_shortcode_html']);

        // attendees
        add_action('wp_ajax_wpws_get_attendees', ['WebinarSysteemAjax', 'get_attendees']);
        add_action('wp_ajax_wpws_delete_attendees', ['WebinarSysteemAjax', 'delete_attendees']);
        add_action('wp_ajax_wpws_import_attendees', ['WebinarSysteemAjax', 'import_attendees']);

        // chats/messages
        add_action('wp_ajax_wpws_get_messages', ['WebinarSysteemAjax', 'get_messages']);
        add_action('wp_ajax_wpws_delete_messages', ['WebinarSysteemAjax', 'delete_messages']);

        // questions
        add_action('wp_ajax_wpws_get_questions', ['WebinarSysteemAjax', 'get_questions']);
        add_action('wp_ajax_wpws_delete_questions', ['WebinarSysteemAjax', 'delete_questions']);

        // webinar editor
        add_action('wp_ajax_wpws_register_attendee', ['WebinarSysteemAjax', 'register_attendee']);
        add_action('wp_ajax_wpws_get_pages_and_posts', ['WebinarSysteemAjax', 'get_pages_and_posts']);
        add_action('wp_ajax_wpws_get_timezones', ['WebinarSysteemAjax', 'get_timezones']);
        add_action('wp_ajax_wpws_get_mailinglist_accounts', ['WebinarSysteemAjax', 'get_mailinglist_accounts']);
        add_action('wp_ajax_wpws_get_mailinglist_lists', ['WebinarSysteemAjax', 'get_mailinglist_lists']);
        add_action('wp_ajax_wpws_get_wp_users', ['WebinarSysteemAjax', 'get_wp_users']);
        add_action('wp_ajax_wpws_get_wp_roles', ['WebinarSysteemAjax', 'get_wp_roles']);
        add_action('wp_ajax_wpws_get_woocommerce_roles', ['WebinarSysteemAjax', 'get_woocommerce_roles']);
        add_action('wp_ajax_wpws_get_default_email_template_options', ['WebinarSysteemAjax', 'get_default_email_template_options']);

        // settings
        add_action('wp_ajax_wpws_get_settings', ['WebinarSysteemAjax', 'get_settings']);
        add_action('wp_ajax_wpws_update_settings', ['WebinarSysteemAjax', 'update_settings']);
        add_action('wp_ajax_wpws_check_mailinglist_key', ['WebinarSysteemAjax', 'check_mailinglist_key']);

        // editor
        add_action('wp_ajax_wpws_get_webinar_params', ['WebinarSysteemAjax', 'get_webinar_params']);
        add_action('wp_ajax_wpws_update_webinar_params', ['WebinarSysteemAjax', 'update_webinar_params']);
        add_action('wp_ajax_wpws_update_webinar_slug', ['WebinarSysteemAjax', 'update_webinar_slug']);
        add_action('wp_ajax_wpws_update_webinar_status', ['WebinarSysteemAjax', 'update_webinar_status']);

        // webinar recordings
        add_action('wp_ajax_wpws_get_webinar_recordings', ['WebinarSysteemAjax', 'get_webinar_recordings']);
        add_action('wp_ajax_wpws_delete_webinar_recording', ['WebinarSysteemAjax', 'delete_webinar_recording']);

        // notices
        add_action('wp_ajax_wpws_get_notices', ['WebinarSysteemAjax', 'get_admin_notices']);

        // Polls
        add_action('wp_ajax_wpws_get_polls', ['WebinarSysteemAjax', 'get_polls']);
        add_action('wp_ajax_wpws_create_poll', ['WebinarSysteemAjax', 'create_poll']);
        add_action('wp_ajax_wpws_update_poll', ['WebinarSysteemAjax', 'update_poll']);
        add_action('wp_ajax_wpws_poll_analytics_by_poll', ['WebinarSysteemAjax', 'poll_analytics_by_poll']);
        add_action('wp_ajax_wpws_poll_analytics_by_webinar', ['WebinarSysteemAjax', 'poll_analytics_by_webinar']);
        add_action('wp_ajax_wpws_delete_poll', ['WebinarSysteemAjax', 'delete_poll']);
        add_action('wp_ajax_wpws_submit_poll', ['WebinarSysteemAjax', 'submit_poll']);
        add_action('wp_ajax_nopriv_wpws_submit_poll', ['WebinarSysteemAjax', 'submit_poll']);
        add_action('wp_ajax_wpws_launch_poll', ['WebinarSysteemAjax', 'launch_poll']);
        add_action('wp_ajax_wpws_close_poll', ['WebinarSysteemAjax', 'close_poll']);

        // test webhooks
        add_action('wp_ajax_wpws_test_new_registration_webhook', ['WebinarSysteemAjax', 'test_new_registration_webhook']);
        add_action('wp_ajax_wpws_test_attended_webinar_webhook', ['WebinarSysteemAjax', 'test_attended_webinar_webhook']);

        add_action('admin_head', [$this, 'webinarsysteem_ajaxurl']);
        add_action('wp_head', [$this, 'webinarsysteem_ajaxurl']);

        add_action('after_setup_theme', [$this, 'load_languages']);
        add_action('admin_init', [$this, 'admin_init']);

        add_action('admin_action_wswebinar_duplicate_post_as_draft', [$this, 'wswebinar_duplicate_post_as_draft']);

        add_action('admin_notices', ['WebinarsysteemMailingListIntegrations', 'check_aweber_disconnected']);
        add_action('admin_notices', [$this, 'postNotices']);
        add_action('admin_notices', [$this, 'check_mysql_and_php_versions']);

        add_action('admin_init', [$this, 'wpwsPluginNoticeIgnore']);
        add_action('admin_bar_init', [$this, 'remove_admin_bar_from_webinars']);

        add_action('init', [$this, 'embedded_iframe']);

        add_action('wp_ajax_wpws_external_cron', [$this, 'external_cron']);
        add_action('wp_ajax_nopriv_wpws_external_cron', [$this, 'external_cron']);

        if (self::is_webinarpress_page()) {
            add_action('admin_footer_text', ['WebinarSysteemPromotionalNotices', 'footerRating']);
        }

        add_action(
            'widgets_init',
            function() {
                register_widget("WebinarSysteemUpcomingWebinars");
                register_widget("WebinarSysteemPastWebinars");
            });

        // Webinar User Profile Hooks
        add_action('template_redirect', ['WebinarSysteemUserPages', 'handle_unsubscribe']);
        add_filter('the_content', ['WebinarSysteemUserPages', 'get_page_content'], 10, 1);

        add_action('avf_enqueue_wp_mediaelement', array($this, 'enqueue_enfold_enqueue_wp_mediaelement'), 10, 2);

        add_action('wp_mail_failed', ['WebinarSysteem', 'on_wp_mail_failed'], 10, 1);
        add_filter('register_post_type_args', [$this, 'change_post_types_slug'], 10, 2);
        add_action('upgrader_process_complete', [$this, 'upgrade_process_complete'], 10, 2);

        add_action('wp_ajax_wpws_subscribe_to_drip_course', ['WebinarSysteemAjax', 'subscribe_to_drip_course']);
        add_action('init', [$this, 'allow_origin']);
    }

    /*
    Needed for registration widgets hosted on other pages to allow cross site scripting requests
    */
    public function allow_origin() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (isset($_POST['action'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            switch ($_POST['action']) {
                case 'wpws_get_translations':
                case 'wpws_login_attendee':
                case 'wpws_register_attendee':
                    header('Access-Control-Allow-Origin: *');
                    break;
            }
        }
    }

    function embedded_iframe() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!isset($_POST['wpws-embedded-iframe'])) {
            return;
        }

        // Verify nonce
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (!isset($_POST['embed_nonce']) || !wp_verify_nonce($_POST['embed_nonce'], WebinarSysteemJS::get_nonce_secret())) {
            die();
        }
        /*
         * Disable XSS protection to allow loading other scripts from our embedded
         * script. This is needed for loading things like JWPlayer scripts
         */
        header('X-XSS-Protection: 0');

        ?>
        <html>
        <head>
            <style>
                body {
                    padding: 0;
                    margin: 0;
                    overflow: hidden;
                }
            </style>
        </head>
        <div id="content">
            <?php 
                // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                echo stripslashes($_POST['content']) 
            ?>
        </div>
        </html>
        <?php
        die();
    }

    public function external_cron() {
        // Process pending WebinarPress Connect messages
        WebinarSysteemMediaServer::process_pending_messages();

        $emails = new WebinarSysteemEmails();

        // Send queued emails
        $emails->send_queued_emails();

        // Only check scheduled emails every 5 mins
        if (!$emails->was_last_schedule_run_within(5)) {
            $emails->update_last_schedule_run();
            $emails->send_scheduled_emails();
        }

        die('done!');
    }

    static function upgrade_process_complete($upgrade_info, $options) {
        $current_plugin = plugin_basename(__FILE__);

        if ($options['action'] != 'update' || $options['type'] != 'plugin') {
            return;
        }

        foreach($options['plugins'] as $plugin) {
            if ($plugin != $current_plugin) {
                continue;
            }

            flush_rewrite_rules();
        }
    }

    static function on_wp_mail_failed($error) {
        if (is_wp_error($error)) {
            $error_text = $error->get_error_message();
            WebinarSysteemLog::log("wp_mail failure detected: $error_text (this might not be from WebinarPress!)");
        }
    }

    public function __construct($file = null, $directory = null, $version = null)
    {
        $this->_FILE_ = $file;
        $this->_DIRECTORY_ = $directory;
        $this->plugin_version = $version;
        $this->setAttributes($file);

        if (!WebinarSysteem::has_setup_actions_and_filters()) {
            // TODO this is very hacky because WebinarSysteem instances
            // are create all over the code, refactoring should remove that

            $this->setup_actions_and_filters();
            WebinarSysteem::set_has_setup_actions_and_filters();
        }
    }

    function change_post_types_slug($args, $post_type) {
        if ($post_type === $this->post_slug) {
            $custom_slug = WebinarSysteemSettings::instance()->get_webinar_slug();

            $args['rewrite']['slug'] = isset($custom_slug) && strlen($custom_slug) > 0
                ? $custom_slug
                : 'webinars';
        }

        return $args;
    }

    public function enqueue_enfold_enqueue_wp_mediaelement($condition, $options) {
        return false;
    }

    /*
     * Define ajax url for ajax requests.
     */
    public function webinarsysteem_ajaxurl()
    {
        ?>
        <script type="text/javascript">
          var wpws_ajaxurl = '<?php echo esc_url(admin_url('admin-ajax.php')) ?>';
        </script>
        <?php
    }

    /*
     * Run migrations
     */
    public function run_database_migrations() {
        $db = new WebinarsysteemDbMigrations();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $force_db_upgrade = isset($_GET['force-db-migrate']);

        if (is_user_logged_in() && current_user_can('administrator') && $force_db_upgrade) {
            $db->force_migrations_with_debug();
        }

        $db->run_migrations();
    }

    public function run_email_schedule() {
        if (!is_user_logged_in() && current_user_can('administrator')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $force_email_schedule = isset($_GET['force-email-schedule']);

        if ($force_email_schedule) {
            $emails = new WebinarSysteemEmails();
            $emails->send_scheduled_emails();
            die('Done!');
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $force_email_queue = isset($_GET['force-email-queue']);

        if ($force_email_queue) {
            $emails = new WebinarSysteemEmails();
            $emails->send_queued_emails();
            die('Done!');
        }
    }

    public static function get_locale() {
        global $wp_version;

        // get the locale
        $locale = get_locale();
        if ($wp_version >= 4.7) {
            $locale = get_user_locale();
        }

        return apply_filters('plugin_locale',  $locale, 'wpwebinarsystem');
    }

    /*
     * Load language files
     */
    public function load_languages()
    {
        $wpws_lang_dir = dirname(plugin_basename($this->_FILE_)) . '/localization/';

        // get the locale
        $locale = self::get_locale();
        $mofile = sprintf('%1$s-%2$s.mo', '_wswebinar', $locale);

        // check in lang/plugins/wpwebinarsystem folder
        $mofile_global1 = WP_LANG_DIR.'/plugins/wpwebinarsystem/'.$mofile;
        if (file_exists($mofile_global1)) {
            load_textdomain(self::$lang_slug, $mofile_global1);
            return;
        }

        // check in root lang/plugins folder
        $mofile_global2 = WP_LANG_DIR.'/plugins/'.$mofile;
        if (file_exists($mofile_global2)) {
            load_textdomain(self::$lang_slug, $mofile_global2);
            return;
        }

        load_plugin_textdomain(self::$lang_slug, false, $wpws_lang_dir);
    }

    /*
     * Adds webinarDelete function to the delete_post hook if current use have rights.
     */
    public function admin_init() {
        if (current_user_can('delete_posts')) {
            add_action('delete_post', array($this, 'webinarDelete'), 10);
        }

        if (WebinarSysteemPermissions::can_create_webinars()) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_REQUEST['wpws_auth_mautic']) && isset($_REQUEST['mautic_url']) && isset($_REQUEST['client_id']) && isset($_REQUEST['client_secret'])) {
                $callback_url = admin_url('?wpws_connect_mautic=1');

                // get the redirect URL
                $mautic = new MauticSimpleClient();
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $url = $mautic->being_auth(sanitize_text_field($_REQUEST['mautic_url']), sanitize_text_field($_REQUEST['client_id']), sanitize_text_field($_REQUEST['client_secret']),
                    $callback_url
                );

                // redirect!
                wp_redirect($url);
                die();
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_REQUEST['wpws_connect_mautic']) && isset($_REQUEST['code']) && isset($_REQUEST['state'])) {
                // convert the code into an access token
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $code = sanitize_text_field($_REQUEST['code']);

                $mautic = new MauticSimpleClient();
                $token = $mautic->complete_auth($code);
    
                // redirect to the settings page
                wp_redirect(admin_url('admin.php?page=wswbn-settings#mailing-lists'));
                die();
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_REQUEST['wpws_disconnect_mautic'])) {
                $mautic = new MauticSimpleClient();
                $mautic->disconnect();
    
                // redirect!
                wp_redirect(admin_url('admin.php?page=wswbn-settings#mailing-lists'));
                die();
            }
        }
    }

    /*
     * Deleting questions that belongs to the deleted webinar.
     */
    public function webinarDelete($pid)
    {
        if (get_post_type($pid) !== $this->post_slug) {
            return;
        }

        global $wpdb;
        $tabl = WebinarSysteemTables::get_questions();
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        if ($wpdb->get_var($wpdb->prepare('SELECT webinar_id FROM ' . $tabl . ' WHERE webinar_id = %d', $pid))) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            return $wpdb->query($wpdb->prepare('DELETE FROM ' . $tabl . ' WHERE webinar_id = %d', $pid));
        }

        return true;
    }

    /*
     *
     * Load admin scripts
     *
     */

    public function enqueue_admin_scripts()
    {
        wp_enqueue_style('webinar-admin', plugin_dir_url($this->_FILE_) . 'includes/css/webinar-admin.css', array(), WPWS_PLUGIN_VERSION);
        wp_enqueue_style('webinar-admin-fonts', plugin_dir_url($this->_FILE_) . 'includes/css/fonts.css', array(), WPWS_PLUGIN_VERSION);

        $post_type = get_post_type(get_the_ID());

        if (!self::is_webinarpress_page() && $post_type != 'wswebinars') {
            return;
        }

        WebinarSysteemJS::embed_assets();

        wp_enqueue_script('jquery');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_script('jquery-ui-core', false, array('jquery'));
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_script('jquery-ui-tabs', false, array('jquery'));
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_script('jquery-ui-datepicker', false, array('jquery'));
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_script('jquery-ui-accordion', false, array('jquery'));
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_script('jquery-ui-sortable', false, array('jquery'));
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_script('wp-color-picker', false, array('jquery'));
        //wp_enqueue_script('wp-color-picker-alpha', plugin_dir_url($this->_FILE_) . 'includes/js/wp-color-picker-alpha.js');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('bootstrap-switch-script', plugin_dir_url($this->_FILE_) . 'includes/js/bootstrap-switch.min.js', array(), WPWS_PLUGIN_VERSION);
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('webinar-systeem', plugin_dir_url($this->_FILE_) . 'includes/js/webinar-systeem.js', array('jquery', 'jquery-ui-core', 'jquery-ui-accordion'), WPWS_PLUGIN_VERSION);
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('webinar-systeem-custom-fields', plugin_dir_url($this->_FILE_) . 'includes/js/webinar-systeem-custom-fields.js', array('jquery', 'jquery-ui-core', 'jquery-ui-accordion'), WPWS_PLUGIN_VERSION);
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_localize_script('webinar-systeem', 'wpwebinarsystem', array( 'ajaxurl' => admin_url('admin-ajax.php'), 'security' => wp_create_nonce(WebinarSysteemJS::get_nonce_secret())));
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('ZeroClipboard_script', plugin_dir_url($this->_FILE_) . 'includes/js/ZeroClipboard.min.js', array(), WPWS_PLUGIN_VERSION);
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wp-chosen', plugin_dir_url($this->_FILE_) . 'includes/js/chosen.jquery.min.js', array(), WPWS_PLUGIN_VERSION);
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wpws_jscolor', plugin_dir_url($this->_FILE_) . 'includes/js/jscolor.js', array(), WPWS_PLUGIN_VERSION);
        wp_style_add_data('webinar-admin', 'rtl', 'replace');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wswebinar-jquery-ui', plugin_dir_url($this->_FILE_) . 'includes/css/jquery-ui.theme.min.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wswebinar-jquery-ui-structure', plugin_dir_url($this->_FILE_) . 'includes/css/jquery-ui.structure.min.css');

        $screen = get_current_screen();
        if ($screen->post_type == 'wswebinars' || wp_get_theme()->get('Name') != 'Divi') {
            // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
            wp_enqueue_style('webinar-admin-icons', plugin_dir_url($this->_FILE_) . 'includes/css/icons.css');
        };

        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('bootstrap-switch-style', plugin_dir_url($this->_FILE_) . 'includes/css/bootstrap-switch.min.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wp-chosen-style', plugin_dir_url($this->_FILE_) . 'includes/css/chosen.min.css');
        wp_enqueue_media();

        wp_localize_script('webinar-systeem', 'wpwsL10n', array(
            'automated' => __('Automated', '_wswebinar'),
            'countdown' => __('Countdown', '_wswebinar'),
        ));

        if (current_user_can('administrator') &&
            WebinarSysteemSettings::instance()->should_show_course_invite()) {
            wp_enqueue_style('wp-pointer');
            wp_enqueue_script('wp-pointer');
            add_action('admin_print_footer_scripts', [$this, 'show_drip_subscription_pointer']);
        }
    }

    public function loadPageScripts()
    {
        WebinarSysteemJS::embed_registration_widgets();

        $post_types = get_post_type(get_the_ID());
        if ($post_types == 'wpws_page') {
            // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion
            wp_enqueue_script('wpws-jquery-ui-core', false, array('jquery'));
        }

        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wpws-moment', plugin_dir_url($this->_FILE_) . 'includes/js/moment-with-locales.min.js', array(), WPWS_PLUGIN_VERSION);
    }

    public function enqueue_front_end_scripts()
    {
        $post_type = get_post_type(get_the_ID());

        if ($post_type != 'wswebinars' || !is_single()) {
            return;
        }

        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_script('wpws-jquery-ui-core', false, array('jquery'));
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wpws-zero-clipboard', plugin_dir_url($this->_FILE_) . 'includes/js/ZeroClipboard.min.js', array('jquery', 'jquery-ui-core', 'wpws-bootstrap-switch-script'), WPWS_PLUGIN_VERSION);
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_script('wpws-bootstrap-script', plugin_dir_url($this->_FILE_) . 'includes/js/bootstrap.min.js');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion	
        wp_enqueue_script('wpws-bootstrap-switch-script', plugin_dir_url($this->_FILE_) . 'includes/js/bootstrap-switch.min.js');

        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wpws-add-event', plugin_dir_url($this->_FILE_) . 'includes/js/addEvent.js', array('jquery', 'jquery-ui-core'), WPWS_PLUGIN_VERSION);
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wpws-wpwebinarsystem-helper', plugin_dir_url($this->_FILE_) . 'includes/js/helper-functions.js', array('jquery',), WPWS_PLUGIN_VERSION);
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wpws-wpwebinarsystem', plugin_dir_url($this->_FILE_) . 'includes/js/front-end.js', array('jquery',), WPWS_PLUGIN_VERSION);
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wpws-wpwebinarsystem-front', plugin_dir_url($this->_FILE_) . 'includes/js/int-controllers.js', array('jquery'), WPWS_PLUGIN_VERSION);
        wp_localize_script('wpws-wpwebinarsystem-front', 'wpwebinarsystem', array('ajaxurl' => admin_url( 'admin-ajax.php'), 'security' => wp_create_nonce(WebinarSysteemJS::get_nonce_secret())));
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wpws-flipclock', plugin_dir_url($this->_FILE_) . 'includes/js/flipclock.min.js', array('jquery'), WPWS_PLUGIN_VERSION);

        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter, WordPress.WP.EnqueuedResourceParameters.MissingVersion	
        wp_enqueue_script('wpws-google-platform', '//apis.google.com/js/platform.js', array('jquery'));
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wpws-videojs', plugin_dir_url($this->_FILE_) . 'includes/libs/videojs/videojs.js', array(), WPWS_PLUGIN_VERSION);
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wpws-videojs-ie', plugin_dir_url($this->_FILE_) . 'includes/libs/videojs/videojs-ie8.min.js', array(), WPWS_PLUGIN_VERSION);
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wpws-videojs-youtube', plugin_dir_url($this->_FILE_) . 'includes/libs/videojs/videojs-youtube.min.js', array(), WPWS_PLUGIN_VERSION);
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script('wpws-wpwsmediaelement-js', plugin_dir_url($this->_FILE_) . 'includes/libs/mediaelement/mediaelement-and-player.min.js', array('jquery'), WPWS_PLUGIN_VERSION);

        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wpws-bootstrap', plugin_dir_url($this->_FILE_) . 'includes/css/bootstrap.min.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wpws-bootstrap-switch-style', plugin_dir_url($this->_FILE_) . 'includes/css/bootstrap-switch.min.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wpws-font-awesome', plugin_dir_url($this->_FILE_) . 'includes/css/font-awesome.min.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wpws-webinar', plugin_dir_url($this->_FILE_) . 'includes/css/webinar.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wpws-flipclock', plugin_dir_url($this->_FILE_) . 'includes/css/flipclock.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wpws-ubuntu-font', '//fonts.googleapis.com/css?family=Ubuntu:300,400,500');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wpws-webinar-admin-fonts', plugin_dir_url($this->_FILE_) . 'includes/css/fonts.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wpws-webinar-admin-icons', plugin_dir_url($this->_FILE_) . 'includes/css/icons.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wpws-font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wpws-videojs-css', plugin_dir_url($this->_FILE_) . 'includes/libs/videojs/videojs.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wpws-wpwsmediaelement', plugin_dir_url($this->_FILE_) . 'includes/libs/mediaelement/mediaelementplayer.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wpws-mediaelement-skin', plugin_dir_url($this->_FILE_) . 'includes/libs/mediaelement/mejs-skins.css');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_style('wswebinar-calendar', plugin_dir_url($this->_FILE_) . 'includes/css/atc-style-blue.css');

        wp_enqueue_media();
    }

    public function deregisterCurrentThemeScripts()
    {
        global $post, $wp_styles;
        if (empty($post) || in_array(get_option('_wswebinar_enable_theme_styles'), array('on', null, ''))) {
            return;
        }

        $post_type = get_post_type($post->ID);

        if ($post_type != $this->post_slug) {
            return;
        }

        $temp_dir = get_template_directory_uri();

        foreach ($wp_styles->registered as $handle => $data) {
            if ($this->startsWith($data->src, $temp_dir)) {
                wp_deregister_style($handle);
                wp_dequeue_style($handle);
            }
        }
    }

    private function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /*
     *
     * Register options needed for the options page.
     *
     */

    public function register_webinar_settings()
    {
        register_setting('wswebinar_options', '_wswebinar_enable_woocommerce_integration');
        register_setting('wswebinar_options', '_wswebinar_enable_theme_styles');
        register_setting('wswebinar_options', '_wswebinar_email_sentFrom');
        register_setting('wswebinar_options', '_wswebinar_email_senderAddress');
        register_setting('wswebinar_options', '_wswebinar_email_headerImg');
        register_setting('wswebinar_options', '_wswebinar_email_footerTxt');
        register_setting('wswebinar_options', '_wswebinar_email_baseCLR');
        register_setting('wswebinar_options', '_wswebinar_email_bckCLR');
        register_setting('wswebinar_options', '_wswebinar_email_bodyBck');
        register_setting('wswebinar_options', '_wswebinar_email_bodyTXT');
        register_setting('wswebinar_options', '_wswebinar_AdminEmailAddress');
        register_setting('wswebinar_options', '_wswebinar_email_templatereset');
        register_setting('wswebinar_options', '_wswebinar_newregcontent');
        register_setting('wswebinar_options', '_wswebinar_regconfirmcontent');
        register_setting('wswebinar_options', '_wswebinar_24hrb4content');
        register_setting('wswebinar_options', '_wswebinar_1hrb4content');
        register_setting('wswebinar_options', '_wswebinar_wbnstarted');
        register_setting('wswebinar_options', '_wswebinar_wbnreplay');
        register_setting('wswebinar_options', '_wswebinar_newregsubject');
        register_setting('wswebinar_options', '_wswebinar_regconfirmsubject');
        register_setting('wswebinar_options', '_wswebinar_24hrb4subject');
        register_setting('wswebinar_options', '_wswebinar_1hrb4subject');
        register_setting('wswebinar_options', '_wswebinar_wbnstartedsubject');
        register_setting('wswebinar_options', '_wswebinar_wbnreplaysubject');
        register_setting('wswebinar_options', '_wswebinar_newregenable');
        register_setting('wswebinar_options', '_wswebinar_regconfirmenable');
        register_setting('wswebinar_options', '_wswebinar_1hrb4enable');
        register_setting('wswebinar_options', '_wswebinar_24hrb4enable');
        register_setting('wswebinar_options', '_wswebinar_wbnstartedenable');
        register_setting('wswebinar_options', '_wswebinar_wbnreplayenable');
        register_setting('wswebinar_options', '_wswebinar_mailchimpapikey');
        register_setting('wswebinar_options', '_wswebinar_enormailapikey');
        register_setting('wswebinar_options', '_wswebinar_dripapikey');
        register_setting('wswebinar_options', '_wswebinar_getresponseapikey');
        register_setting('wswebinar_options', '_wswebinar_activecampaignapikey');
        register_setting('wswebinar_options', '_wswebinar_activecampaignurl');
        register_setting('wswebinar_options', '_wswebinar_subscription');
        register_setting('wswebinar_options', '_wswebinar_unsubscribe');
        register_setting('wswebinar_options', '_wswebinar_new_registration_webhook');
        register_setting('wswebinar_options', '_wswebinar_attended_webinar_webhook');
        register_setting('wswebinar_options', '_wswebinar_convertkit_key');
        register_setting('wswebinar_options', '_wswebinar_enable_logging');
        register_setting('wswebinar_options', '_wswebinar_log_key');
        register_setting('wswebinar_options', '_wswebinar_custom_webinar_slug', [
            'default' => 'webinars'
        ]);
        register_setting('wswebinar_options', '_wswebinar_enable_logging');
        register_setting('wswebinar_options', '_wswebinar_favicon_url');

        // storage for settings json
        register_setting('wswebinar_options', '_wswebinar_reduce_server_load');

        $this->registerPermissionSettings();
    }

    // Add the WebinarSysteem admin menus.
    public function register_menus() {
        add_menu_page(
            __('WebinarPress', '_wswebinar'),
            __('WebinarPress', '_wswebinar'),
            '_wswebinar_createwebinars',
            'wswbn-webinars',
            ['WebinarSysteemPages', 'webinar_list'],
            'none', 59
        );

        if (current_user_can('_wswebinar_createwebinars')) {
            add_submenu_page(
                "wswbn-webinars",
                __('Webinars', '_wswebinar'),
                __('Webinars', '_wswebinar'),
                '_wswebinar_createwebinars',
                'wswbn-webinars',
                ['WebinarSysteemPages', 'webinar_list']
            );

            add_submenu_page(
                "wswbn-webinars",
                __('New Webinar', '_wswebinar'),
                __('New Webinar', '_wswebinar'),
                '_wswebinar_createwebinars',
                'wswbn-webinar-editor',
                ['WebinarSysteemPages', 'new_webinar']
            );
        }

        add_submenu_page(
            "wswbn-webinars",
            __('Attendees', '_wswebinar'),
            __('Attendees', '_wswebinar'),
            '_wswebinar_managesubscribers',
            'wswbn-attendees',
            ['WebinarSysteemPages', 'attendees']
        );

        add_submenu_page(
            "wswbn-webinars",
            __('Questions', '_wswebinar'),
            __('Questions', '_wswebinar'),
            '_wswebinar_managesubscribers',
            'wswbn-questions',
            ['WebinarSysteemPages', 'questions']
        );

        add_submenu_page(
            "wswbn-webinars",
            __('Settings', '_wswebinar'),
            __('Settings', '_wswebinar'),
            '_wswebinar_managesubscribers',
            'wswbn-settings',
            ['WebinarSysteemPages', 'settings']
        );

        add_submenu_page(
            "wswbn-webinars",
            '<strong style="color:#39b143;">' . __('Upgrade to PRO', '_wswebinar').'</strong>',
            '<strong style="color:#39b143;">' . __('Upgrade to PRO', '_wswebinar').'</strong>',
            '_wswebinar_managesubscribers',
            'wswbn-upgrade',
            ['WebinarSysteemPages', 'redirect_to_pro_upgrade']
        );

        self::remove_admin_menu_links();

        remove_submenu_page(
            'edit.php?post_type=wswebinars',
            'edit.php?post_type=wswebinars'
        );
    }

    /*
     * Set required class variables.
     */
    protected function setAttributes($file = null)
    {
        global $wpdb;

        if (!empty($file)) {
            define('WSWEB_FILE', $this->_FILE_);
            define('WSWEB_OPTION_PREFIX', '_wswebnar_');
            define('WSWEB_DB_TABLE_PREFIX', $wpdb->prefix . 'wswebinars_');
            define('WSWEB_STORE_URL', 'https://webinarpress.com');
            define('WSWEB_ITEM_NAME', 'WebinarPress Pro');
        }

        $this->post_slug = 'wswebinars';
        $this->db_version = '1.0';

        self::$lang_slug = '_wswebinar';
    }

    /*
     * Register Webinar type
     */
    public function register_webinar_post_type() {
        // update WP rewrite rules
        $settings = WebinarSysteemSettings::instance();
        if ($settings->needs_flush_rewrite_rules()) {
            WebinarSysteemLog::log('Flushing rewrite rules');
            flush_rewrite_rules();
            $settings->set_needs_flush_rewrite_rules(false);
        }

        register_post_type($this->post_slug, array(
                'labels' => array(
                    'name' => __('Webinars', '_wswebinar'),
                    'singular_name' => __('Webinar', '_wswebinar'),
                    'name_admin_bar' => __('Webinar', '_wswebinar'),
                    'add_new' => __('Add New Webinar', '_wswebinar'),
                    'add_new_item' => __('Add New Webinar', '_wswebinar'),
                    'new_item' => __('New Webinar', '_wswebinar'),
                    'edit_item' => __('Edit Webinar', '_wswebinar'),
                    'view_item' => __('View Webinar', '_wswebinar'),
                ),
                'public' => true,
                'has_archive' => false,
                'show_in_menu' => false,
                'rewrite' => array('slug' => 'webinars', 'with_front' => false),
                'show_in_admin_bar' => true,
                'supports' => array('title', 'editor'),
                'capibility_type' => array('wswebinar', 'wswebinars'),
                'capabilities' => array(
                    'read_post' => 'read_wswebinar',
                    'edit_post' => 'edit_wswebinar',
                    'delete_post' => 'delete_wswebinar',
                    'publish_posts' => 'publish_wswebinars',
                    'edit_posts' => 'edit_wswebinars',
                    'edit_others_posts' => 'edit_others_wswebinars',
                    'read_private_posts' => 'read_private_wswebinars',
                    'delete_posts' => 'delete_wswebinars',
                ),
            )
        );
    }

    private function try_create_unsubscribe_page()
    {
        $pages = get_posts(array(
            'name' => 'webinar-unsubscribe',
            'orderby' => 'date',
            'order' => 'DESC',
            'post_type' => 'wpws_page',
        ));
        $page = null;
        if (!empty($pages)) {
            $page = array_shift($pages);
            if (empty($page->post_content)) {
                $page = null;
            }
        }

        if ($page === null) {
            $wpws_page_id = WebinarSysteemUserPages::create_unsubscribe_page();
        } else {
            $wpws_page_id = (int)$page->ID;
        }
        $subscription = get_option('_wswebinar_unsubscribe');
        if (!isset($subscription) || empty($subscription)) {
            update_option('_wswebinar_unsubscribe', $wpws_page_id);
        }

    }

    private function try_create_webinar_overview_page()
    {
        $pages = get_posts(array(
            'name' => 'webinar-overview',
            'orderby' => 'date',
            'order' => 'DESC',
            'post_type' => 'wpws_page',
        ));

        $page = null;

        if (!empty($pages)) {
            $page = array_shift($pages);
            if (empty($page->post_content)) {
                $page = null;
            }
        }

        if ($page === null) {
            $wpws_page_id = WebinarSysteemUserPages::create_webinar_overview_page();
        } else {
            $wpws_page_id = (int)$page->ID;
        }

        $wpws_overview = get_option('_wswebinar_overview');
        if (!isset($wpws_overview) || empty($wpws_overview)) {
            update_option('_wswebinar_overview', $wpws_page_id);
        }
    }

    /*
     * Plugin installation hook function.
     */
    public function install()
    {
        // setup webinar post type
        $this->register_webinar_post_type();

        // setup the widget post type
        WebinarSysteemRegistrationWidget::register_post_type();

        // setup role permissions
        WebinarSysteemPermissions::set_role_permissions();

        $this->try_create_unsubscribe_page();
        $this->try_create_webinar_overview_page();

        flush_rewrite_rules();

        // only show the drip pointer once, if the user cancels it don't show again next install
        $settings = WebinarSysteemSettings::instance();
        if (!$settings->has_shown_course_invite()) {
            $settings->set_has_shown_course_invite();
            WebinarSysteemSettings::instance()->set_show_course_invite();
        }
    }

    // TODO, do we need exact time and webinar time?
    public static function get_webinar_exact_time($webinar_id, $day, $time) {
        $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);

        if (!$webinar->is_recurring()) {
            return $webinar->get_one_time_datetime();
        }

        if ($day == 'rightnow') {
            return WebinarSysteemWebinar::get_now_in_webinar_timezone($webinar_id);
        }

        $webinar_time_zone = get_post_meta($webinar_id,
            '_wswebinar_timezoneidentifier', true);

        if (empty($webinar_time_zone)) {
            return WebinarSysteemDateTime::strtotime_for_wp("$day $time");
        }

        return WebinarSysteemDateTime::strtotime_with_timezone(
            "$day $time", $webinar_time_zone);
    }

    /*
     Register a user for this webinar, the caller can provider either an exact time or a day/time combo
     */

    public static function register_webinar_attendee(
        $webinar_id,
        $name,
        $email,
        $exact_time = null,
        $day = null,
        $time = null,
        $disable_notifications = false,
        $disable_admin_email = false,
        $disable_attendee_email = false,
        $args = []
    ) {
        if (!$webinar_id) {
            return null;
        }

        // set argument defaults
        $args = (object) array_merge([
            'login_current_browser' => true,
            'custom_fields' => null,
            'force_paid_new_registration_email' => false,
            'disable_resubscribe' => false
        ],
            (array) $args
        );

        WebinarSysteemLog::log("Registering '$name' ($email) for in webinar $webinar_id (day: $day, time: $time, date/time: $exact_time)");

        $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);
        $current_date_time = $webinar->get_now_in_timezone();

        /*
         * TODO, add exact_time_utc to attendees so we can just do one query for each
         *
         * Need to calculate using future date to take into account daylight savings changes that
         * can happen between when registered and webinar time
         */

        if ($exact_time == null) {
            $exact_time = WebinarSysteem::get_webinar_exact_time($webinar_id, $day, $time);
        }

        $rand = WebinarSysteemHelperFunctions::generate_uuid();

        $anonymous_email = $email === null ? 1 : 0;

        if ($email == null) {
            $email = WebinarSysteemHelperFunctions::generate_uuid().'@anonymous.com';
        }

        if ($name == null) {
            /*
            // Generate our anonymous name - convert to base 24
            $today = strtotime('today', time());
            $name = 'Anonymous '.strtoupper(base_convert(time() - $today, 10, 30));
            */

            $name = 'Anonymous '.ucfirst(WebinarSysteemHelperFunctions::readable_random_string(wp_rand(4, 6)));
        }

        $data = [
            'name' => $name === null ? null : trim($name),
            'email' => $email === null ? null : trim($email),
            // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            'time' => date('Y-m-d H:i:s', $current_date_time),
            // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            'exact_time' => date('Y-m-d H:i:s', $exact_time),
            'secretkey' => $rand,
            'webinar_id' => $webinar_id,
            'random_key' => self::random_string(20),
            'custom_fields' => $args->custom_fields,
            'watch_day' => null,
            'watch_time' => null,
            'seconds_attended' => 0,
            'attended' => false,
            'newly_registered' => 1,
            'anonymous_email' => $anonymous_email
        ];

        WebinarSysteemAttendees::add_or_update_attendee($data,
            ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d']);

        if ($args->login_current_browser) {
            WebinarSysteemWebinarSession::save_login(
                $webinar_id,
                $rand,
                $data['email'],
                $data['random_key']
            );
        }

        // get the new attendee
        $attendee = WebinarSysteemAttendees::get_attendee_by_email($email, $webinar_id);

        $emails = new WebinarSysteemEmails();

        if (!$disable_admin_email) {
            $emails->send_new_registration_email_to_admin($attendee);
        }

        // resubscribe the user if needed
        if (
            !$args->disable_resubscribe &&
            !$anonymous_email &&
            WebinarSysteemSettings::instance()->get_resubscribe_attendees_on_register()) {
            WebinarSysteemUnsubscribe::subscribe($email);
        }

        // send the new registration email if it is not a paid webinar
        if (!$disable_attendee_email && !$anonymous_email) {
            $emails->send_new_registration_email($attendee);
        }

        return $attendee;
    }

    public function render_zoom_frame_content() {
        // Set non-cachable in LightSpeed Cache
        if (defined('LITESPEED_ON') && !defined('LSCACHE_NO_CACHE')) define('LSCACHE_NO_CACHE', true);

        // disable caching in WP Super Cache and W3 Total Cache.
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_REQUEST['wpws_zoom_frame'])) {
            // Set the char set so we don't default
            header('Content-Type: text/html; charset=UTF-8');

            if (WebinarSysteemSettings::instance()->get_enable_optimized_zoom()) {
                header('Cross-Origin-Embedder-Policy: require-corp');
                header('Cross-Origin-Opener-Policy: same-origin');
            }
            include WebinarSysteemTemplates::get_path('zoom-frame.php');
            die();
        }
    }

    /*
     * Redirect the template url to Webinar custom template.
     */
    public function handle_webinar_template($original_template, $force_execute = false, $post_id = null) {
        global $wp;

        if (!$force_execute) {
            if (!isset($wp->query_vars["post_type"]) ||
                $wp->query_vars["post_type"] !== $this->post_slug ||
                defined('LSCACHE_IS_ESI')
            ) {
                return $original_template;
            }
        }

        // Set non-cachable in LightSpeed Cache
        if (defined('LITESPEED_ON') && !defined('LSCACHE_NO_CACHE')) define('LSCACHE_NO_CACHE', true);

        // disable caching in WP Super Cache and W3 Total Cache.
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        global $post;
        @$webinar_id = empty($post_id) ? $post->ID : $post_id;

        if (empty($webinar_id)) {
            wp_die(esc_html('Please save your webinar first before previewing.', '_wswebinar'));
        }

        $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);

        // are we registered?
        $attendee = WebinarSysteemAttendees::get_attendee($webinar_id);

        if ($attendee != null) {
            $newly_registered = $attendee->newly_registered == '1';
            $already_registered = !$newly_registered;

            if ($newly_registered) {
                WebinarSysteemAttendees::set_attendee_is_not_newly_registered($attendee);
            }
        } else {
            $already_registered = false;
            $newly_registered = false;
        }

        // if the thank you page is disabled and we are newly treat as already registered
        if ($newly_registered && $webinar->is_confirmation_page_disabled()) {
            $newly_registered = false;
            $already_registered = true;
        }

        $return_template = '';

        // define the webinar pages

        $registration_filename = $webinar->get_registration_page_template() == 'studio'
            ? WebinarSysteemTemplates::get_path('webinar-registration-wpwsjs.php')
            : WebinarSysteemTemplates::get_path('webinar-registration.php');

        $confirmation_filename = $webinar->get_confirmation_page_template() == 'studio'
            ? WebinarSysteemTemplates::get_path('webinar-confirmation-wpwsjs.php')
            : WebinarSysteemTemplates::get_path('webinar-confirmation.php');

        // which page template should we use?
        $live_page_template = $webinar->get_live_page_template();

        if ($live_page_template === 'studio') {
            // studio view
            $live_filename = WebinarSysteemTemplates::get_path('webinar-live-wpwsjs.php');
            $replay_filename = WebinarSysteemTemplates::get_path('webinar-live-wpwsjs.php');
            $closed_filename = WebinarSysteemTemplates::get_path('webinar-live-wpwsjs.php');
            $countdown_filename = WebinarSysteemTemplates::get_path('webinar-live-wpwsjs.php');
        } else {
            // classic view
            $live_filename = WebinarSysteemTemplates::get_path('webinar-live.php');
            $replay_filename = WebinarSysteemTemplates::get_path('webinar-live.php');
            $closed_filename = WebinarSysteemTemplates::get_path('webinar-closed.php');

            // send team members to the webinar page
            $countdown_filename = WebinarSysteemTemplates::get_path('webinar-countdown.php');
        }

        if ($newly_registered) {
            $return_template = $confirmation_filename;
        }

        // get current webinar state
        $webinar_status = $this->checkWebinarStatusForNow($webinar_id);

        // get the page for manual webinars
        if ($webinar->is_manual() && $already_registered) {
            $lookup = [
                'cou' => $countdown_filename,
                'liv' => $live_filename,
                'clo' => $closed_filename,
                'rep' => $replay_filename
            ];

            $return_template = $lookup[$webinar_status];
        }

        // Not registered
        if (!$already_registered && !$newly_registered) {
            $return_template = $registration_filename;
        }

        // Overwrite the displayed page
        $needs_auto_login_from_forced = false;
        $disable_auto_login = false;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['wspage']) && WebinarSysteemPermissions::can_create_webinars()) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            switch ($_GET['wspage']) {
                case 'register':
                    $return_template = $registration_filename;
                    $disable_auto_login = true;
                    break;

                case 'confirmation':
                    $return_template = $confirmation_filename;
                    break;

                case 'countdown':
                    $return_template = $countdown_filename;
                    break;

                case 'closed':
                    $return_template = $closed_filename;
                    break;

                case 'replay':
                    $return_template = $replay_filename;
                    break;

                case 'live':
                case 'webinar':
                    $needs_auto_login_from_forced = true;

                    if ($webinar->is_manual()) {
                        $return_template = $live_filename;
                    } else {
                        $return_template = $replay_filename;
                    }
                    break;

                default:
                    break;
            }
        }

        // Try to auto-login, if:
        //  1. If the attendee is not registered
        //  2. the webinar is over (recurring) then try to auto-register
        //  3. Team member is 'viewing' the webinar page

        if ($return_template == $registration_filename ||
            $needs_auto_login_from_forced) {

            // only try to auto-login if we don't already have an attendee
            if ($attendee == null && !$disable_auto_login) {
                self::try_auto_login_and_register_attendees();
            }
        }

        if ($return_template == $live_filename) {
            WebinarSysteemWebinarCache::write($webinar_id);

            // If we are using zoom then add the required headers to enable
            // SharedArrayBuffer which enables the gallary view
            if ($webinar->is_using_zoom() && WebinarSysteemSettings::instance()->get_enable_optimized_zoom()) {
                header('Cross-Origin-Embedder-Policy: require-corp');
                header('Cross-Origin-Opener-Policy: same-origin');
            }
        }

        if ($return_template == $registration_filename) {
            // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
            wp_enqueue_script(
                'wpws-registration',
                plugin_dir_url($this->_FILE_) . 'includes/js/registration.js',
                array('jquery',),
                WPWS_PLUGIN_VERSION);

            wp_localize_script('wpws-registration', 'wpws', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce(WebinarSysteemJS::get_nonce_secret())
            ));
        }

        include $return_template;
        die();
    }

    /*
     * Remove admin bar in Webinar pages
     */
    public function remove_admin_bar_from_webinars()
    {
        $post_types = get_post_type(get_the_ID());
        if ($post_types == 'wswebinars' && is_single()) {
            remove_action('wp_footer', 'wp_admin_bar_render', 1000);
            remove_action('wp_head', '_admin_bar_bump_cb');
            remove_action('wp_head', 'skt_itconsultant_custom_head_codes');
        }
    }

    /*
     * Set the Webinar views data
     */
    public static function setPostData($post_id)
    {
        $current = get_post_meta($post_id, '_wswebinar_views', true);
        if (empty($current)) {
            $current = 0;
        }

        $new = 1 + (int)$current;
        update_post_meta($post_id, '_wswebinar_views', $new);
    }

    private function checkWebinarStatusForNow($post_id)
    {
        $getStatus = get_post_meta($post_id, '_wswebinar_gener_webinar_status', true);
        if (empty($getStatus)) {
            $getStatus = 'cou';
        }

        return $getStatus;
    }

    /**
     * Check if webinar is automated
     *
     * @return bool
     */
    public static function isAutomated($webinar_id)
    {
        $air_type = self::webinarAirType($webinar_id);
        $gener_time_occur_saved = get_post_meta($webinar_id, '_wswebinar_gener_time_occur', true);

        if (!empty($gener_time_occur_saved) && $air_type == 'rec') {
            return true;
        }
        return false;
    }

    private function getWebinarStatusText($webinar_id)
    {

        if (WebinarSysteem::isAutomated($webinar_id)) {
            return __('Automated', '_wswebinar');
        }

        $stat = $this->checkWebinarStatusForNow($webinar_id);
        $string = '';
        switch ($stat) {
            case 'cou':
                $string = 'Countdown';
                break;
            case 'liv':
                $string = 'Live';
                break;
            case 'rep':
                $string = 'Replay';
                break;
            case 'clo':
                $string = 'Closed';
                break;
            default:
                break;
        }
        return $string;
    }

    public static function try_login_from_secret(
        $webinar_id,
        $secret,
        $email
    ) {
        global $wpdb;

        // if we are logging in to a recurring webinar
        $table = WebinarSysteemTables::get_subscribers();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $wpdb->prepare("SELECT * FROM $table WHERE webinar_id=%d AND email=%s AND secretkey=%s", $webinar_id, $email, $secret);

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $row = $wpdb->get_row($query);

        if (empty($row)) {
            return false;
        }

        $key = self::random_string(20);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->update(
            $table,
            ['random_key' => $key],
            ['id' => $row->id],
            ['%s'],
            ['%s']);

        WebinarSysteemWebinarSession::save_login($webinar_id, $secret, $email, $key);

        return true;
    }

    public static function is_already_registered_for_webinar(
        $post_id,
        $email,
        $exact_time = null
    ) {
        global $wpdb;

        $webinar = WebinarSysteemWebinar::create_from_id($post_id);

        $email = trim($email);
        $table = WebinarSysteemTables::get_subscribers();

        // if we are logging in to a recurring webinar
        if ($exact_time == null) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $query = $wpdb->prepare("SELECT * FROM $table WHERE email=%s AND webinar_id=%d", $email, $post_id);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $query = $wpdb->prepare("SELECT * FROM $table WHERE email=%s AND webinar_id=%d AND exact_time=%s", $email, $post_id, $exact_time);
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $attendee = $wpdb->get_row($query);

        if (empty($attendee)) {
            return false;
        }

        if ($webinar->is_automated()) {
            $recurring_session_is_valid = false;

            $now = $webinar->get_now_in_timezone();

            $webinar_start_time = WebinarSysteem::get_webinar_time($webinar->id, $attendee);
            $webinar_end_time = $webinar_start_time + $webinar->get_duration();

            // The webinar is currently live
            if ($now >= $webinar_start_time && $now <= $webinar_end_time) {
                $recurring_session_is_valid = true;
            }

            // The webinar hasn't started yet
            else if ($now < $webinar_start_time) {
                $recurring_session_is_valid = true;
            }

            // If automated replays are available then calculate the max time that the attendee can access,
            // we shouldn't need the 'studio' filter here but better to be safe because classic won't work
            else if (
                $webinar->get_automated_replay_enabled() && (
                    $webinar->get_automated_replay_available_duration() == 0 ||  // duration of 0 means forever
                    $now < $webinar_start_time + $webinar->get_automated_replay_available_duration()
                )) {
                $recurring_session_is_valid = true;
            }

            // The webinar has finished
            if (!$recurring_session_is_valid) {
                return false;
            }
        }

        // The email is already registered for this session
        $attendee_random_key = WebinarSysteemWebinarSession::get_registration_key($webinar->id);
        $this_browser = self::is_logged_in_with_this_browser($attendee_random_key, $post_id);

        // Update random key to this browser.
        if (!$this_browser) {
            $key = self::random_string(20);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->update(
                $table,
                ['random_key' => $key],
                ['id' => $attendee->id],
                ['%s'],
                ['%s']);

            $rand = wp_rand(888888, 889888);

            WebinarSysteemWebinarSession::save_login($post_id, $rand, $email, $key);
        }

        return true;
    }

    /*
     * Duplicate Webinar
     */
    public function wswebinar_duplicate_post_as_draft()
    {
        global $wpdb;
        
        // Verify the nonce
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (!isset($_GET['dup_wpnonce']) || !wp_verify_nonce($_GET['dup_wpnonce'], WebinarSysteemJS::get_nonce_secret())) {
            wp_die(esc_html('Nonce verification failed!'));
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing	
        if (!(isset($_GET['post']) || isset($_POST['post']) || (isset($_REQUEST['action']) && 'wswebinar_duplicate_post_as_draft' == $_REQUEST['action']))) {
            wp_die(esc_html('No Webinar to duplicate has been supplied!'));
        }

        /*
         * get the original post id
         */
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing	
        $post_id = intval(isset($_GET['post']) ? $_GET['post'] : $_POST['post']);

        if ($post_id == 0) {
            wp_die(esc_html('No Webinar to duplicate has been supplied!'));
        }
        /*
         * and all the original post data then
         */
        $post = get_post($post_id);

        /*
         * if you don't want current user to be the new post author,
         * then change next couple of lines to this: $new_post_author = $post->post_author;
         */
        $current_user = wp_get_current_user();
        $new_post_author = $current_user->ID;

        /*
         * if post data exists, create the post duplicate
         */
        if (isset($post) && $post != null) {

            $post_title = 'Copy of ' . $post->post_title;

            /*
             * new post data array
             */
            $args = array(
                'comment_status' => $post->comment_status,
                'ping_status' => $post->ping_status,
                'post_author' => $new_post_author,
                'post_content' => $post->post_content,
                'post_excerpt' => $post->post_excerpt,
                'post_name' => $post->post_name,
                'post_parent' => $post->post_parent,
                'post_password' => $post->post_password,
                'post_status' => 'draft',
                'post_title' => $post_title,
                'post_type' => $post->post_type,
                'to_ping' => $post->to_ping,
                'menu_order' => $post->menu_order,
            );

            /*
             * insert the post by wp_insert_post() function
             */
            $new_post_id = wp_insert_post($args);

            /*
             * get all current post terms ad set them to the new post draft
             */
            $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
            foreach ($taxonomies as $taxonomy) {
                $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
            }

            /*
             * duplicate all post meta
             */
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $post_meta_infos = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=%d",
                    $post_id
                )
            );

            if (count($post_meta_infos) != 0) {
                $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                foreach ($post_meta_infos as $meta_info) {
                    $meta_key = $meta_info->meta_key;
                    $meta_value = addslashes($meta_info->meta_value);
                    $sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
                }
                $sql_query .= implode(" UNION ALL ", $sql_query_sel);
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->query($sql_query);
                delete_post_meta($new_post_id, '_wswebinar_views');
            }

            // if the webinar is paid, create a new woo commerce product
            $webinar = WebinarSysteemWebinar::create_from_id($new_post_id);

            // change the cache key
            $webinar->reset_cache_key();

            // set the new slug
            $webinar->set_slug($post_title);

            /*
             * finally, redirect to the edit post screen for the new draft
             */
            wp_redirect(admin_url('admin.php?page=wswbn-webinars&webinar_id=' . $new_post_id));
            exit;
        } else {
            wp_die(esc_html('Webinar creation failed, could not find original Webinar: ' . $post_id));
        }
    }

    public static function getYoutubeIdFromUrl($link)
    {
        preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $link, $matches);
        if (!empty($matches[0])) {
            return $matches[0];
        }

        return false;
    }

    public static function webinarAirType($webinar_id)
    {
        $gener_air_type_saved = get_post_meta($webinar_id, '_wswebinar_gener_air_type', true);
        if (empty($gener_air_type_saved)) {
            $gener_air_type_saved = 'live';
        }

        return $gener_air_type_saved;
    }

    public static function is_recurring_webinar($webinar_id)
    {
        $air_type = self::webinarAirType($webinar_id);
        $gener_time_occur_saved = get_post_meta($webinar_id, '_wswebinar_gener_time_occur', true);
        if (!empty($gener_time_occur_saved) && $air_type == 'rec' && ($gener_time_occur_saved == 'recur' || $gener_time_occur_saved == 'jit')) {
            return true;
        }

        return false;
    }

    public static function getRecurringInstances($webinar_id, $short = false)
    {
        $gener_rec_days_array = array();
        $gener_rec_days_saved = get_post_meta($webinar_id, '_wswebinar_gener_rec_days', true);
        if (!empty($gener_rec_days_saved)) {
            $gener_rec_days_array = json_decode($gener_rec_days_saved, true);
        }

        $gener_rec_times_saved = get_post_meta($webinar_id, '_wswebinar_gener_rec_times', true);
        $gener_rec_times_array = array();
        if (!empty($gener_rec_times_saved)) {
            $gener_rec_times_array = json_decode($gener_rec_times_saved, true);
        }

        if ($short){
            return array('days' => $gener_rec_days_array, 'times' => $gener_rec_times_array);
        }

        $timestamp_collection = array();
        // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
        $date = date('Y-m-d', time());
        foreach ($gener_rec_times_array as $time) {
            if ($time != 'rightnow') {
                $timestamp = strtotime($date . ' ' . $time);
                array_push($timestamp_collection, $timestamp);
            } else {
                // Equal to 'rightnow';
                array_push($timestamp_collection, $time);
            }
        }
        sort($timestamp_collection);
        // return array('days' => $gener_rec_days_array, 'times' => $gener_rec_times_array);
        return array('days' => $gener_rec_days_array, 'times' => $timestamp_collection);
    }

    public static function getJustinTimeInstances($webinar_id)
    {
        $gener_jit_days_array = array();
        $gener_jit_days_saved = get_post_meta($webinar_id, '_wswebinar_gener_jit_days', true);
        if (!empty($gener_jit_days_saved)) {
            $gener_jit_days_array = json_decode($gener_jit_days_saved, true);
        }

        $gener_jit_times_saved = get_post_meta($webinar_id, '_wswebinar_gener_jit_times', true);
        $gener_jit_times_array = array();
        $gener_jit_time = filter_var($gener_jit_times_saved, FILTER_SANITIZE_NUMBER_INT);

        for ($x = 0; $x < 24; $x++) {
            for ($y = 0; $y < 60; $y += $gener_jit_time) {
                $time = $x . ':' . $y;
                array_push($gener_jit_times_array, $time);
            }
        }

        return array('days' => $gener_jit_days_array, 'times' => $gener_jit_times_array);
    }

    /*
     * Return the plugin information
     */
    public static function plugin_info($needs = false)
    {
        $plugin_info = get_plugin_data(WSWEB_FILE);
        return ($needs == false ? $plugin_info : $plugin_info[$needs]);
    }

    /*
     *
     * Webinar Meta box content loader
     *
     */

    public static function getWeekDayArray($req = '')
    {
        $arr = array(
            'mon' => __('Monday', '_wswebinar'),
            'tue' => __('Tuesday', '_wswebinar'),
            'wed' => __('Wednesday', '_wswebinar'),
            'thu' => __('Thursday', '_wswebinar'),
            'fri' => __('Friday', '_wswebinar'),
            'sat' => __('Saturday', '_wswebinar'),
            'sun' => __('Sunday', '_wswebinar'),
        );
        if (empty($req))
            return $arr;
        return $arr[$req];
    }

    /*
     * Return recurring time integers
     */
    public static function getRecurringInstancesInTime($webinar_id)
    {
        $array = array();
        $date_format = self::get_wp_datetime_formats(self::$WP_DATE_FORMAT);
        $time_format = self::get_wp_datetime_formats(self::$WP_TIME_FORMAT);
        $ins = self::getRecurringInstances($webinar_id);
        if (count($ins['days']) < 1 || count($ins['times']) < 1) {
            return $array;
        }

        foreach ($ins['days'] as $day) {
            foreach ($ins['times'] as $time) {
                if ($time != 'rightnow') {
                    $time = $time + 0;
                    // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                    $humanTime = date($time_format, $time);
                    // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                    $humanDate = date($date_format, strtotime($day));
                    array_push($array, array('day' => $day, 'time' => $time, 'datetime' => self::getWeekDayArray($day) . ' ' . $humanTime, 'date' => $humanDate));
                }
            }
        }

        return $array;
    }

    /*
     * Return recurring JIT time integers
     */
    public static function getJITInstancesInTime($webinar_id)
    {

        $array = array();
        $date_format = self::get_wp_datetime_formats(self::$WP_DATE_FORMAT);
        $time_format = self::get_wp_datetime_formats(self::$WP_TIME_FORMAT);
        $ins = self::getJustinTimeInstances($webinar_id);

        if (count($ins['days']) < 1 || count($ins['times']) < 1) {
            return $array;
        }
        foreach ($ins['days'] as $day) {
            foreach ($ins['times'] as $time) {
                $time = strtotime($time);
                $time = $time + 0;

                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                $humanTime = date($time_format, $time);
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                $humanDate = date($date_format, strtotime($day));
                array_push($array, array('day' => $day, 'time' => $time, 'datetime' => self::getWeekDayArray($day) . ' ' . $humanTime, 'date' => $humanDate));
            }
        }
        return $array;
    }

    public static function get_webinar_time($webinar_id, $attendee = null)
    {
        $webinar = WebinarSysteemWebinar::create_from_id($webinar_id);

        if ($webinar->is_recurring()) {
            if (empty($attendee)) {
                return null;
            }
            return strtotime($attendee->exact_time);
        }

        return $webinar->get_one_time_datetime();
    }

    public static function getWebinarTimezone($webinar_id)
    {
        $timeabbr = get_post_meta($webinar_id, '_wswebinar_timezoneidentifier', true);
        $wpoffset = get_option('gmt_offset');
        $gmt_offset = WebinarSysteemDateTime::format_timezone(($wpoffset > 0) ? '+' . $wpoffset : $wpoffset);
        $timeZone = ((!empty($timeabbr)) ? WebinarSysteemDateTime::get_timezone_abbreviation($timeabbr) : 'UTC ' . $gmt_offset);

        return $timeZone;
    }

    /**
     * Gets attendee registered time in webinar timezone.
     *
     * @param  $webinar_id
     * @param  string $format
     * @return string formatted current time
     */
    public static function getTimezoneTime($webinar_id, $format = null)
    {
        $time_zone = get_post_meta($webinar_id, '_wswebinar_timezoneidentifier', true);
        if ($format == null) {
            $format = 'Y-m-d H:i:s';
        }

        if (empty($time_zone)) {
            return current_time(($format == null ? 'timestamp' : $format));
        }

        if ($time_zone) {
            try {
                $date = new DateTime();
                $date->setTimezone(new DateTimeZone($time_zone));
                return $date->format($format);
            } catch (Exception $e) {
                return false;
            }
        }
    }

    public static function getWebinarDuration($webinar_id)
    {
        $_wswebinar_gener_duration = get_post_meta($webinar_id, '_wswebinar_gener_duration', true);
        if (empty($_wswebinar_gener_duration)) {
            $_wswebinar_gener_duration = 3600;
        }

        $_wswebinar_gener_duration = floatval($_wswebinar_gener_duration);
        return $_wswebinar_gener_duration;
    }

    public function registerPermissionSettings()
    {
        global $wp_roles;
        $roles = $wp_roles->get_names();
        foreach ($roles as $roleSlug => $roleName) {
            register_setting('wswebinar_options', '_wswebinar_createwebinars_' . $roleSlug);
            register_setting('wswebinar_options', '_wswebinar_managesubscribers_' . $roleSlug);
            register_setting('wswebinar_options', '_wswebinar_accesscontrolbar_' . $roleSlug);
            register_setting('wswebinar_options', '_wswebinar_managequestions_' . $roleSlug);
            register_setting('wswebinar_options', '_wswebinar_managechatlogs_' . $roleSlug);
            register_setting('wswebinar_options', '_wswebinar_webinarsettings_' . $roleSlug);
            register_setting('wswebinar_options', '_wswebinar_manageregforms_' . $roleSlug);
        }
    }



    /**
     * Remove admin menu links based on user role
     */
    public function remove_admin_menu_links()
    {
        $user = wp_get_current_user();
        $user_role = $user->roles ? $user->roles[0] : false;
        if ($user_role === 'wpws_webinar_moderator') {
            remove_menu_page('tools.php');
            remove_menu_page('edit.php');
            remove_menu_page('options-general.php');
            remove_menu_page('admin.php?page=wswbn-regforms');
        }

        if (!WebinarSysteemPermissions::can_manage_attendees()) {
            remove_menu_page('admin.php?page=wswbn-attendees');
        }

        if (!WebinarSysteemPermissions::can_manage_questions()) {
            remove_menu_page('admin.php?page=wswbn-questions');
        }

        if (!WebinarSysteemPermissions::can_manage_chats()) {
            remove_menu_page('admin.php?page=wswbn-questions');
        }

        if (!WebinarSysteemPermissions::can_manage_settings()) {
            remove_menu_page('admin.php?page=wswbn-settings');
        }
    }

    public function liveControlBar()
    {
        global $is_live_page;

        $webinar_id = get_the_ID();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $status = !empty($_GET['page']) ? $_GET['page'] : get_post_meta($webinar_id, '_wswebinar_gener_webinar_status', true);
        $page = ($status == 'live' || $status == 'liv') ? 'livep_' : 'replayp_';
        $show_chatbox = get_post_meta($webinar_id, '_wswebinar_' . $page . 'show_chatbox', true);
        $show_questionbox = get_post_meta($webinar_id, '_wswebinar_' . $page . 'askq_yn', true);
        $isMediaElementJs = !in_array(get_post_meta($webinar_id, '_wswebinar_' . $page . 'vidurl_type', true), array('image', 'vimeo', 'iframe', 'youtubelive'));
        $has_permission_to_show = (current_user_can('manage_options') || current_user_can('_wswebinar_accesscbar'));

        $CTA_action = get_post_meta($webinar_id, '_wswebinar_' . $page . 'call_action', true);
        $isManualCTA = ($CTA_action == 'manual');
        $show_cta_status = get_post_meta($webinar_id, '_wswebinar_' . $page . 'manual_show_cta', true);
        $actionbox_status = get_post_meta($webinar_id, '_wswebinar_' . $page . 'show_actionbox', true);
        $show_hostb = get_post_meta($webinar_id, '_wswebinar_' . $page . 'hostbox_yn', true);
        $show_descb = get_post_meta($webinar_id, '_wswebinar_' . $page . 'webdes_yn', true);
        $show_inctv = get_post_meta($webinar_id, '_wswebinar_' . $page . 'incentive_yn', true);

        if (!isset($is_live_page) || !$is_live_page || !$has_permission_to_show) {
            return;
        }

        ?>
        <div id="webinar-actionbar">
            <ul>
                <li>
                    <a href="#" id="livep-play-button"
                       class="wbn-icon wbnicon-play <?php if (!$isMediaElementJs) { ?>disable-hover<?php } ?>"></a>
                </li>
            </ul>

            <ul class="webinar-admin-chatico">
                <li class="tooltip-livep cusrsor-pointer" data-toggle="tooltip" data-placement="bottom" title=""
                    data-original-title="<?php esc_html_e('Host and Description Box', '_wswebinar'); ?>">
                    <a style="padding-top: 10px;" href="#" id="show_multi_boxes"
                       class="text-center fa fa-info <?php echo($show_hostb == 'yes' | $show_descb == 'yes' ? 'message-center-newmsg' : ''); ?>"></a>
                </li>
                <li class="tooltip-livep cusrsor-pointer" data-toggle="tooltip" data-placement="bottom" title=""
                    data-original-title="<?php esc_html_e('Question Box', '_wswebinar'); ?>">
                    <a href="#" id="webinar_show_questionbox" data-webinarid="<?php echo esc_attr($webinar_id); ?>"
                       class="icon fa fa-question <?php echo($show_questionbox == 'yes' ? 'message-center-newmsg' : ''); ?>"
                       style="font-size: 18px; padding-top: 7px; margin-top: 0px;"></a>
                </li>
                <li class="tooltip-livep cusrsor-pointer" data-toggle="tooltip" data-placement="bottom" title=""
                    data-original-title="<?php esc_html_e('Message Center', '_wswebinar'); ?>">
                    <a href="#" class="icon webi-class-comments webinar-message-center"></a>
                    <ul id="wswebinar_private_que" style="display: none;"></ul>
                </li>
                <li class="tooltip-livep cusrsor-pointer" data-toggle="tooltip" data-placement="bottom" title=""
                    data-original-title="<?php esc_html_e('Incentive Box', '_wswebinar'); ?>">
                    <a href="#" class="glyphicon glyphicon-gift" id="gift_icon" style="padding-top: 9px;top: 0;"></a>
                </li>
            </ul>
            <ul class="right-column pull-right">
                <li>
                    <a href="#" class="webinar_live_viewers">
                        <span id="webinar-live-viewers-icon"></span>
                        <span id="webinar-live-viewers">0</span>
                    </a>
                    <ul id="attendee-online-list"></ul>
                </li>
                <li>
                    <a href="#" class="disable-hover">Status :
                        <span class='status-text'>
                            <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php echo $this->getWebinarStatusText(get_the_ID()); ?>
                        </span>
                    </a>
                </li>
            </ul>
        </div>
        <?php
    }

    public static function getTimezone()
    {
        $gmt_opt = get_option('gmt_offset');
        $hourint = (int)$gmt_opt;
        $xyz = ($hourint > 0 ? '+' : '');
        $float = $gmt_opt - intval($gmt_opt);
        if ($float == 0) {
            $timezone = '00';
        } else if ($float == 0.5) {
            $timezone = '30';
        } else if ($float == 0.75) {
            $timezone = '45';
        } else {
            $timezone = '00';
        }
        $timezone_string = get_option('timezone_string');
        return $xyz . $hourint . ':' . $timezone . (empty($timezone_string) ? '' : " ($timezone_string)");
    }

    public static function get_available_timezones()
    {
        $time_zones = timezone_identifiers_list();
        $time_to_use = 'now'; # just a dummy time
        $time_zone_abbreviations = array();

        foreach ($time_zones as $time_zone_id) {
            try {
                $dateTime = new DateTime($time_to_use);
                $dateTime->setTimeZone(new DateTimeZone($time_zone_id));
                $abbreviation = $dateTime->format('T');
                $gmtoffset = $dateTime->format('P');

                if (!isset($time_zone_abbreviations[$abbreviation])) {
                    $time_zone_abbreviations[$time_zone_id] = $time_zone_id . ' - ' . $abbreviation;
                }
            } catch (Exception $exc) {
                continue;
            }
        }

        return $time_zone_abbreviations;
    }

    public static function is_webinarpress_page()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['page'])) {
            $find = 'wswbn';

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            if (substr($_GET['page'], 0, strlen($find)) === $find) {
                return true;
            }
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return isset($_GET['post_type']) && $_GET['post_type'] == 'wswebinars';
    }

    public static function get_custom_fields_from_request($postId)
    {
        $webinar = WebinarSysteemWebinar::create_from_id($postId);
        if ($webinar == null) {
            return [];
        }

        $fields = $webinar->get_custom_fields();

        $data = array();

        if (empty($fields)) {
            return wp_json_encode($data);
        }

        foreach ($fields as $field) {
            $value = null;

            // Support legacy format
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_REQUEST["ws-{$field->id}"])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $value = $_REQUEST["ws-{$field->id}"];
            }

            // Support new format from auto reg links
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_REQUEST["custom_{$field->id}"])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $value = $_REQUEST["custom_{$field->id}"];
            }

            if ($value == null) {
                // We have a required field that was no provided!
                if ($field->isRequired) {
                    return null;
                }
            }

            if ($field->type == 'checkbox') {
                $data[] = array(
                    'id' => $field->id,
                    'value' => $value
                        ? 'Yes'
                        : 'No'
                );
                continue;
            }

            $data[] = array(
                'id' => $field->id,
                'value' => $value
            );
        }

        return wp_json_encode($data);
    }

    public function postNotices()
    {
        $usermeta = get_user_meta(get_current_user_id(), '_wswebinar_postnotdismiss', true);
        if ($usermeta == 'yes') {
            return;
        }
        $args = array(
            'post_type' => 'wswebinars',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'ignore_sticky_posts' => 1,
        );

        $query_posts = get_posts($args);

        foreach ($query_posts as $post) {
            setup_postdata($post);
            $date_entered = get_post_meta($post->ID, '_wswebinar_gener_date', true);

            if (empty($date_entered) && !$this->is_recurring_webinar($post->ID)) {

                ?>
                <div class="error wswebinar_adnotice wswebinar_adnotice_post">
                    <p>Please configure a time and date for your webinar <a
                                href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" rel="bookmark"
                                title="Permanent Link to <?php the_title_attribute(array('post' => $post->ID)); ?>"><?php echo esc_html(get_the_title($post->ID)); ?></a>
                        to work properly.</p>
                    <div class="closeIcon">
                        <i class="wbn-icon wbnicon-close close_post_notification"></i>
                    </div>
                </div>

                <?php
            }


            wp_reset_postdata();
            wp_reset_query();
        }
    }

    public function check_mysql_and_php_versions() {
        if (!get_option('wpws-admin-notice')) {
            return;
        }

        if (WebinarSysteemRequirements::is_database_version_out_of_date()) {
            ?>
            <div class="error wswebinar_adnotice">
                <p><?php esc_html_e('Your MySQL version is out of date, and some functionalities of WebinarPress won\'t work as expected. Please upgrade the MySQL version on your server to a minimum of MySQL 5.5.50 or higher.', '_wswebinar') ?></p>
            </div>
            <?php
        }

        if (WebinarSysteemRequirements::is_php_version_out_of_date()) { ?>
            <div class="error wswebinar_adnotice">
                <p><?php esc_html_e('Your PHP version is out of date, and some functionalities of WebinarPress won\'t work as expected. Please upgrade the PHP version on your server to a minimum of PHP 5.6 or higher.', '_wswebinar') ?></p>
            </div>
            <?php
        }
    }

    public function wpwsPluginNoticeIgnore()
    {
        global $current_user;
        $user_id = $current_user->ID;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['wpws-db-ignore-notice'])) {

            add_user_meta($user_id, 'wpws_db_plugin_notice_ignore', 'true', true);

        } 
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        else if (isset($_GET['wpws-php-ignore-notice'])) {
            add_user_meta($user_id, 'wpws_php_plugin_notice_ignore', 'true', true);
        }
    }

    public static function isRightnow($webinar_id)
    {
        if (self::is_recurring_webinar($webinar_id)) {
            $time_slots = get_post_meta($webinar_id, '_wswebinar_gener_rec_times', true);
            if (!empty($time_slots)) {
                $slot_ar = json_decode($time_slots);
                return (in_array('rightnow', $slot_ar));
            }
        }
        return false;
    }

    public function getNextRecurringTime($webinar_id)
    {
        $isRecurring = self::is_recurring_webinar($webinar_id);
        $isRightnow = self::isRightnow($webinar_id);
        $gener_time_occur_saved = get_post_meta($webinar_id, '_wswebinar_gener_time_occur', true);
        $value = '';
        if ($isRecurring) {
            if ($gener_time_occur_saved == 'recur' && !$isRightnow) {
                $recurr_instances = $this->getRecurringInstances($webinar_id);
                $date_format = get_option('date_format');
                $time_format = WebinarSysteem::get_wp_datetime_formats(WebinarSysteem::$WP_TIME_FORMAT);
                $date_time_instances_unix = array();
                $filtered_date_time = array();

                foreach ($recurr_instances['days'] as $day) {
                    $day_string = "next $day";
                    // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                    if (strtolower(date('D')) == $day) {
                        $day_string = "this $day";
                    }

                    $day = strtotime($day_string);
                    // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                    $date = date($date_format, strtotime('today ' . date("D", $day)));

                    foreach ($recurr_instances['times'] as $time) {
                        // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                        $time = date($time_format, $time);
                        $date_time_instances_unix[] = strtotime($date . "" . $time);
                    }
                }
                $cur_time = WebinarSysteemWebinar::get_now_in_webinar_timezone($webinar_id);

                foreach ($date_time_instances_unix as $key) {
                    if ($cur_time < $key) {
                        $filtered_date_time[] = $key;
                    }
                }
                sort($filtered_date_time);
                if ($filtered_date_time) {
                    $value = $filtered_date_time[0];
                }

                return $value;

            }
        }
    }

    public function getNextJITRecurringTime($webinar_id)
    {
        $jit_instances = $this->getJustinTimeInstances($webinar_id);
        $date_format = get_option('date_format');
        $time_format = WebinarSysteem::get_wp_datetime_formats(WebinarSysteem::$WP_TIME_FORMAT);
        $date_time_instances_unix = array();
        $filtered_date_time = array();
        $value = '';

        foreach ($jit_instances['days'] as $day) {
            $day_string = "next $day";

            // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            if (strtolower(date('D')) == $day) {
                $day_string = "this $day";
            }

            $day = strtotime($day_string);

            // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            $date = date($date_format, strtotime('today ' . date("D", $day)));

            foreach ($jit_instances['times'] as $time) {
                $time = strtotime($time);
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                $time = date($time_format, $time);
                $date_time_instances_unix[] = strtotime($date . "" . $time);
            }
        }

        $cur_time = WebinarSysteemWebinar::get_now_in_webinar_timezone($webinar_id);

        foreach ($date_time_instances_unix as $key) {
            if ($cur_time < $key) {
                $filtered_date_time[] = $key;
            }
        }

        sort($filtered_date_time);

        if ($filtered_date_time) {
            $value = $filtered_date_time[0];
        }

        return $value;
    }

    public static function get_wp_datetime_formats($WP_TIME_ANNOT)
    {
        $time_format = get_option('time_format');
        $date_format = get_option('date_format');

        if ($WP_TIME_ANNOT == self::$WP_DATE_FORMAT) {
            return $date_format;
        }

        if ($WP_TIME_ANNOT == self::$WP_TIME_FORMAT) {
            return $time_format;
        }

        if ($WP_TIME_ANNOT == self::$WP_DATE_TIME_FORMAT) {
            return $date_format . ' ' . $time_format;
        }

        return null;
    }

    public function webinarExcludePlugins($plugins)
    {
        // We are not in Webinar Ajax
        if (!defined('DOING_AJAX') || !DOING_AJAX || !defined('DOING_WEBINAR_AJAX') || !DOING_WEBINAR_AJAX) {
            return $plugins;
        }

        foreach ($plugins as $key => $plugin) {
            if (false === strpos($plugin, 'wpwebinarsystem')) {
                unset($plugins[$key]);
            }
        }
        return $plugins;
    }

    public static function get_timezone_str_by_utc_offset($offset)
    {
        /*
         * Required Offset : -5:30
         */
        list($hours, $minutes) = explode(':', $offset);
        $seconds = $hours * 60 * 60 + $minutes * 60;
        $tz = timezone_name_from_abbr('', $seconds, 1);
        if ($tz === false) {
            $tz = timezone_name_from_abbr('', $seconds, 0);
        }
        return $tz;
    }

    /**
     * Returns a random string.
     *
     * @return String
     */
    public static function random_string($length = 10)
    {
        $characters = '0123456789';
        $randstring = '';
        for ($i = 0; $i < $length; $i++) {
            @$randstring = $randstring . $characters[wp_rand(0, strlen($characters))];
        }
        return $randstring;
    }

    /**
     * Returns unique browser or not
     *
     * @param {String} random code from cookie
     * @return boolean
     */
    private static function is_logged_in_with_this_browser($rand, $post_id)
    {
        global $wpdb;
        $table = WebinarSysteemTables::get_subscribers();

        // make sure we have a valid value
        if (empty($rand) || !$rand) {
            return false;
        }

        // find this attendee
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $wpdb->prepare("SELECT * FROM $table WHERE webinar_id=%d and random_key=%s", $post_id, $rand);

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $row = $wpdb->get_row($query);

        return !empty($row);
    }

    /*
    * Create new role webinar moderator
    */
    public function createRoles() {
        add_role('wpws_webinar_moderator', 'Webinar Moderator', array(
            'read' => true,
            'manage_options' => true,
            'edit_others_posts' => true,
            'delete_others_posts' => true,
            'delete_private_posts' => true,
            'edit_private_posts' => true,
            'read_private_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'delete_published_posts' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            '_wswebinar_createwebinars' => true,
            '_wswebinar_managesubscribers' => true,
            '_wswebinar_managequestions' => true,
            '_wswebinar_managechatlogs' => true,
            '_wswebinar_webinarsettings' => true,
            'publish_wswebinars' => true,
            'edit_wswebinars' => true,
            'edit_others_wswebinars' => true,
            'read_private_wswebinars' => true,
            'read_wswebinar' => true,
            'edit_wswebinar' => true,
            'delete_wswebinar' => true,
            'delete_wswebinars' => true,
            '_wswebinar_manageregforms' => true,
        ));

        $is_createwebinars_wpws_webinar_moderator = get_option('_wswebinar_createwebinars_wpws_webinar_moderator');
        $is_managesubscribers_wpws_webinar_moderator = get_option('_wswebinar_managesubscribers_wpws_webinar_moderator');
        $is_managequestions_wpws_webinar_moderator = get_option('_wswebinar_managequestions_wpws_webinar_moderator');
        $is_managechatlogs_wpws_webinar_moderator = get_option('_wswebinar_managechatlogs_wpws_webinar_moderator');
        $is_changesettings_wpws_webinar_moderator = get_option('_wswebinar_webinarsettings_wpws_webinar_moderator');
        $is_accesscontrolbar_wpws_webinar_moderator = get_option('_wswebinar_accesscontrolbar_wpws_webinar_moderator');

        if (!isset($is_createwebinars_wpws_webinar_moderator) || (isset($is_createwebinars_wpws_webinar_moderator) && $is_createwebinars_wpws_webinar_moderator !== "off")) {
            update_option('_wswebinar_createwebinars_wpws_webinar_moderator', 'on');
        }

        if (!isset($is_managesubscribers_wpws_webinar_moderator) || (isset($is_managesubscribers_wpws_webinar_moderator) && $is_managesubscribers_wpws_webinar_moderator !== "off")) {
            update_option('_wswebinar_managesubscribers_wpws_webinar_moderator', 'on');
        }

        if (!isset($is_managequestions_wpws_webinar_moderator) || (isset($is_managequestions_wpws_webinar_moderator) && $is_managequestions_wpws_webinar_moderator !== "off")) {
            update_option('_wswebinar_managequestions_wpws_webinar_moderator', 'on');
        }

        if (!isset($is_managechatlogs_wpws_webinar_moderator) || (isset($is_managechatlogs_wpws_webinar_moderator) && $is_managechatlogs_wpws_webinar_moderator !== "off")) {
            update_option('_wswebinar_managechatlogs_wpws_webinar_moderator', 'on');
        }

        if (!isset($is_changesettings_wpws_webinar_moderator) || (isset($is_changesettings_wpws_webinar_moderator) && $is_changesettings_wpws_webinar_moderator !== "off")) {
            update_option('_wswebinar_webinarsettings_wpws_webinar_moderator', 'on');
        }

        if (!isset($is_accesscontrolbar_wpws_webinar_moderator) || (isset($is_accesscontrolbar_wpws_webinar_moderator) && $is_accesscontrolbar_wpws_webinar_moderator !== "off")) {
            update_option('_wswebinar_accesscontrolbar_wpws_webinar_moderator', 'on');
        }
    }

    public function purgeRoles(){
        remove_role('wpws_webinar_moderator');
    }

    public function wpwsAdminBarRender() {
        global $wp_admin_bar;

        if (!current_user_can('_wswebinar_createwebinars')) {
            $wp_admin_bar->remove_menu('new-wswebinars');
        }
    }

    /*
    * Allow Privileged roles to edit Webinar Settings
    */
    public function wswebinarOptionsPageCapability() {
        return 'read';
    }

    // TODO, move this into a separate class when refactoring WebinarSystem
    // http://wp1:8888/webinars/recurring/?auto_register=1&attendee_name=Mike&attendee_email=miked@antfx.com
    public function try_auto_login_and_register_attendees() {
        global $post;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $request = (object)$_REQUEST;

        $args_to_remove = [
            'auto_register',
            'attendee_name',
            'attendee_email',
            'session_date',
            'session_day',
            'session_time',
            'disable_notifications',
            'auth',
            'confirm',
            'verify'];

        // Attempt to login via a token for links in email
        if (isset($request->auth)) {
            // try to auto-login
            $auth_data = WebinarSysteemBase64::decode_array($request->auth);;

            if ($auth_data != null && is_array($auth_data) && count($auth_data) == 2) {
                self::try_login_from_secret(
                    $post->ID,
                    $auth_data[0],
                    $auth_data[1]
                );
            }

            wp_redirect(remove_query_arg($args_to_remove));
            die();
        }

        // Attempt to login via a token for links in email
        if (isset($request->confirm) && isset($request->verify)) {
            // try to auto-login
            $confirm_data = WebinarSysteemBase64::decode_array($request->confirm);

            if (
                $confirm_data != null && // we have data
                is_array($confirm_data) && // it's an array
                count($confirm_data) == 4 && // its the right size
                is_int($confirm_data[2]) && // the session time is a number
                $request->verify == WebinarSysteemHashes::get_secure_hash($request->confirm) // the security code is ok
            ) {
                $name = sanitize_text_field($confirm_data[0]);
                $email = sanitize_text_field($confirm_data[1]);
                $exact_time = $confirm_data[2];

                // are we already registered?
                // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                $is_registered = self::is_already_registered_for_webinar($post->ID, $email, date('Y-m-d H:i:s', $exact_time));

                if (!$is_registered) {
                    // register the attendee
                    self::register_webinar_attendee(
                        $post->ID,
                        $name,
                        $email,
                        $exact_time,
                        null,
                        null,
                        false,
                        false,
                        false,
                        [
                            'custom_fields' => $confirm_data[3]
                        ]
                    );
                }
            }

            wp_redirect(remove_query_arg($args_to_remove));
            die();
        }

        $webinar = WebinarSysteemWebinar::create_from_id($post->ID);

        if (is_user_logged_in() && (
            !isset($request->attendee_name) ||
            !isset($request->attendee_email)
        )) {
            $user = wp_get_current_user();
            $request->attendee_name = $user->display_name;
            $request->attendee_email = $user->user_email;
        }

        if ($webinar->get_registration_name_mode() == 'required' && empty($request->attendee_name)) {
            return;
        }

        $allow_auto_register = (
            // auto register is set and we are an admin or team member
            (isset($request->auto_register) && (self::is_current_visitor_team_member() || self::is_current_visitor_admin()))
        );

        // is auto-register enabled?
        if (!$allow_auto_register) {
            return;
        }

        $disable_notifications = isset($request->disable_notifications);

        $custom_fields = self::get_custom_fields_from_request($post->ID);

        // A required field was not provided and we are not team members/admins
        if (
            !self::is_current_visitor_team_member() &&
            !self::is_current_visitor_admin() &&
            $custom_fields == null
        ) {
            return;
        }

        self::register_webinar_attendee(
            $post->ID,
            sanitize_text_field($request->attendee_name),
            sanitize_email($request->attendee_email),
            null,
            null,
            null,
            $disable_notifications,
            $disable_notifications,
            $disable_notifications,
            [
                'custom_fields' => $custom_fields
            ]);

        wp_redirect(remove_query_arg($args_to_remove));
        die();
    }

    public static function is_current_visitor_team_member() {
        return current_user_can('manage_options');
    }

    public static function is_current_visitor_admin() {
        return current_user_can('_wswebinar_createwebinars');
    }

    public function show_drip_subscription_pointer() {
        $current_user = wp_get_current_user();

        $js = '$.post(ajaxurl, { action: "wpws_subscribe_to_drip_course",
                                 email: $( "#wpws-drip-pointer-email" ).val(),
                                 nonce: "'. wp_create_nonce( 'drip pointer subscribe') .'",
                                 subscribe: "%d" });';

        $content  = '';
        $content .= _x(
                'Find out how to create a compelling webinar that drives conversions in this ridiculously actionable (and FREE) 5-part email course.',
                'drip pointer',
                '_wswebinar') . '<br /><br />';

        $content .= '<label>';
        $content .= '<b>' . _x('Email Address:', 'drip pointer', '_wswebinar') . '</b>';
        $content .= '<br />';
        $content .= '<input type="text" id="wpws-drip-pointer-email" value="' . esc_attr( $current_user->user_email ) . '" />';
        $content .= '</label>';

        WebinarSysteemUtils::show_admin_pointer('#wpadminbar',
            _x('Want to know the Secrets of Highly Engaging Webinars?', 'drip pointer', '_wswebinar'),
            $content,
            _x('Yes, please!', 'drip pointer', '_wswebinar'),
            sprintf($js, 1),
            _x('No, thanks', 'drip pointer', '_wswebinar'),
            sprintf($js, 0));
    }
}