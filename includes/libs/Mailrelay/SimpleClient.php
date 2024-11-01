<?php

class MailrelaySimpleClient extends SimpleWebClient\Client {
    protected $api_key = null;

    function __construct($key, $host) {
        $this->api_key = $key;

        parent::__construct(
            "https://{$host}/ccm/admin/api/version/2/&type=json",
            []
        );
    }

    public function get_lists() {
        $params = [
            'function' => 'getGroups',
            'apiKey' => $this->api_key,
            'offset' => 0,
            'count' => 50,
        ];

        $response = $this->send_request('', 'POST', $params);

        if ($response->success != true) {
            return null;
        }

        $res = [];
        $lists = $response->data['data'];

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
            'function' => 'addSubscriber',
            'apiKey' => $this->api_key,
            'email' => $email,
            'name' => $name,
            'groups' => [$list_id]
        ];

        $res = $this->send_request('', 'POST', $params);
        return $res->success;
    }
}
