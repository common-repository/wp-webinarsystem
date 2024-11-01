<?php

class WebinarSysteemSettings {

    const GLOBAL_SCRIPT_REGISTRATION_PAGE = 'registration-page';
    const GLOBAL_SCRIPT_CONFIRMATION_PAGE = 'confirmation-page';
    const GLOBAL_SCRIPT_COUNTDOWN_PAGE = 'countdown-page';
    const GLOBAL_SCRIPT_WEBINAR_PAGE = 'webinar-page';
    const GLOBAL_SCRIPT_REPLAY_PAGE = 'replay-page';
    const GLOBAL_SCRIPT_PAGES = [
        self::GLOBAL_SCRIPT_REGISTRATION_PAGE,
        self::GLOBAL_SCRIPT_CONFIRMATION_PAGE,
        self::GLOBAL_SCRIPT_COUNTDOWN_PAGE,
        self::GLOBAL_SCRIPT_WEBINAR_PAGE,
        self::GLOBAL_SCRIPT_REPLAY_PAGE,
    ];

    private static $_instance;
    private static $slug = '_wswebinar_';

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    private $settings;

    public function __construct() {
        $this->load_settings();
    }

    public function load_settings() {
        $data = get_option('_wswebinar_settings');

        $settings = false;

        // First try unserialize (current method)
        if ($data) {
            $settings = unserialize($data);

            // If it is not an array then reset
            if ($settings && !is_array($settings)) {
                $settings = [];
            }
        }

        // Settings not set so create default object
        if (!$settings) {
            $settings = [];
        };

        $this->settings = $settings;
    }

    public function save_settings() {
        $data = serialize($this->settings);
        update_option('_wswebinar_settings', $data);
    }

    public function get_option_with_default($name, $default = false) {
        $value = get_option($name);

        if ($value == false) {
            return $default;
        }

        return $value;
    }

    public function set_registration_widgets_with_triggers($widget_ids) {
        $this->settings['registration_widgets_with_triggers'] = $widget_ids;
        $this->save_settings();
    }

    public function get_registration_widgets_with_triggers() {
        if (!array_key_exists('registration_widgets_with_triggers', $this->settings) ||
            !is_array($this->settings['registration_widgets_with_triggers'])) {
            return [];
        }

        return $this->settings['registration_widgets_with_triggers'];
    }

    public function get_installation_date() {
        if (!array_key_exists('installation_date', $this->settings)) {
            $this->settings['installation_date'] = time();
            $this->save_settings();
        }

        return $this->settings['installation_date'];
    }

    public function days_since_install() {
        $install_date = $this->get_installation_date();
        return (time() - $install_date) / 86400;
    }

    // reduce server load
    public function get_reduce_server_load() {
        return get_option('_wswebinar_reduce_server_load') == 'on';
    }

    public function set_reduce_server_load($value) {
        update_option('_wswebinar_reduce_server_load', $value ? 'on' : '');
    }

    // enable beta updates
    public function get_enable_beta_updates() {
        return get_option('_wswebinar_enable_beta') == 'on';
    }

    public function set_enable_beta_updates($value) {
        update_option('_wswebinar_enable_beta', $value ? 'on' : '');
    }

    // enable optimized zoom
    public function get_enable_optimized_zoom() {
        return get_option('_wswebinar_enable_optimized_zoom') == 'on';
    }

    public function set_enable_optimized_zoom($value) {
        update_option('_wswebinar_enable_optimized_zoom', $value ? 'on' : '');
    }

    // enable logging
    public function get_enable_logging() {
        return get_option('_wswebinar_enable_logging') == 'on';
    }

    public function set_enable_logging($value) {
        update_option('_wswebinar_enable_logging', $value ? 'on' : '');
    }

    // new reg webhook
    public function get_new_registration_webhook() {
        return $this->get_option_with_default('_wswebinar_new_registration_webhook', '');
    }

    public function set_new_registration_webhook($value) {
        update_option('_wswebinar_new_registration_webhook', $value);
    }

    // attended webhook
    public function get_attended_webinar_webhook() {
        return $this->get_option_with_default('_wswebinar_attended_webinar_webhook', '');
    }

    public function set_attended_webinar_webhook($value) {
        update_option('_wswebinar_attended_webinar_webhook', $value);
    }

    //ZoomSDK Client ID
    public function get_client_id_zoomsdk() {
        return $this->get_option_with_default('_wswebinar_client_id_zoomsdk', '');
    }

    public function set_client_id_zoomsdk($value) {
        update_option('_wswebinar_client_id_zoomsdk', $value);
    }

