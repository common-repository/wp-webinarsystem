<?php

class MailChimpSimpleClient extends SimpleWebClient\Client {
    protected $api_key = null;

    function __construct($key) {
        $this->api_key = $key;

        $dc = 'us1';

        if (strstr($key, "-")){
            list($key, $dc) = explode("-", $key, 2);
            if (!$dc) {
                $dc = 'us1';
            }
        }

        $endpoint = str_replace(
            'https://api',
            'https://'.$dc.'.api',
            'https://api.mailchimp.com/3.0'
        );

        parent::__construct(
            $endpoint,
            []
        );
    }

    public function get_lists($filters=array(), $start=0, $limit=25, $sort_field='created', $sort_dir='DESC') {
        $headers = array(
			'Authorization' => "Bearer $this->api_key",
		);
		$params = [
            'filters' => $filters,
            "start" => $start,
            'limit' => $limit,
            'sort_field' => $sort_field,
            'sort_dir' => $sort_dir];
		$this->headers = $headers;
        $response = $this->send_request('/lists/', 'GET', $params);
        if ($response->success != true) {
            return null;
        }

        $res = [];
        $lists = $response->data['lists'];

        foreach ($lists as $item) {
            $res[] = [
                'id' => $item['id'],
                'name' => $item['name']
            ];
        }
        return $res;
    }

    public function add_contact($list_id, $first_name, $last_name, $email) {
		$headers = array(
			'Authorization' => "Bearer $this->api_key",
		);
        $params = [
            'id' => $list_id,
            'email_address' => $email,
            'status' => 'subscribed',
			'full_name' => htmlentities($first_name).' '.htmlentities($last_name),
            'merge_fields' => [
                'FNAME' => htmlentities($first_name),
                'LNAME' => htmlentities($last_name)
            ],
            'email_type' => 'html',
            'double_optin' => false,
            'update_existing' => false,
            'replace_interests' => true,
            'send_welcome' => false
        ];
		$this->headers = $headers;
        $res = $this->send_request('/lists/'.$list_id.'/members', 'POST', $params);
        return $res->success;
    }
}
