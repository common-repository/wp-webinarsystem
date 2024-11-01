<?php
/**
 * @package   WP_GetDrip_API
 * @author Himani Lotia
 * @license   GPL-2.0+
 * @since     1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * WP_GetDrip_API Class
 *
 * Makes Drip API calls the WordPress way
 *
 * @since 1.0.0
 *
 * @author Himani Lotia
 *
 */
class WP_GetDrip_API {

	private $api_token = null;

	private $version = '1.0.0';

	private $endpoint = 'https://api.getdrip.com/v2/';

	private $user_agent = 'wpwebinarsystem drip (getdrip.com)';


	public function __construct( $api_token ) {

		$api_token = trim($api_token);

        if (empty($api_token) || !preg_match('#^[\w-]+$#si', $api_token)) {
            return false;
        }

        $this->api_token = $api_token;

	}

	public function set_drip_api_token( $api_token ) {

		$this->api_token = $api_token;

	}

	/********************
	 * VALIDATION       *
	 ********************/

	/**
	 * Validate Drip API token
	 *
	 * @since  1.0.0
	 *
	 * @author Himani Lotia
	 *
	 * @param $api_token
	 *
	 * @return bool
	 */
	public function validate_drip_token( $api_token ) {


		$is_valid = false;

		$this->set_drip_api_token( $api_token );

		$accounts = $this->list_accounts();

		if ( ! empty( $accounts ) ) {

			$is_valid = true;

		}

		return $is_valid;
	}

	/**************************************************
	 * ACCOUNTS                                       *
	 * https://www.getdrip.com/docs/rest-api#accounts *
	 **************************************************/

	/**
	 * List all accounts
	 *
	 * @since  1.0.0
	 *
	 * @author Himani Lotia
	 *
	 * @return array
	 */
	public function list_accounts() {


		$accounts = array();

		$api_params = array();

		$response = $this->send_request( 'accounts', 'GET', $api_params );

		if ( $response[ 'success' ] ) {

			$accounts = $response[ 'response' ];

		}


		return $accounts;
	}

	public function fetch_account( $account_id ) {
	}

	/*****************************************************
	 * SUBSCRIBERS                                       *
	 * https://www.getdrip.com/docs/rest-api#subscribers *
	 *****************************************************/

	/**
	 * Create or update a subscriber
	 *
	 * @since  1.0.0
	 *
	 * @author Himani Lotia
	 *
	 * @param string $account_id
	 * @param array  $subscriber_data {
	 *
	 * @type string email              Required. The subscriber's email address
	 * @type string new_email          Optional. A new email address for the subscriber. If provided and a
	 *                                      subscriber with the email above does not exist, this address will be used to
	 *                                      create a new subscriber
	 * @type string user_id            Optional. A unique identifier for the user in your database, such as a
	 *                                      primary key
	 * @type string time_zone          Optional. The subscriber's time zone (in Olson format). Defaults to `Etc/UTC`
	 * @type string ip_address         Optional. The subscriber's ip address E.g. "111.111.111.11"
	 * @type array  custom_fields      Optional. An Object containing custom field data. E.g. { "name": "John Doe" }
	 * @type array  tags               Optional. An Array containing one or more tags. E.g. ["Customer", "SEO"]
	 * @type array  remove_tags        Optional. An Array containing one or more tags to be removed from the
	 *                                      subscriber. E.g. ["Customer", "SEO"]
	 * @type bool   prospect           Optional. A Boolean specifiying whether we should attach a lead score to the
	 *                                      subscriber (when lead scoring is enabled). Defaults to `true`
	 * @type int    base_lead_score    Optional. An Integer specifying the starting value for lead score calculation
	 *                                      for this subscriber. Defaults to `30`
	 * }
	 *
	 * @return array
	 */
	public function create_or_update_subscriber( $account_id, $subscriber_data ) {

		$subscribers = array();

		$resource = "{$account_id}/subscribers";

		if ( empty( $subscriber_data[ 'subscribers' ][ 0 ][ 'email' ] ) ) {


		} else {

			$response = $this->send_request( $resource, 'POST', $subscriber_data );

			if ( $response[ 'success' ] ) {

				$subscribers = $response[ 'response' ];

			}

		}


		return $subscribers;
	}

	public function list_subscribers( $account_id ) {
	}

	public function fetch_subscriber( $account_id, $subscriber_id ) {
	}

