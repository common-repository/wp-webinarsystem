<?php

class MauticSimpleClient {
    public function __construct() {
        $this->load_settings();
    }

    public function being_auth(
        $mautic_url,
        $client_id,
        $client_secret,
        $callback_url
    ) {
        $this->settings->status = 'pending';
        $this->settings->mautic_url = $mautic_url;
        $this->settings->client_id = $client_id;
        $this->settings->client_secret = $client_secret;
        $this->settings->callback_url = $callback_url;

        $this->save_settings();

        return add_query_arg([
            'client_id' => $client_id,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $callback_url,
            'response_type' => 'code',
            'state' => md5($client_id)
        ], $mautic_url.'/oauth/v2/authorize');
    }

    public function load_settings() {
        $this->settings = get_option('_wswebinar_mautic');

        if ($this->settings == false) {
            $this->settings = (object)[];
        }
    }

    public function save_settings() {
        update_option('_wswebinar_mautic', $this->settings);
    }

    public function is_connected() {
        return $this->settings != null
            && isset($this->settings->status)
            && $this->settings->status == 'connected';
    }

    public function complete_auth($code) {
        if ($this->settings->status !== 'pending') {
            die('not pending');
            return;
        }

        $response = wp_remote_post($this->settings->mautic_url.'/oauth/v2/token', [
            'body' => [
                'client_id' => $this->settings->client_id,
                'client_secret' => $this->settings->client_secret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->settings->callback_url,
                'code' => $code
            ]
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $body = \json_decode($body, true);

        if (isset($body['error_description'])) {
            return null;
        }

        $this->settings->status = 'connected';
        $this->settings->access_token = $body['access_token'];
        $this->settings->refresh_token = $body['refresh_token'];
        $this->settings->expires_at = time() + intval($body['expires_in']);

        $this->save_settings();
    }

    public function disconnect() {
        $this->settings->status = 'disconnected';
        $this->save_settings();
    }

    public function make_request($action, $data = array(), $method = 'GET') {
        $url = $this->settings->mautic_url.'/api/'.$action;
        $data['access_token'] = $this->settings->access_token;

        $response = false;
        if ($method == 'GET') {
            $url = add_query_arg($data, $url);
            $response = wp_remote_get($url);
        } else if ($method == 'POST') {
            $response = wp_remote_post($url, [
                'body' => $data
            ]);
        }

        if (!$response) {
            return new \WP_Error('invalid', 'Request could not be performed');
        }

        if (is_wp_error($response)) {
            return new \WP_Error('wp_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $body = \json_decode($body, true);

        if(isset($body['errrors'])) {
            if(!empty($body['errrors'][0]['description'])) {
                $message = $body['errrors'][0]['description'];
            } else if(!empty($body['error_description'])) {
                $message = $body['error_description'];
            } else {
                $message = 'Error when requesting to API Server';
            }

            return new \WP_Error('request_error', $message);
        }

        return $body;
    }

    public function subscribe($email, $name) {
        // check the refresh token
        $this->maybe_refresh_token();

        $subscriber = [
            'name' => $name,
            'email' => $email,
            'created_at' => time(),
            'last_seen_at' => time()
        ];

        $response = $this->make_request('contacts/new', $subscriber, 'POST');

        if ($response['contact']['id']) {
            return true;
        }

        return false;
    }

    protected function maybe_refresh_token() {
        $settings = $this->settings;
        $expireAt = $settings->expires_at;

        if ($expireAt && $expireAt <= (time() - 10)) {
            // we have to regenerate the tokens
            $response = wp_remote_post($this->settings->mautic_url.'/oauth/v2/token', [
                'body' => [
                    'client_id' => $this->settings->client_id,
                    'client_secret' => $this->settings->client_secret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->settings->refresh_token,
                    // 'redirect_uri' => $this->settings->redirect_url
                ]
            ]);

            if (is_wp_error($response)) {
                $settings->status = 'disconnected';
                $this->settings = $settings;
                $this->save_settings();
                return;
            }

            $body = wp_remote_retrieve_body($response);
            $body = \json_decode($body, true);

            if(isset($body['error_description'])) {
                $settings->status = 'disconnected';
            }

            $settings->access_token = $body['access_token'];
            $settings->refresh_token = $body['refresh_token'];
            $settings->expires_at = time() + intval($body['expires_in']);

            $this->settings = $settings;

            $this->save_settings();
        }
    }
}
