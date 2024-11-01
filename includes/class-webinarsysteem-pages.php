<?php

class WebinarSysteemPages {
    protected static function write_page($id, $params = []) {
        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <div
            id="<?php echo esc_attr($id) ?>"
            data-url="<?php echo esc_url($ajax_url) ?>"
            data-params='<?php echo esc_attr(str_replace('\'', '&apos;', wp_json_encode($params))) ?>'
        ></div>
        <?php
    }

    public static function registration_widgets() {
        wp_enqueue_editor();
        self::write_page("wpws-registration-widgets");
    }

    public static function webinar_list() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['webinar_id'])) {
            wp_enqueue_editor();
            // wp_enqueue_media();
            self::write_page("wpws-webinar-editor", [
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                'webinar_id' => $_GET['webinar_id'],
                'enabled_mailinglist_providers' => WebinarsysteemMailingListIntegrations::get_enabled_providers(),
                'woo_commerce_is_enabled' => WebinarSysteemWooCommerceIntegration::is_ready(),
                'is_cron_active' => WebinarSysteemCron::was_active_within(),
                'translations' => WebinarSysteemSettings::instance()->get_translations(),
                'max_hosted_attendee_count' => 0,
                'isZoomSetup' => WebinarSysteemHelperFunctions::isZoomSetup()
            ]);
            return;
        }

        self::write_page("wpws-webinar-list");
    }

    public static function new_webinar() {
        wp_enqueue_editor();

        self::write_page("wpws-webinar-editor", [
            'webinar_id' => null,
            'enabled_mailinglist_providers' => WebinarsysteemMailingListIntegrations::get_enabled_providers(),
            'woo_commerce_is_enabled' => WebinarSysteemWooCommerceIntegration::is_ready(),
            'is_cron_active' => WebinarSysteemCron::was_active_within(),
            'translations' => WebinarSysteemSettings::instance()->get_translations(),
            'max_hosted_attendee_count' => 0,
            'isZoomSetup' => WebinarSysteemHelperFunctions::isZoomSetup()
        ]);
    }

    public static function attendees() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $webinar_id = isset($_GET['id']) ? $_GET['id'] : null;
        self::write_page("wpws-attendees", [
            'webinar_id' => (int) $webinar_id,
        ]);
    }

    public static function chats() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $webinar_id = isset($_GET['id']) ? $_GET['id'] : null;
        self::write_page("wpws-chats", [
            'webinar_id' => (int) $webinar_id,
        ]);
    }

    public static function questions() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $webinar_id = isset($_GET['id']) ? $_GET['id'] : null;
        self::write_page("wpws-questions", [
            'webinar_id' => (int) $webinar_id,
        ]);
    }

    public static function settings() {
        wp_enqueue_editor();
        self::write_page("wpws-settings", []);
    }

    public static function redirect_to_pro_upgrade() {
        self::write_page("wpws-upgrade", []);
    }

    public static function webinar_recordings() {
        self::write_page("wpws-webinar-recordings", [
            'license_key' => ''
        ]);
    }

    public static function polls() {
        self::write_page("wpws-poll-list", [
            'translations' => WebinarSysteemSettings::instance()->get_translations(),
        ]);
    }
}