    //ZoomSDK Client Secret
    public function get_client_secret_zoomsdk() {
        return $this->get_option_with_default('_wswebinar_client_secret_zoomsdk', '');
    }

    public function set_client_secret_zoomsdk($value) {
        update_option('_wswebinar_client_secret_zoomsdk', $value);
    }

    // Depreciated, shouldn't be needed with future page layouts..
    public function get_use_theme_styles() {
        return in_array(get_option('_wswebinar_enable_theme_styles'), array('on', null, ''));
    }

    public function set_use_theme_styles($value) {
        update_option('_wswebinar_enable_theme_styles', $value ? 'on' : 'off');
    }

    // Fav icon
    public function get_favicon_url() {
        return $this::get_option_with_default('_wswebinar_favicon_url', '');
    }

    public function set_favicon_url($value) {
        update_option('_wswebinar_favicon_url', $value);
    }

    // use woocommerce
    public function is_woocommerce_enabled() {
        return get_option('_wswebinar_enable_woocommerce_integration') == 'on';
    }

    public function set_woocommerce_is_enabled($value) {
        update_option('_wswebinar_enable_woocommerce_integration', $value ? 'on' : '');
    }

    // woocommerce redirect page
    public function get_woocommerce_add_to_cart_redirect_page() {
        return $this->get_option_with_default('_wswebinar_woocommerce_add_to_cart_redirect_page', 'checkout');
    }

    public function set_woocommerce_add_to_cart_redirect_page($value) {
        update_option('_wswebinar_woocommerce_add_to_cart_redirect_page', $value);
    }

    // woocommerce register action
    public function get_woocommerce_register_action() {
        return $this->get_option_with_default('_wswebinar_woocommerce_register_action', 'add-to-cart');
    }

    public function set_woocommerce_register_action($value) {
        update_option('_wswebinar_woocommerce_register_action', $value);
    }

    // webinar slug
    public function get_webinar_slug() {
        return get_option('_wswebinar_custom_webinar_slug');
    }

    public function set_webinar_slug($value) {
    }

    // drip api key
    public function get_drip_api_key() {
        return $this->get_option_with_default('_wswebinar_dripapikey', '');
    }

    public function set_drip_api_key($value) {
        update_option('_wswebinar_dripapikey', $value);
    }

    // MailChimp api key
    public function get_mailchimp_api_key() {
        return $this->get_option_with_default('_wswebinar_mailchimpapikey', '');
    }

    public function set_mailchimp_api_key($value) {
        update_option('_wswebinar_mailchimpapikey', $value);
    }

    // Mailrelay host
    public function get_mailrelay_host() {
        return $this->get_option_with_default('_wswebinar_mailrelay_host', '');
    }

    public function set_mailrelay_host($value) {
        update_option('_wswebinar_mailrelay_host', $value);
    }

    // Mailrelay api key
    public function get_mailrelay_key() {
        return $this->get_option_with_default('_wswebinar_mailrelay_key', '');
    }

    public function set_mailrelay_key($value) {
        update_option('_wswebinar_mailrelay_key', $value);
    }

    // MailerLite api key
    public function get_mailerlite_key() {
        return $this->get_option_with_default('_wswebinar_mailerlite_key', '');
    }

    public function set_mailerlite_key($value) {
        update_option('_wswebinar_mailerlite_key', $value);
    }

    // Mail Octopus api key
    public function get_mailoctopus_key() {
        return $this->get_option_with_default('_wswebinar_mailoctopus_key', '');
    }

    public function set_mailoctopus_key($value) {
        update_option('_wswebinar_mailoctopus_key', $value);
    }

    // Enormail API Key
    public function get_enormail_api_key() {
        return $this->get_option_with_default('_wswebinar_enormailapikey', '');
    }

    public function set_enormail_api_key($value) {
        update_option('_wswebinar_enormailapikey', $value);
    }

    // Getresponse API Key
    public function get_getresponse_api_key() {
        return $this->get_option_with_default('_wswebinar_getresponseapikey', '');
    }

    public function set_getresponse_api_key($value) {
        update_option('_wswebinar_getresponseapikey', $value);
    }

    // ActiveCampaign API Key
    public function get_activecampaign_api_key() {
        return $this->get_option_with_default('_wswebinar_activecampaignapikey', '');
    }

    public function set_activecampaign_api_key($value) {
        update_option('_wswebinar_activecampaignapikey', $value);
    }

    // ActiveCampaign API URL
    public function get_activecampaign_api_url() {
        return $this->get_option_with_default('_wswebinar_activecampaignurl', '');
    }

    public function set_activecampaign_api_url($value) {
        update_option('_wswebinar_activecampaignurl', $value);
    }

