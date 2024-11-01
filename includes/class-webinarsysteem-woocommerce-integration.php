<?php

/**
 * Description of WebinarSysteemWooCommerceIntegration
 * Integrate WooCommerce for paid webinars. Tickets aka Products will be sold.
 *
 * @package  WebinarSysteem/WooCommerceIntegration
 * @author Thambaru Wijesekara <howdy@thambaru.com>
 */

define('ASSFURL', WP_PLUGIN_URL . "/" . dirname(plugin_basename(__FILE__)));

class WebinarSysteemWooCommerceIntegration
{
    /**
     * Checks if WooCommerce plugin exists
     *
     * @return boolean
     */
    public static function is_woo_commerce_ready()
    {
        return class_exists('WooCommerce');
    }

    /**
     * Checks if user enabled the integration
     *
     * @return boolean
     */
    public static function is_enabled()
    {
        return false;
    }

    /**
     * Checks whether WooCommerce plugin exists and the user enabled the integration
     *
     * @return boolean
     */
    public static function is_ready()
    {
        return self::is_woo_commerce_ready() && self::is_enabled();
    }
}

?>
