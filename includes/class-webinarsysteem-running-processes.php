<?php

class WebinarSysteemRunningProcesses {
    protected static function get_key_from_name($name) {
        return 'wpws_is_running_'.$name;
    }

    public static function is_running($name) {
        $value = get_transient(self::get_key_from_name($name));
        WebinarSysteemLog::log("WebinarSysteemRunningProcesses::is_running:".$name."=".wp_json_encode($value));
        return $value === "wpws_running";
    }

    public static function refresh($name, $expires_in = 60) {
        set_transient(self::get_key_from_name($name), "wpws_running", $expires_in);
    }
}