	/**
	 * Unsubscribe subscriber from one or all campaigns
	 *
	 * @since  1.0.0
	 *
	 * @author Himani Lotia
	 *
	 * @param string $account_id    Required.
	 * @param string $subscriber_id Required. Either the canonical id or the subscriber's email address
	 * @param string $campaign_id   Optional. The campaign from which to unsubscribe the subscriber. Defaults to `all`.
	 *
	 * @return array
	 */
	public function unsubscribe_from_campaign( $account_id, $subscriber_id, $campaign_id = 'all' ) {

		$subscribers = array();

		$resource = "{$account_id}/subscribers/{$subscriber_id}/unsubscribe";

		$api_params = ( 'all' == $campaign_id ) ? array() : array( 'campaign_id' => $campaign_id );

		$response = $this->send_request( $resource, 'POST', $api_params );

		if ( $response[ 'success' ] ) {

			$subscribers = $response[ 'response' ];

		}

		return $subscribers;
	}

	public function delete_subscriber( $account_id, $subscriber_id ) {
	}

	public function create_or_update_batch_subscribers( $account_id, $subscribers_batch ) {
	}

	/***************************************************
	 * CAMPAIGNS                                       *
	 * https://www.getdrip.com/docs/rest-api#campaigns *
	 ***************************************************/

	/**
	 * List all campaigns
	 *
	 * @since  1.0.0
	 *
	 * @author Himani Lotia
	 *
	 * @param string $account_id
	 * @param string $status Optional. Filter by one of the following statuses: draft, active, or paused. Defaults to all
	 * @param int    $page
	 *
	 * @return array
	 */
	public function list_campaigns( $account_id, $page = 1, $status = 'all' ) {

		$campaigns = array();


		$resource = "{$account_id}/campaigns";


		$api_params = ( 'all' == $status ) ? array() : array( 'status' => $status );

		if ( 1 < $page ) {

			$api_params[ 'page' ] = $page;

		}

		$response = $this->send_request( $resource, 'GET', $api_params );


		if ( $response[ 'success' ] ) {

			$campaigns = $response[ 'response' ];

		}


		return $campaigns;

	}

	public function fetch_campaign( $account_id, $campaign_id ) {
	}

	public function activate_campaign( $account_id, $campaign_id ) {
	}

	public function pause_campaign( $account_id, $campaign_id ) {
	}

	public function list_campaign_subscribers( $account_id, $campaign_id ) {
	}

	/**
	 *Subscribe someone to a campaign
	 *
	 * @since  1.0.0
	 *
	 * @author Himani Lotia
	 *
	 * @param string $account_id
	 * @param string $campaign_id
	 * @param array  $options {
	 *
	 * @type string email                  Required. The subscriber's email address
	 * @type string user_id                Optional. A unique identifier for the user in your database, such as a
	 *                                          primary key
	 * @type string time_zone              Optional. The subscriber's time zone (in Olson format). Defaults to `Etc/UTC`
	 * @type bool   double_optin           Optional. If true, the double opt-in confirmation email is sent; if
	 *                                          false, the confirmation email is skipped. Defaults to the value set on
	 *                                          the campaign
	 * @type int    starting_email_index   Optional. The index (zero-based) of the email to send first. Defaults
	 *                                          to `0`
	 * @type array  custom_fields          Optional. An Object containing custom field data. E.g. { "name": "John Doe" }
	 * @type array  tags                   Optional. An Array containing one or more tags. E.g. ["Customer", "SEO"]
	 * @type bool   reactivate_if_removed  Optional. If true, re-subscribe the subscriber to the campaign if there
	 *                                          a removed subscriber in Drip with the same email address; otherwise,
	 *                                          respond with 422 Unprocessable Entity. Defaults to `true`
	 * @type bool   prospect               Optional. A Boolean specifying whether we should attach a lead score
	 *                                          to the subscriber (when lead scoring is enabled). Defaults to `true`
	 * @type int    base_lead_score        Optional. An Integer specifying the starting value for lead score
	 *                                          calculation for this subscriber. Defaults to `30`
	 * }
	 *
	 * @return array
	 */
	public function subscribe_to_campaign( $account_id, $campaign_id, $options ) {

		$subscribers = array();

		$resource = "{$account_id}/campaigns/{$campaign_id}/subscribers";

		if ( empty( $options[ 'subscribers' ][ 0 ][ 'email' ] ) ) {

		} else {

			$response = $this->send_request( $resource, 'POST', $options );

			if ( $response[ 'success' ] ) {

				$subscribers = $response[ 'response' ];

			}

		}


		return $subscribers;
	}

	/************************************************
	 * EVENTS                                       *
	 * https://www.getdrip.com/docs/rest-api#events *
	 ************************************************/

