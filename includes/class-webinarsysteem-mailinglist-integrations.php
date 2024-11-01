<?php

class WebinarsysteemMailingListIntegrations {

    public static $consumerKey = ""; # For Aweber developer account.
    public static $consumerSecret = "";  # For Aweber developer account.

    /**
     * aWeber API Key validation
     * 
     * @return boolean
     */
    public static function is_aweber_connected() {
        $has_tokens = false;
        $can_communicate = false;
        $token_secret = get_option('_wswebinar_aweber_accessTokenSecret');
        $token_secret_token = get_option('_wswebinar_aweber_accessToken');
        $has_tokens = (!empty($token_secret) & !empty($token_secret_token) ? TRUE : FALSE);

        if ($has_tokens) {
            $aweber = new WSAWeberAPI(self::$consumerKey, self::$consumerSecret);
            try {
                $account = $aweber->getAccount(get_option('_wswebinar_aweber_accessToken'), get_option('_wswebinar_aweber_accessTokenSecret'));
            } catch (Exception $ex) {
                update_option(WebinarSysteem::$lang_slug . '_aweber_key_revoked', true);
                self::revokeAweberConfig();
                return false;
            }

            $account_id = $account->id;
            $can_communicate = (!empty($account_id) ? true : false);
        }
        return ($has_tokens && $can_communicate ? TRUE : FALSE);
    }

    public static function is_mautic_connected() {
        $mautic = new MauticSimpleClient();
        return $mautic->is_connected();
    }

    /**
     * Checks if ActiveCampaign API Key and URL is valid.
     * 
     * @param string $key
     * @param string $url
     * @return boolean
     */
    public static function is_activecampaign_connected($key = NULL, $url = NULL) {
        $key = $key ? $key : get_option('_wswebinar_activecampaignapikey');
        $url = $url ? $url : get_option('_wswebinar_activecampaignurl');

        if (!$key && !$url)
            return FALSE;

        $ac = new WPWS_ActiveCampaign($url, $key);
        if ((int) $ac->credentials_test())
            return TRUE;

        return FALSE;
    }

    public static function validate_enormail_key($key) {
        $lists = new EM_Account(new Em_Rest($key));
        $set = $lists->info();

        $decoded_set = json_decode($set);
        return !isset($decoded_set->error);
    }

    public static function validate_drip_api_key($key) {
        $drip = new WP_GetDrip_API($key);
        return $drip->validate_drip_token($key);
    }

    public static function validate_activecampaign_api_key($key, $url) {
        $ac = new WPWS_ActiveCampaign($url, $key);
        return $ac->credentials_test();
    }

    public static function validate_convertkit_api_key($api_key = null) {
        $client = new calderawp\convertKit\forms($api_key);
        $response = $client->get_all();

        if (!$response) {
            return false;
        }

        return $response->success;
    }

    public static function validate_mailchimp_api_key($api_key) {
        $client = new MailChimpSimpleClient($api_key);
        return $client->get_lists() != null;
    }

    public static function validate_mailrelay_api_key($api_key, $host) {
        $client = new MailrelaySimpleClient($api_key, $host);
        return $client->get_lists() != null;
    }

	/**
	* Get Drip Campaigns
	* 
	* @return Campaign List
	*/
	public static function getDripCampaigns(){
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$account_id = $_GET['account_id'];	

		$account_campaigns = array(
			array(
				'label' => '',
				'value' => ''
			)
		);
		$api_key = get_option('_wswebinar_dripapikey');
			if(!empty($account_id)){
						$_drip_api = new WP_GetDrip_API($api_key);
		$_drip_api->set_drip_api_token($api_key);
		$campaigns = $_drip_api->list_campaigns($account_id);
		if( ! empty( $campaigns )) {
			if ( 1 < $campaigns[ 'meta' ][ 'total_pages' ] ) {

					$all_campaigns = $campaigns[ 'campaigns' ];

					while ( $campaigns[ 'meta' ][ 'page' ] < $campaigns[ 'meta' ][ 'total_pages' ] ) {

						$campaigns = $_drip_api->list_campaigns( $account_id, $campaigns[ 'meta' ][ 'page' ] + 1 );

						if ( ! empty( $campaigns ) ) {

							$all_campaigns = array_merge( $all_campaigns, $campaigns[ 'campaigns' ] );

						}

					}
		}
		else
		{
			$all_campaigns = $campaigns[ 'campaigns' ];
		}
		foreach ( $all_campaigns as $campaign ) {

					$account_campaigns[ ] = array( 'label' => $campaign[ 'name' ], 'value' => $campaign[ 'id' ] );

				}
			}
	}
	
	echo wp_json_encode($account_campaigns);
	wp_die();		

	}