    // ConvertKit API Key
    public function get_convertkit_api_key() {
        return $this->get_option_with_default('_wswebinar_convertkit_key', '');
    }

    public function set_convertkit_api_key($value) {
        update_option('_wswebinar_convertkit_key', $value);
    }

    protected function get_roles_for_permissions($permission, $roles) {
        $permissions = [];
        foreach ($roles as $slug => $role) {
            $permissions[$slug] = $slug == 'administrator'
                ? true
                : get_option('_wswebinar_'.$permission.'_'.$slug) == 'on';
        }
        return $permissions;
    }

    public function get_permissions() {
        $roles = $this->get_roles();

        return [
            'createwebinars' => $this->get_roles_for_permissions('createwebinars', $roles),
            'managesubscribers' => $this->get_roles_for_permissions('managesubscribers', $roles),
            // 'accesscontrolbar' => $this->get_roles_for_permissions('accesscontrolbar', $roles),
            'managequestions' => $this->get_roles_for_permissions('managequestions', $roles),
            'managechatlogs' => $this->get_roles_for_permissions('managechatlogs', $roles),
            'webinarsettings' => $this->get_roles_for_permissions('webinarsettings', $roles),
        ];
    }

    public function get_roles() {
        global $wp_roles;
        return $wp_roles->get_names();
    }

    public function update_permissions($permissions) {
        foreach ($permissions as $permission => $role_permissions) {
            foreach ($role_permissions as $role => $enabled) {
                $value = $enabled
                    ? 'on'
                    : 'off';

                update_option('_wswebinar_'.$permission.'_'.$role, $value);
            }
        }

        // setup role permissions
        WebinarSysteemPermissions::set_role_permissions();
    }

    public function get_color_option($option, $default = null) {
        return WebinarSysteemHelperFunctions::add_hash_to_color(
            $this->get_option_with_default($option, $default)
        );
    }

    // email from name
    public function get_email_from_name() {
        return $this->get_option_with_default('_wswebinar_email_sentFrom', get_bloginfo('name'));
    }

    public function set_email_from_name($value) {
        update_option('_wswebinar_email_sentFrom', $value);
    }

    // email from address
    public function get_email_from_address() {
        return $this->get_option_with_default('_wswebinar_email_senderAddress', get_bloginfo('admin_email'));
    }

    public function set_email_from_address($value) {
        update_option('_wswebinar_email_senderAddress', $value);
    }

    // header image
    public function get_email_header_image() {
        $image_url = $this->get_option_with_default('_wswebinar_email_headerImg', '');

        // remove default WPWS logo from old versions
        if (strpos($image_url, 'includes/images/webinarpress-logo.png') !== false) {
            return '';
        }

        return $image_url;
    }

    public function set_email_header_image($value) {
        return update_option('_wswebinar_email_headerImg', $value);
    }

    public function get_email_image_height(){
        return $this->get_option_with_default('_wswebinar_email_headerImgHeight','');
    }

    public function set_email_image_height($value){
        return update_option('_wswebinar_email_headerImgHeight',$value);
    }

    // footer text
    public function get_email_footer_text() {
        return $this->get_option_with_default('_wswebinar_email_footerTxt', '');
    }

    public function set_email_footer_text($value) {
        update_option('_wswebinar_email_footerTxt', $value);
    }

    // base color
    public function get_email_base_color() {
        return $this->get_color_option('_wswebinar_email_baseCLR');
    }

    public function set_email_base_color($value) {
        update_option('_wswebinar_email_baseCLR', $value);
    }

    // background color
    public function get_email_background_color() {
        return $this->get_color_option('_wswebinar_email_bckCLR', '#f2f2f2');
    }

    public function set_email_background_color($value) {
        update_option('_wswebinar_email_bckCLR', $value);
    }

    // body background color
    public function get_email_body_background_color() {
        return $this->get_color_option('_wswebinar_email_bodyBck', '#ffffff');
    }

    public function set_email_body_background_color($value) {
        update_option('_wswebinar_email_bodyBck', $value);
    }

    // body text color
    public function get_email_body_text_color() {
        return $this->get_color_option('_wswebinar_email_body_text', '#555555');
    }

    public function set_email_body_text_color($value) {
        update_option('_wswebinar_email_body_text', $value);
    }

    // button background color
    public function get_email_button_background_color() {
        return $this->get_color_option('_wswebinar_email_button_background_color', '#3498db');
    }

    public function set_email_button_background_color($value) {
        update_option('_wswebinar_email_button_background_color', $value);
    }

    // button text color
    public function get_email_button_text_color() {
        return $this->get_color_option('_wswebinar_email_button_text_color', '#ffffff');
    }

