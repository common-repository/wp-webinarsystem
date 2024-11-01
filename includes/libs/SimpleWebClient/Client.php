<?php


namespace SimpleWebClient;

class Client
{
    protected $headers = [];
    protected $endpoint = null;
    protected $user_agent = null;

    function __construct($endpoint, $headers) {
        $this->endpoint = $endpoint;
        $this->headers = $headers;
        $this->user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.19 (KHTML, like Gecko) Chrome/1.0.154.53 Safari/525.19';
    }

    public function ping() {
        return false;
    }

    public function send_request($resource, $method, $body = []) {
        $api_url = $this->endpoint . $resource;

        $arguments = [
            'timeout' => 30,
            'sslverify' => false,
            'headers' => array_merge(
                [
                    'User-Agent' => $this->user_agent,
                    'Content-Type' => 'application/json'
                ],
                $this->headers
            ),
            'body' => empty($body) ? array() : wp_json_encode($body)
        ];

        switch ($method) {
            case 'GET':
                $raw_response = wp_remote_get($api_url, $arguments);
                break;

            case 'POST':
                $raw_response = wp_remote_post($api_url, $arguments);
                break;

            default:
                $raw_response = wp_remote_request(
                    $api_url,
                    array_merge(array('method' => $method), $arguments)
                );
                break;
        }

        $response = json_decode(wp_remote_retrieve_body($raw_response), true);
        $response_code = wp_remote_retrieve_response_code($raw_response);
        $success = false;

        if (!is_wp_error($raw_response) &&
            in_array($response_code, array(200, 201, 202, 204))
        ) {
            if (empty($response)) {
                $response = $raw_response['response']['message'];
            }

            $success = true;
        }

        return (object) [
            'success' => $success,
            'data' => $response,
            'code' => $response_code
        ];
    }

    public static function get_client_ip() {
        if (!empty( $_SERVER['HTTP_CLIENT_IP'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field($ip);
    }
}
