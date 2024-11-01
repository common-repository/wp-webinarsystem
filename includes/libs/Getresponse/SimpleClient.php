<?php

class GetResponseSimpleClient extends SimpleWebClient\Client {
    protected $api_key = null;

    function __construct($key) {
        parent::__construct(
            'https://api.getresponse.com/v3',
            ['X-Auth-Token' => 'api-key ' . $key]
        );
    }

    public function ping() {
        $res = $this->send_request('/accounts', 'GET');
        return $res->success;
    }

    public function add_contact($campaign_id, $name, $email) {
        $params = [
            'campaign' => [
                'campaignId' => $campaign_id
            ],
            'email' => $email,
            'name' => $name,
            'ipAddress' => self::get_client_ip()
        ];

        $res = $this->send_request('/contacts', 'POST', $params);

        return $res->success;
    }

    public function list_campaigns() {
        $res = $this->send_request('/campaigns', 'GET');
        if (!$res->success) {
            return [];
        }

        return array_map(function ($val) {
            return (object) $val;
        }, $res->data);
    }
}
