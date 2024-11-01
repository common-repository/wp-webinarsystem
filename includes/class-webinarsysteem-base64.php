<?php

class WebinarSysteemBase64
{
    static function url_encode($data) {
        return strtr(base64_encode($data), '+/=', '._-');
    }

    static function url_decode($data) {
        return base64_decode(strtr($data, '._-', '+/='));
    }

    public static function decode_array($base64) {
        try {
            $decoded_base64 = self::url_decode($base64);
            return json_decode($decoded_base64);
        } catch (Exception $e) {
            return null;
        }
    }

    public static function encode_array($data) {
        try {
            $json = wp_json_encode($data);
            return self::url_encode($json);
        } catch (Exception $e) {
            return null;
        }
    }

    // Store JSON in base64 because WP does strange things that often screws up JSON
    // like removing slashes and adding rel="noreferrer noopener" to links :(

    public static function decode_base64_or_json($json) {
        try {
            $res = json_decode($json);

            if ($res != null) {
                return $res;
            }

            return json_decode(base64_decode($json));
        } catch (Exception $e) {
            return null;
        }
    }
}
