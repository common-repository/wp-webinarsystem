<?php

class WebinarSysteemConfirmationShortCodes
{
    static function init()
    {
        // Thank you page
        add_shortcode('webinarpress_confirmation_webinar_name',
            ['WebinarSysteemConfirmationShortCodes', 'confirmation_webinar_name']);

        add_shortcode('webinarpress_confirmation_url',
            ['WebinarSysteemConfirmationShortCodes', 'confirmation_webinar_url']);

        add_shortcode('webinarpress_confirmation_button_link',
            ['WebinarSysteemConfirmationShortCodes', 'confirmation_webinar_button_link']);

        add_shortcode('webinarpress_confirmation_text_link',
            ['WebinarSysteemConfirmationShortCodes', 'confirmation_webinar_text_link']);

        add_shortcode('webinarpress_confirmation_host',
            ['WebinarSysteemConfirmationShortCodes', 'confirmation_webinar_host']);

        add_shortcode('webinarpress_confirmation_starts_at',
            ['WebinarSysteemConfirmationShortCodes', 'confirmation_webinar_starts_at']);

        add_shortcode('webinarpress_confirmation_add_to_calendar',
            ['WebinarSysteemConfirmationShortCodes', 'confirmation_add_to_calendar']);
    }

    static function get_details_from_token() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!array_key_exists('token', $_GET)) {
            return null;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $token = $_GET['token'];

        if (empty($token)) {
            return null;
        }

        return WebinarSysteemConfirmationTokenParser::parse($token);
    }

    static function handle_data_field($field)
    {
        $details = self::get_details_from_token();

        // output some help for the user - useful for designers
        if (!$details) {
            return "[webinar_$field]";
        }

        return $details->{$field};
    }

    static function confirmation_webinar_name()
    {
        return self::handle_data_field('webinar_name');
    }

    static function confirmation_webinar_host()
    {
        return self::handle_data_field('host');
    }

    static function confirmation_webinar_url()
    {
        return self::handle_data_field('link');
    }

    static private function write_class_if_provided($className)
    {
        if (empty($className)) {
            return '';
        }

        return "class=\"$className\"";
    }

    static function confirmation_webinar_text_link($attributes)
    {
        WebinarSysteemJS::embed_assets();
        $details = self::get_details_from_token();

        if (!$details) {
            return '[webinar_starts_at]';
        }

        $attributes = shortcode_atts([
            'class' => '',
            'text' => __('Go to webinar', '_wswebinar'),
        ], $attributes, 'webinar_text_link');

        ob_start();
        ?>
        <a <?php echo esc_attr(self::write_class_if_provided(esc_attr($attributes['class']))) ?> href="<?php echo esc_url($details->link) ?>">
            <?php echo esc_html($attributes['text']) ?>
        </a>
        <?php
        return ob_get_clean();
    }

    static function confirmation_webinar_button_link($attributes)
    {
        WebinarSysteemJS::embed_assets();
        $details = self::get_details_from_token();

        if (!$details) {
            return '[webinar_starts_at]';
        }

        $attributes = shortcode_atts([
            'button_text' => __('Go to webinar', '_wswebinar'),
            'button_color' => '#9006f7',
            'text_color' => '#ffffff',
            'class' => '',
            'icon' => '',
            'size' => 'medium'
        ], $attributes, 'webinar_go_to_webinar');

        $params = [
            'link' => $details->link,
            'icon' => $attributes['icon'],
            'buttonText' => $attributes['button_text'],
            'buttonColor' => $attributes['button_color'],
            'textColor' => $attributes['text_color'],
            'customClass' => $attributes['class'],
            'size' => $attributes['size']
        ];

        ob_start();
        ?>
        <span
            class='wpws_confirmation_webinar_button'
            data-params='<?php echo esc_attr(str_replace('\'', '&apos;', wp_json_encode($params))) ?>'
        >
        </span>
        <?php
        return ob_get_clean();
    }

    static function confirmation_webinar_starts_at($attributes)
    {
        WebinarSysteemJS::embed_assets();
        $details = self::get_details_from_token();

        if (!$details) {
            return '[webinar_starts_at]';
        }

        $attributes = shortcode_atts([
            'locale' => 'en',
            'format' => 'MMM DD YYYY'
        ], $attributes, 'webinar_starts_at');

        $params = [
            'time' => $details->starts_at,
            'locale' => $attributes['locale'],
            'format' => $attributes['format'],
            'timezoneOffset' =>  $details->timezone_offset,
        ];

        ob_start();
        ?>
        <span
            class='wpws_confirmation_webinar_starts_at'
            data-params='<?php echo esc_attr(str_replace('\'', '&apos;', wp_json_encode($params))) ?>'
        >
        </span>
        <?php
        return ob_get_clean();
    }

    static function confirmation_add_to_calendar($attributes)
    {
        WebinarSysteemJS::embed_assets();
        $details = self::get_details_from_token();

        if (!$details) {
            return '[webinar_add_to_calendar]';
        }

        $attributes = shortcode_atts([
            'locale' => 'en',
            'button_text' => __('Add to Calendar', '_wswebinar'),
            'button_color' => '#9006f7',
            'text_color' => '#ffffff',
            'class' => '',
            'size' => 'medium',
            'icon' => 'calendar'
        ], $attributes, 'webinar_add_to_calendar');

        $params = [
            'time' => $details->starts_at,
            'name' =>$details->webinar_name,
            'link' =>$details->link_with_auth,
            'timezoneOffset' =>  $details->timezone_offset,
            'duration' => $details->duration,
            'buttonText' => $attributes['button_text'],
            'buttonColor' => $attributes['button_color'],
            'textColor' => $attributes['text_color'],
            'customClass' => $attributes['class'],
            'size' => $attributes['size'],
            'icon' => $attributes['icon']
        ];

        ob_start();
        ?>
        <span
            class='wpws_confirmation_add_to_calendar'
            data-params='<?php echo esc_attr(str_replace('\'', '&apos;', wp_json_encode($params))) ?>'
        >
        </span>
        <?php
        return ob_get_clean();
    }
}