    public function set_email_button_text_color($value) {
        update_option('_wswebinar_email_button_text_color', $value);
    }

    // include unsubscribe links
    public function get_include_unsubscribe_links() {
        return get_option('_wswebinar_subscription') == 'on';
    }

    public function set_include_unsubscribe_links($value) {
        update_option('_wswebinar_subscription', $value ? 'on' : 'off');
    }

    // include unsubscribe links
    public function get_resubscribe_attendees_on_register() {
        return get_option('_wswebinar_resubscribe_attendees_on_register', true) == '1';
    }

    public function set_resubscribe_attendees_on_register($value) {
        update_option('_wswebinar_resubscribe_attendees_on_register', $value);
    }

    // Admin email address
    public function get_admin_email_address() {
        $value = get_option('_wswebinar_AdminEmailAddress');

        return !empty($value)
            ? $value
            : get_bloginfo('admin_email');
    }

    public function set_admin_email_address($value) {
        update_option('_wswebinar_AdminEmailAddress', $value);
    }

    // get email settings
    public function get_email_template_options($type) {
        // why standardize when we can hard code! :(
        $content_key = $type == 'wbnstarted' || $type == 'wbnreplay'
            ? '_wswebinar_'.$type
            : '_wswebinar_'.$type.'content';

        $defaults = $this->get_default_email_templates()[$type];

        // get the content
        $content = get_option($content_key);
        if (empty($content)) {
            $content = $defaults->content;
        }

        // get the subject
        $subject = get_option('_wswebinar_'.$type.'subject');
        if (empty($subject)) {
            $subject = $defaults->subject;
        }

        return (object) [
            'enabled' => get_option('_wswebinar_'.$type.'enable') != 'off',
            'subject' => $subject,
            'content' => apply_filters('meta_content', $content)
        ];
    }

    public function set_email_template_options($type, $options) {
        // why standardize when we can hard code! :(
        $content_key = $type == 'wbnstarted' || $type == 'wbnreplay'
            ? '_wswebinar_'.$type
            : '_wswebinar_'.$type.'content';

        update_option('_wswebinar_'.$type.'enable', $options->enabled ? 'on' : 'off');
        update_option('_wswebinar_'.$type.'subject', $options->subject);
        update_option($content_key, $options->content);
    }

    public static function get_global_script($page, $script) {
        $script = get_option('_wswebinar_global_scripts_' . $page . '_' . $script);
        return !is_null($script) ? $script : '';
    }

