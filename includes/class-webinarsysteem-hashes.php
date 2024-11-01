<?php

class WebinarSysteemHashes {
    static protected $encryption_algorithm = 'AES-256-CBC';

    public static function get_hash_key() {
        return WebinarSysteemSettings::instance()->get_key('hash_key');
    }

    public static function get_secure_hash($data) {
        $key = self::get_hash_key();
        return wp_hash($data, $key);
    }

    public static function encrypt($string) {
        $secret_key = WebinarSysteemSettings::instance()->get_key('enc_key');
        $secret_iv = WebinarSysteemSettings::instance()->get_key('enc_iv');

        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        return base64_encode(openssl_encrypt($string, self::$encryption_algorithm, $key, 0, $iv));
    }

    public static function decrypt($string) {
        $secret_key = WebinarSysteemSettings::instance()->get_key('enc_key');
        $secret_iv = WebinarSysteemSettings::instance()->get_key('enc_iv');

        $key = hash('sha256', $secret_key);
        $iv = substr(hash( 'sha256', $secret_iv), 0, 16 );

        return openssl_decrypt(base64_decode($string), self::$encryption_algorithm, $key, 0, $iv);
    }
}
