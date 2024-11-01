<?php

class WebinarSysteemPostTypeBase
{
    public $id;
    public $config;

    public function __construct($post) {
        $this->$config = null;
    }

    public function get_field($field, $default = null) {
        // TODO, re-load all the fields??
        $ret = get_post_meta($this->id, '_wswebinar_'.$field, true);

        if ($ret == '' && $default !== null) {
            return $default;
        }

        return $ret;
    }

    public function get_color($field, $default = null) {
        return WebinarSysteemHelperFunctions::add_hash_to_color(
            $this->get_field($field, $default)
        );
    }

    public function set_field($field, $value) {
        return update_post_meta($this->id, '_wswebinar_'.$field, $value);
    }

    public function delete_field($field) {
        return delete_post_meta($this->id, '_wswebinar_'.$field);
    }

    public function get_secure_field($field) {
        $key = $this->get_field($field);

        if (!$key) {
            $key = WebinarSysteemHelperFunctions::generate_uuid();
            $this->set_field($field, $key);
        }

        return $key;
    }

    public function get_json($field, $default = null) {
        $json = $this->get_field($field);

        if (empty($json)) {
            return $default;
        }

        $result = json_decode($json, true);

        if ($result == null || empty($result)) {
            return $default;
        }

        return $result;
    }

    public function set_json($field, $value) {
        $json = wp_json_encode($value);
        return $this->set_field($field, $json);
    }

    public function get_object($field) {
        $params = $this->get_field($field, null);

        return $params == ''
            ? (object) []
            : $params;
    }

    public function load() {
        $data = $this->get_field('config', false);

        $config = false;

        // First try unserialize (current method)
        if ($data) {
            $config = unserialize($data);
        }

        // Settings not set so create default object
        if (!$config) {
            $config = [];
        };

        $this->config = $config;
    }

    public function save() {
        $this->set_field('config', serialize($this->config));
    }

    public function get_config($key, $def = null) {
        if ($this->config == null) {
            return $def;
        }

        return isset($this->config, $key)
            ? $this->config[$key]
            : $def;
    }

    public function set_config($key, $value) {
        if ($this->config === null) {
            return false;
        }

        $this->config[$key] = $value;          
    }
}

?>