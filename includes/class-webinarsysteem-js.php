<?php

class WebinarSysteemJS extends WebinarSysteem {

    private static $has_embedded_assets = false;

    public static function get_domain() {
        return 'wpwebinarsystem';
    }

    public static function get_nonce_secret() {
        return 'wpws-js';
    }

    public static function get_version() {
        return WPWS_PLUGIN_VERSION;
    }

    public static function get_plugin_path() {
        $path = dirname(__FILE__);
        return plugin_dir_url($path);
    }

    public static function get_base_path($url = true) {
        $path = dirname(__FILE__);
        return ($url? plugin_dir_url($path) : plugin_dir_path($path) ).'wpws-js/';
    }

    public static function get_asset_manifest_data() {
        $manifest = WebinarSysteemJS::get_base_path(false) . 'build/asset-manifest.json';
        $devBundle = WebinarSysteemJS::get_base_path(false) . 'build/static/js/bundle.js';

        // dev bundle
        if (file_exists($devBundle)){
            return [
                'main.js' => 'static/js/bundle.js',
                'main.css' => false
            ];
        }

        // production build
        if (file_exists($manifest)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            return json_decode(file_get_contents($manifest), true);
        }

        return false;
    }

    public static function get_translation_language_code() {
        $domain_translations = get_translations_for_domain(WebinarSysteem::$lang_slug);
        $language = $domain_translations->get_header('Language');

        if (!$language) {
            return '';
        }

        return $language;
    }

    public static function get_js_path() {
        return WebinarSysteemJS::get_base_path() . 'build/main.js';
    }

    public static function get_polyfill_path() {
        return WebinarSysteemJS::get_plugin_path() . 'includes/js/polyfill.min.js';
    }

    public static function get_css_path() {
        return WebinarSysteemJS::get_base_path() . 'build/main.css';
    }

    public static function get_asset_path() {
        return WebinarSysteemJS::get_base_path() . 'build/';
    }

    public static function embed_assets() {
        if (self::$has_embedded_assets) {
            return;
        }

        if ($script = WebinarSysteemJS::get_js_path()) {
            wp_enqueue_script('wp-polyfill');

            // add the bundle script
            wp_register_script(
                WebinarSysteemJS::get_domain(),
                $script,
                [],
                WebinarSysteemJS::get_version(),
                'all');
        }

        // todo, what does this do?
        wp_enqueue_script(WebinarSysteemJS::get_domain());

        if ($style = WebinarSysteemJS::get_css_path()) {
            wp_enqueue_style(
                WebinarSysteemJS::get_domain() . '-styles',
                $style,
                array(),
                WebinarSysteemJS::get_version(),
                'all');
        }

        // register local variables
        $translations = get_translations_for_domain(WebinarSysteem::$lang_slug);

        wp_localize_script(WebinarSysteemJS::get_domain(), '___wpws', array(
            'locale' => get_locale(),
            //'language' => $translations->get_header('Language'),
            'language' => get_locale(),
            'ajax' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce(self::get_nonce_secret()),
            'base' => self::get_asset_path(),
            'plugin' => self::get_plugin_path(),
            'version' => WPWS_PLUGIN_VERSION,
            'adminUrl' => admin_url(),
            'isAdmin' => is_admin()
        ));

        self::$has_embedded_assets = true;
    }

    public static function embed_registration_widgets() {
        $registration_widgets_with_triggers = WebinarSysteemRegistrationWidget::get_registration_widgets_with_triggers();

        if (!count($registration_widgets_with_triggers)) {
            return;
        }

        self::embed_assets();

        wp_localize_script(
            WebinarSysteemJS::get_domain(),
            '___wpwsRegistrationWidgetsWithTriggers',
            ['widgets' => $registration_widgets_with_triggers]
        );
    }

    public static function check_ajax_nonce() {
        check_ajax_referer(self::get_nonce_secret(), 'security');
    }
}
