<?php

class WebinarSysteemLog {

    private static $instance;
    private $log_path;
    private $logging_enabled;
    private $log_key;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        // is logging enabled
        $this->logging_enabled = get_option("_wswebinar_enable_logging") == 'on';

        if (!$this->logging_enabled) {
            return;
        }

        // store the log path
        $this->log_key = get_option("_wswebinar_log_key");

        if (empty($this->log_key) || !$this->log_key) {
            $this->log_key = WebinarSysteemHelperFunctions::generate_uuid();
            update_option("_wswebinar_log_key", $this->log_key);
        }

        $log_directory = plugin_dir_path(dirname(__FILE__)).'logs/';

        if (!@is_dir($log_directory)) {
            @wp_mkdir_p($log_directory);
        }

        $this->log_path = $log_directory.$this->log_key.'.log';
    }

    public static function get_key() {
        return self::getInstance()->log_key;
    }

    public static function get_url() {
        $filename = 'logs/'.self::get_key().'.log';
        return plugins_url($filename, dirname(__FILE__));
    }

    public static function exists() {
        return file_exists(self::get_path());
    }

    public static function get_path() {
        return self::getInstance()->log_path;
    }

    public static function log($msg) {
        // make sure logging is enabled and not in demo mode
        if (!self::getInstance()->logging_enabled || WebinarSysteemSettings::instance()->is_demo()) {
            return;
        }

        $log_path = self::getInstance()->log_path;
        $time = current_time('Y-m-d H:i:s');
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($log_path, '['.$time.'] '.$msg.PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function dump_object($object) {
        try {
            ob_start();
            var_dump($object);
            return ob_get_clean();
        } catch (Exception $e) {
            return 'var_dump failed: '.$e->getMessage();
        }
    }
}
