<?php

class WebinarSysteemRequirements {
    public static function get_database_server_version() {
        global $wpdb;

        if (empty($wpdb->is_mysql)) {
            return array(
                'string' => '',
                'number' => '',
            );
        }

        if ($wpdb->use_mysqli) {
            // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_get_server_info
            $server_info = mysqli_get_server_info($wpdb->dbh);
        } else {
            // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysql_get_server_info
            $server_info = mysql_get_server_info($wpdb->dbh);
        }

        return array(
            'string' => $server_info,
            'number' => preg_replace('/([^\d.]+).*/', '', $server_info),
        );
    }

    public static function is_database_version_out_of_date() {
        $version = WebinarSysteemRequirements::get_database_server_version();
        return (
            version_compare($version['number'], '5.5', '<') &&
            !strstr($version['string'], 'MariaDB')
        );
    }

    public static function is_php_version_out_of_date() {
        $php_version = '';
		
        if (function_exists('phpversion')) {
            $php_version = phpversion();
            $php_version = esc_html($php_version);
        }

        return $php_version < 5.6;
    }
}
