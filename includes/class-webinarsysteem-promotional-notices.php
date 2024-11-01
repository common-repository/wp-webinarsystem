<?php

/**
 * Description of WebinarSysteemPromotionalNotices
 * Show notices for seasonal pro-plugin promotions.
 * 
 * 
 */
class WebinarSysteemPromotionalNotices {

    public static $notice_slug = "ws-notice-";

    /**
     * Created for Valentine.
     * 
     * Will be displayed till 1st of February to 16th of February 2016 only to NEW USERS.
     */
    static function valentine() {
        $user_id = get_current_user_id();
        $meta = get_user_meta($user_id, self::$notice_slug . 'valentine', true);
        $rightTime = time() < strtotime("17 Feb 2016");
        if (!empty($meta) || !$rightTime)
            return;
        global $current_user;
        wp_get_current_user();
        add_user_meta($user_id, self::$notice_slug . 'valentine', NULL, true);
        ?>
        <div class="ws-notice">
            <div class="notice-image-container">
                <img src="<?php echo esc_url(plugins_url('./images/webinarbot-valentine-hearteyes.png', __FILE__)) ?>" height="100">
            </div>
            <div class="notice-text">
                <?php esc_html_e("Hey", '_wswebinar'); ?> <strong><?php echo esc_html($current_user->display_name); ?></strong><?php esc_html_e(", thank you for using my plugin. Want more functionalities like automated, recurring and paid webinars? Then download <strong>WebinarPress Pro during this Valentine's celebration with 30% off!</strong> This promotion is only for you as a user of this free version of WebinarPress.<br>Use coupon <strong>lovewebinarbot</strong> during checkout.<br>Love, Webinarbot", '_wswebinar'); ?>
            </div>
            <div class="notice-button-container">
                <a class="button button-primary" href="http://www.wpwebinarsystem.com/?utm_source=pluginfreeversion&utm_medium=notification&utm_content=valentineweekend&utm_campaign=valentinenotification" target="_blank"><?php esc_html_e('Yes, download Pro!', '_wswebinar') ?></a><br/>
                <div class="welcome-panel-close" data-notice-slug="valentine">
                    Dismiss
                </div>
            </div>
        </div>
        <?php
    }

    static function footerRating() {
        printf('If you like %1$s please leave us a %2$s rating. A huge thank you in advance!',
            '<strong>WebinarPress</strong>',
            '<a href="https://wordpress.org/support/view/plugin-reviews/wp-webinarsystem?filter=5#postform" target="_blank">★★★★★</a>'
        );
    }

    /**
     * Ajax call to dismiss a given notice
     * 
     */
    static function dismiss() {
        $user_id = get_current_user_id();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $status = update_user_meta($user_id, self::$notice_slug . $_POST['notice_slug'], true);
        echo wp_json_encode(array('status' => $status));
        wp_die();
    }

}
