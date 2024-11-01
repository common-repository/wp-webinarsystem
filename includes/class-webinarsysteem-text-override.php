<?php

class WebinarSysteemTextOverride
{
    public static function get_text($key) {
        $text = [
            'woocommerce-webinar-tickets-title' => __('My Webinar Tickets', '_wswebinar'),
            'woocommerce-webinar-tickets-webinar' => __('Webinar', '_wswebinar'),
            'woocommerce-webinar-tickets-date' => __('Session', '_wswebinar'),
            'woocommerce-webinar-tickets-join' => __('Join', '_wswebinar'),
            'woocommerce-webinar-tickets-join-webinar' => __('Join webinar', '_wswebinar'),
            'woocommerce-webinar-tickets-time' => __('Time', '_wswebinar'),
            'woocommerce-webinar-tickets-order' => __('Order', '_wswebinar')
        ];

        return apply_filters('wpws_text_override', $text[$key], $key);
    }

    public static function e($key) {
        echo esc_html(self::get_text($key));
    }
}