	/**
	 * Record event
	 *
	 * @since  1.0.0
	 *
	 * @author Himani Lotia
	 *
	 * @param string $account_id
	 * @param array  $options {
	 *
	 * @type string email       Required. The subscriber's email address
	 * @type string action      Required. The name of the action taken. E.g. "Logged in"
	 * @type bool   prospect    Optional. Optional. A Boolean specifiying whether we should attach a lead score to the
	 *                          subscriber (when lead scoring is enabled). Defaults to `true`
	 * @type array  properties  Optional. A Object containing custom event properties. If this event is a conversion,
	 *                          include the value (in cents) in the in the properties with a `value` key
	 * @type string occurred_at Optional. The String time at which the event occurred in ISO-8601 format. Defaults to
	 *                          the current time
	 * }
	 *
	 * @return array
	 */
	public function record_event( $account_id, $options ) {

		$events = array();

		$resource = "{$account_id}/events";

		if ( empty( $options[ 'events' ][ 0 ][ 'email' ] ) ) {


		} else if ( empty( $options[ 'events' ][ 0 ][ 'action' ] ) ) {


		} else {

			$response = $this->send_request( $resource, 'POST', $options );

			if ( $response[ 'success' ] ) {

				$events = $response[ 'response' ];

			}

		}


		return $events;
	}

	public function record_batch_events( $account_id, $events_batch ) {
	}

	/**********************************************
	 * TAGS                                       *
	 * https://www.getdrip.com/docs/rest-api#tags *
	 **********************************************/

	/**
	 * Tag a subscriber
	 *
	 * @since  1.0.0
	 *
	 * @author Himani Lotia
	 *
	 * @param string $account_id
	 * @param string $email The subscriber's email address
	 * @param string $tag   The String tag to apply. E.g. "Customer"
	 *
	 * @return bool
	 */
	public function tag_subscriber( $account_id, $email, $tag ) {

		$this->logger->log->debug( __METHOD__ );

		$tag_added = false;

		$resource = "{$account_id}/tags";

		if ( empty( $email ) ) {


		} else if ( empty( $tag ) ) {


		} else {

			$api_params = array( 'email' => $email, 'tag' => $tag );

			$response = $this->send_request( $resource, 'POST', $api_params );

			if ( $response[ 'success' ] ) {

				$tag_added = $response[ 'success' ];

			}

		}


		return $tag_added;
	}

	/**
	 * Remove a subscriber tag
	 *
	 * @since  1.0.0
	 *
	 * @author Himani Lotia
	 *
	 * @param $account_id
	 * @param $email
	 * @param $tag
	 *
	 * @return bool
	 */
	public function remove_tag( $account_id, $email, $tag ) {

		$tag_removed = false;

		$resource = "{$account_id}/tags";

		if ( empty( $email ) ) {


		} else if ( empty( $tag ) ) {


		} else {

			$api_params = array( 'email' => $email, 'tag' => $tag );

			$response = $this->send_request( $resource, 'DELETE', $api_params );

			if ( $response[ 'success' ] ) {

				$tag_removed = $response[ 'success' ];

			}

		}


		return $tag_removed;

	}

	/**************************************************
	 * HELPERS                                        *
	 *                                                *
	 **************************************************/

	/**
	 * Send request to Drip API
	 *
	 * @since  1.0.0
	 *
	 * @author Himani Lotia for gravity+ <support@gravityplus.pro>
	 *
	 * @param $resource
	 * @param $method
	 * @param $body
	 *
	 * @return array
	 */
	public function send_request( $resource, $method, $body ) {

		$response = array();
		$success  = false;

		if ( ! empty( $this->api_token ) ) {

			$api_url = $this->endpoint . $resource;

			$arguments = array(
				'timeout'   => 30,
				'sslverify' => false,
				'headers'   => array(
					'Authorization' => "Basic " . base64_encode( "{$this->api_token}:" ),
					'User-Agent'    => $this->user_agent,
					'Content-Type'  => 'application/vnd.api+json'
				),
				'body'      => empty( $body ) ? array() : wp_json_encode( $body )
			);

			switch ( $method ) {

				case 'GET':

					$raw_response = wp_remote_get( $api_url, $arguments );

					break;

				case 'POST':

					$raw_response = wp_remote_post( $api_url, $arguments );

					break;

				default:

					$raw_response = wp_remote_request( $api_url, array_merge( array( 'method' => $method ), $arguments ) );

					break;
			}

			$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );

			if ( is_wp_error( $raw_response ) || ( ! in_array( wp_remote_retrieve_response_code( $raw_response ), array(
					200,
					201,
					204
				) ) )
			) {
			

			} else {

				if ( empty( $response ) ) {

					$response = $raw_response[ 'response' ][ 'message' ];

				}

				$success = true;

			}

		}

		return array( 'success' => $success, 'response' => $response );

	}

}