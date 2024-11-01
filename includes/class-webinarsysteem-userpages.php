<?php 
class WebinarSysteemUserPages {
	static function register_post_type() {
		register_post_type('wpws_page', [
          'labels' => [
            'name' => __('WPWS Page', '_wswebinar') ,
            'singular_name' => __('WPWS Page', '_wswebinar')
          ],
          'public' => true,
          'has_archive' => false,
          'show_ui' => false,
          'show_in_menu' => false,
          'rewrite' => false,
          'show_in_nav_menus' => false,
          'can_export' => false,
          'publicly_queryable' => true,
          'exclude_from_search' => true
        ]);
	}
	
	static function create_unsubscribe_page() {
        remove_all_actions('pre_post_update');
        remove_all_actions('save_post');
        remove_all_actions('wp_insert_post');

		$id = wp_insert_post([
            'post_status' => 'publish',
            'post_type' => 'wpws_page',
            'post_author' => 1,
            'post_content' => __('You are now unsubscribed', '_wswebinar'),
            'post_title' => __('Webinar Subscription', '_wswebinar'),
            'post_name' => 'webinar-unsubscribe'
		]);
		
		flush_rewrite_rules();
		
		return $id;
	}

	static function create_webinar_overview_page() {
        remove_all_actions('pre_post_update');
        remove_all_actions('save_post');
        remove_all_actions('wp_insert_post');

		$id = wp_insert_post([
            'post_status' => 'publish',
            'post_type' => 'wpws_page',
            'post_author' => 1,
            'post_content' => '[wpws_overview]',
            'post_title' => __('Webinar Overview', '_wswebinar'),
            'post_name' => 'webinar-overview'
		]);
		
		flush_rewrite_rules();
		
		return $id;
	}

	static function handle_unsubscribe() {
        global $wpdb;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET['action']) || !isset($_GET['data'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $action = $_GET['action'];
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $email = WebinarSysteemHashes::decrypt($_GET['data']);

        // handle legacy encryption keys
        if (!is_email($email)) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $email = self::encrypt_decrypt($_GET['data'], 'd');
        }

        // make sure we have an eamil
        if ($email == false || strlen($email) == 0 || !is_email($email)) {
            return;
        }

        if ($action == 'unsubscribe' || $action == 'wpws-unsubscribe') {
            WebinarSysteemUnsubscribe::unsubscribe($email);

            // webinar unsubscribe (legacy)
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_GET['id'])) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.Security.NonceVerification.Recommended	
                $wpdb->delete(WSWEB_DB_TABLE_PREFIX.'subscribers', [ 'email' => (string) $email, 'webinar_id' => (int) $_GET['id'] ]);
            }
            return;
        }

        if ($action == 'wpws-subscribe') {
            WebinarSysteemUnsubscribe::subscribe($email);
            return;
        }
	}

	static function get_page_content($page_content = '[wpws_unsubscribe]') {
		global $post;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ($post == null || $page_content == null || !isset($_GET['action'])) {
            return $page_content;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($post->post_name === 'webinar-overview' && isset($_GET['data'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$email = WebinarSysteemHashes::decrypt($_GET['data']);
			$content = self::get_overview_content($email);
			return str_replace('[wpws_overview]', trim($content), $page_content);
        }

        return $page_content;
	}
	
	static function get_overview_content($email = '') {
		ob_start();
		$is_unsubscribed = WebinarSysteemUnsubscribe::is_unsubscribed($email);

		$action_url = $is_unsubscribed
            ? self::get_subscribe_url($email)
            : self::get_unsubscribe_url($email);

		?>
        <form method="post" action="<?php echo esc_url($action_url) ?>">
            <?php
            if ($is_unsubscribed) {
                ?>
                <p>
                <?php echo esc_html_e('You have been unsubscribed from all webinar emails', '_wswebinar') ?>
                </p>
                <p>
                    <button
                        class="webinarpress-button webinarpress-subscribe"
                        type="submit"
                    >
                        <?php echo esc_html_e('Subscribe again', '_wswebinar') ?>
                    </button>
                </p>
                <?php
            } else {
                ?>
                <p>
                    <?php echo esc_html_e('You are subscribed to webinar emails', '_wswebinar') ?>
                </p>
                <p>
                    <button
                        class="webinarpress-button webinarpress-unsubscribe"
                        type="submit"
                    >
                        <?php echo esc_html_e('Unsubscribe', '_wswebinar') ?>
                    </button>
                </p>
                <?php
            }
            ?>
        </form>
        <?php

		return ob_get_clean();
	}

	static function encrypt_decrypt($string, $action ='e') {
		$secret_key = 'my_simple_secret_key';
    	$secret_iv = 'my_simple_secret_iv';
 
    	$encrypt_method = "AES-256-CBC";
    	$key = hash( 'sha256', $secret_key );
    	$iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );
 
    	if ($action == 'e') {
            return base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
    	}

        return openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	}
	
	static function get_email_management_url($email) {
		$data = ['data' => WebinarSysteemHashes::encrypt($email)];

		return self::get_subscription_url(
			WebinarSysteemSettings::instance()->get_overview_page_id(),
			'wpws-overview',
			$data
		);
	}
	
	static function get_unsubscribe_url($email) {
		$data = ['data' => WebinarSysteemHashes::encrypt($email)];

		return self::get_subscription_url(
			WebinarSysteemSettings::instance()->get_overview_page_id(),
			'wpws-unsubscribe',
			$data
		);
	}

    static function get_subscribe_url($email) {
        $data = ['data' => WebinarSysteemHashes::encrypt($email)];

        return self::get_subscription_url(
            WebinarSysteemSettings::instance()->get_overview_page_id(),
            'wpws-subscribe',
            $data
        );
    }
	
	static function get_subscription_url($post, $action, $data) {
		$url = get_permalink($post);

        $params = [
            'action='.$action,
            'data='.$data['data']
        ];

		if (!empty($data)) {
		    $url .= (wp_parse_url($url,PHP_URL_QUERY) ? '&' : '?').join('&', $params);
		}

		return $url;
	}
}
?>