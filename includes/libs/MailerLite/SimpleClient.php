<?php

class MailerliteSimpleClient extends SimpleWebClient\Client {
    protected $api_key = null;

    function __construct($key) {
        $this->api_key = $key;

        parent::__construct(
            "https://api.mailerlite.com/api/v2/", [
                'X-MailerLite-ApiKey' => $key
            ]
        );
    }

    public function get_lists() {
        $response = $this->send_request('groups', 'GET');

        if ($response->success != true) {
            return null;
        }

        $res = [];
        $lists = $response->data;

        foreach ($lists as $item) {
            $res[] = (object) [
                'id' => strval($item['id']),
                'name' => $item['name']
            ];
        }

        return $res;
    }

    public function add_contact($list_id, $name, $email) {
        $params = [
            'email' => $email,
            'name' => $name,
        ];

        $res = $this->send_request(
            'groups/'.$list_id.'/subscribers',
            'POST', $params);

        return $res->success;
    }
}