    public static function get_default_email_templates() {
        $blog_name = get_bloginfo('name');

        // New registration
        $new_registration = "Howdy

[attendee-name] just signed up for your webinar <i>[webinar-title]</i>.

Regards
{$blog_name}";

        // Registration Confirmation
        $registration_confirmation = "Hi [attendee-name],
         
 Thank you for your registration for the webinar. Below you will find the details of the webinar.\r\n

<b>Webinar name:</b> [webinar-title]
<b>Date:</b> [webinar-date]
<b>Time:</b> [webinar-time]
<b>Timezone:</b> [webinar-timezone]

[webinar-link-button text=\"Join the webinar\"]

Regards
{$blog_name}";

        // One hour before
        $one_hour_before ="Hi [attendee-name]

The webinar you signed up for starts in one hour. Below you will find the link to attend the webinar.

<b>Webinar name:</b> [webinar-title]
<b>Date:</b> [webinar-date]
<b>Time:</b> [webinar-time]
<b>Timezone:</b> [webinar-timezone]

[webinar-link-button text=\"Join the webinar\"]

Regards
{$blog_name}";

        // One day before
        $one_day_before = "Hi [attendee-name]

This is a reminder for your upcoming webinar tomorrow. Below you will find the details of the webinar.

<b>Webinar name:</b> [webinar-title]
<b>Date:</b> [webinar-date]
<b>Time:</b> [webinar-time]
<b>Timezone:</b> [webinar-timezone]

[webinar-link-button text=\"Join the webinar\"]

Regards
{$blog_name}";

        // Started/Starting
        $started = "Hey [attendee-name],

We are starting the webinar, click on the link below to join us!

[webinar-link-button text=\"Join the webinar\"]

Regards
{$blog_name}";

        // Replay
        $replay = "Hi [attendee-name]

You can now watch the webinar again

[webinar-link-button text=\"Join the webinar\"]

See you later!

Regards
{$blog_name}";

        // One day before
        $on_order_complete = "Hi [attendee-name]

Thank you for purchasing a webinar ticket. Please click on the button below to login for the webinar when it starts

<b>Webinar name:</b> [webinar-title]
<b>Date:</b> [webinar-date]
<b>Time:</b> [webinar-time]
<b>Timezone:</b> [webinar-timezone]

[webinar-link-button text=\"Join the webinar\"]

Regards
{$blog_name}";


        // Double optin
        $double_optin = "Hi [attendee-name],

Thank you for registering for [webinar-title]. Please click on the link below to confirm your registration.\r\n

[confirm-registration-button text=\"Confirm my registration\"]

Regards
{$blog_name}";

        return [
            'newreg' => (object) [
                'subject' => 'New Registration',
                'content' => $new_registration
            ],
            'regconfirm' => (object) [
                'subject' => 'You are registered for the webinar!',
                'content' => $registration_confirmation,
            ],
            '1hrb4' => (object) [
                'subject' => 'We are live in one hour!',
                'content' => $one_hour_before,
            ],
            '24hrb4' => (object) [
                'subject' => 'We\'ll be getting started in 24 hours',
                'content' => $one_day_before,
            ],
            'wbnstarted' => (object) [
                'subject' => 'We are starting the webinar!',
                'content' => $started,
            ],
            'wbnreplay' => (object) [
                'subject' => 'Don\'t miss the webinar replay',
                'content' => $replay,
            ],
            'order_complete' => (object) [
                'subject' => 'Your webinar link',
                'content' => $on_order_complete,
            ],
            'double_optin' => (object) [
                'subject' => 'Confirm your registration',
                'content' => $double_optin,
            ]
        ];
    }

    // Admin email address
    public function has_run_once() {
        $value = get_option('_wswebinar_has_run_once');

        return !empty($value);
    }

    public function set_has_run() {
        update_option('_wswebinar_has_run_once', 1);
    }

    public function needs_flush_rewrite_rules() {
        $value = get_option('_wswebinar_needs_flush_rewrite_rules');

        return !empty($value);
    }

    public function set_needs_flush_rewrite_rules($value = true) {
        if ($value == true) {
            update_option('_wswebinar_needs_flush_rewrite_rules', 1);
        } else {
            delete_option('_wswebinar_needs_flush_rewrite_rules');
        }
    }

    public function get_translations() {
        return self::get_option_with_default('_wswebinar_translations', (object) []);
    }

    public function set_translations($value) {
        return update_option('_wswebinar_translations', $value);
    }

    public function get_global_scripts() {
        $scripts = [];

        foreach (self::GLOBAL_SCRIPT_PAGES as $scriptPage) {
            $scripts[$scriptPage] = [
                'headerScriptTag' => self::get_option_with_default(
                    '_wswebinar_global_scripts_' . $scriptPage . '_headerScriptTag',
                    ''
                ),
                'bodyScriptTag' => self::get_option_with_default(
                    '_wswebinar_global_scripts_' . $scriptPage . '_bodyScriptTag',
                    ''
                ),
            ];
        }

        return $scripts;
    }

    public function set_global_scripts($value) {
        foreach (self::GLOBAL_SCRIPT_PAGES as $scriptPage) {
            if(
                !isset($value->{$scriptPage}->headerScriptTag) ||
                !isset($value->{$scriptPage}->bodyScriptTag)
            ) {
                continue;
            }

            update_option(
                '_wswebinar_global_scripts_' . $scriptPage . '_headerScriptTag',
                $value->{$scriptPage}->headerScriptTag
            );

            update_option(
                '_wswebinar_global_scripts_' . $scriptPage . '_bodyScriptTag',
                $value->{$scriptPage}->bodyScriptTag
            );
        }
        return true;
    }

    public function has_shown_course_invite() {
        return get_option('_wswebinar-has-shown-drip-pointer', 0) == 1;
    }

    public function set_has_shown_course_invite($show = true) {
        update_option('_wswebinar-has-shown-drip-pointer', $show ? 1 : 0);
    }

    public function should_show_course_invite() {
        return get_option('_wswebinar-show-drip-pointer', 0) == 1;
    }

    public function set_show_course_invite($show = true) {
        update_option('_wswebinar-show-drip-pointer', $show ? 1 : 0);
    }

    public function is_demo() {
        return apply_filters('wpws_is_demo', false);
    }

    public function get_key($name) {
        $key = get_option('_wswebinar_'.$name);

        if (empty($key) || !$key) {
            $key = WebinarSysteemHelperFunctions::generate_uuid();
            update_option('_wswebinar_'.$name, $key);
        }
        return $key;
    }

    public function get_unsubscribe_page_id() {
        return get_option('_wswebinar_unsubscribe');
    }

    public function get_overview_page_id() {
        return get_option('_wswebinar_overview');
    }
}