	/**
	* Get Drip Account Choices
	* 
	* @return
	*/
	public static function get_drip_account_lists($key) {
		$account_choices = [
            [
                'label' => '',
                'value' => ''
            ]
		];
		
		$_drip_api = new WP_GetDrip_API($key);
		$_drip_api->set_drip_api_token($key);
			
		$accounts = $_drip_api->list_accounts();
		
		if( !empty($accounts)) {
			foreach ($accounts['accounts'] as $account){
				$account_choices[] = array('label' => $account['name'], 'value' => $account['id'] );
			}
		}

		return $account_choices;
	}

    /*
     * Connect with Aweber Mailing API
     * Set cookies and update options.
     */
    public static function aweber_connect() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET['wswebinar_aweber_connect'])) {
            return;
        }

        $aweber = new WSAWeberAPI(self::$consumerKey, self::$consumerSecret);
        $_wswebinar_aweber_accessToken = get_option('_wswebinar_aweber_accessToken');
        if (empty($_wswebinar_aweber_accessToken)) {
            $webinar_aweber_access_token = get_option('_wswebinar_aweber_accessToken');
            if (empty($webinar_aweber_access_token)) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $auth_token = @$_GET['oauth_token'];
                if (empty($auth_token)) {
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $callbackUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                    list($requestToken, $requestTokenSecret) = $aweber->getRequestToken($callbackUrl);
                    update_option(WebinarSysteem::$lang_slug . '_aweber_request_token_secret', $requestTokenSecret);
                    setcookie('webinar_aweberrtkns', $requestTokenSecret);
                    header("Location: {$aweber->getAuthorizeUrl()}");
                    exit();
                }
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $aweber->user->tokenSecret = $_COOKIE['webinar_aweberrtkns'];
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $aweber->user->requestToken = $_GET['oauth_token'];
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $aweber->user->verifier = $_GET['oauth_verifier'];
                list($accessToken, $accessTokenSecret) = $aweber->getAccessToken();

                update_option(WebinarSysteem::$lang_slug.'_aweber_accessTokenSecret', $accessTokenSecret);
                update_option(WebinarSysteem::$lang_slug.'_aweber_accessToken', $accessToken);
                update_option(WebinarSysteem::$lang_slug.'_aweber_key_success', 1);

                $home_url = home_url();
                header('Location: ' . "$home_url/wp-admin/admin.php?page=wswbn-settings#mailing-lists");
                exit();
            }
        }
    }
   
    public static function check_aweber_disconnected() {
        $showed = get_option('_wswebinar_aweber_key_revoked');
        if ($showed == 1) {
            ?>
            <div class="error">
                <p><?php echo sprintf( 
                        // translators: %s will be replaced with the admin setting URL.
                        esc_html__('Unexpectedly aWeber has been disconnected from the server. You are no longer subscribed to aWeber mailinglist. For Changes go to <a href="%s">WebinarSysteem Settings</a>.', '_wswebinar'), "admin.php?page=wswbn-settings"); ?></p>
            </div>
            <?php
            update_option(WebinarSysteem::$lang_slug . '_aweber_key_revoked', false);
        }
    }

    public static function validate_getresponse_key($key) {
        $client = new GetResponseSimpleClient($key);
        return $client->ping();
    }

    public static function get_getresponse_api_key() {
        return get_option('_wswebinar_getresponseapikey');
    }

    /*
     * Remoke the Aweber API configuration from the App.
     */
    
    public static function revokeAweberConfig() {
        unset($_COOKIE['webinar_aweberrtkns']);
        update_option(WebinarSysteem::$lang_slug . '_aweber_accessTokenSecret', '');
        update_option(WebinarSysteem::$lang_slug . '_aweber_accessToken', '');
        update_option(WebinarSysteem::$lang_slug . '_aweber_key_success', 1);
        return true;
    }

    public static function get_convertkit_api_key() {
        return get_option('_wswebinar_convertkit_key');
    }

    public static function is_convertkit_connected() {
        $key = WebinarsysteemMailingListIntegrations::get_convertkit_api_key();
        return ($key && strlen($key) > 0);
    }

    public static function get_accounts_for_provider($provider) {
        switch ($provider) {
            case 'drip':
                $key = get_option('_wswebinar_dripapikey');
                $res = self::get_drip_account_lists($key);
                return array_map(function ($val) {
                    return (object) [
                        'id' => $val['value'],
                        'name' => $val['label']
                    ];
                }, $res);

            default:
                return [];
        }
    }

    public static function get_mailinglist_lists_for_provider($provider, $account_id) {
        return [];
    }

    public static function get_enabled_providers() {
        return [];
    }
}
