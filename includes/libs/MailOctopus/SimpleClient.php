<?php

class MailOctopusSimpleClient extends SimpleWebClient\Client {
    protected $api_key = null;

    function __construct($key) {
        $this->api_key = $key;

        parent::__construct(
            "https://emailoctopus.com/api/1.5/", []
        );
    }

    public function get_lists() {
        $response = $this->send_request('lists?api_key='.$this->api_key, 'GET');

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

    public function add_contact($list_id, $first_name, $last_name, $email) {
        $fields = [];

        if (strlen($first_name)) {
            $fields['FirstName'] = $first_name;
        }

        if (strlen($last_name)) {
            $fields['LastName'] = $last_name;
        }

        $params = [
            'api_key' => $this->api_key,
            'email_address' => $email,
            'fields' => $fields
        ];

        $res = $this->send_request(
            'lists/'.$list_id.'/contacts',
            'POST', $params);

        return $res->success;
    }
}
