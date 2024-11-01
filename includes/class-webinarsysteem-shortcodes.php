<?php

if (!defined('ASSFURL')) {
    define('ASSFURL', WP_PLUGIN_URL . "/" . dirname(plugin_basename(__FILE__)));
}

class WebinarSysteemShortCodes
{
    static function init() {
        add_shortcode('webinarsystem_registration', array('WebinarSysteemShortCodes', 'registration'));
        add_shortcode('webinarsystem_login', array('WebinarSysteemShortCodes', 'login'));

        // Registration widget (support both old and new format)
        add_shortcode('wpws_registration', array('WebinarSysteemShortCodes', 'registration_widget'));
        add_shortcode('webinarpress_registration', array('WebinarSysteemShortCodes', 'registration_widget'));

        // woocommerce user webinars
        if (WebinarSysteemWooCommerceIntegration::is_woo_commerce_ready()) {
            add_shortcode('webinarpress_woocommerce_user_webinars', array('WebinarSysteemShortCodes', 'woocommerce_user_webinars'));
        }

        // Add buttons to the editor
        add_filter('mce_buttons', array('WebinarSysteemShortCodes', 'register_tinymce_buttons'));
        add_filter('mce_external_plugins', array('WebinarSysteemShortCodes', 'register_tinymce_javascript'));
        // add_action('admin_footer', array('WebinarSysteemShortCodes', 'register_tinymce_webinars'));
        add_action('admin_footer', array('WebinarSysteemShortCodes', 'register_tinymce_forms'));

        WebinarSysteemConfirmationShortCodes::init();
    }

    static function registration($attributes)
    {
        wp_enqueue_script('wpws-registration', ASSFURL . '/js/registration.js', array('jquery',), WPWS_PLUGIN_VERSION, false);

        $attrs = shortcode_atts(array(
            'id' => "no_post_id",
            'url' => NULL,
            'button' => NULL
        ), $attributes);

        ob_start();

        //If posts exists
        if (get_post_status($attrs['id']) === FALSE) {
            __('Error: ', '_wswebinar') . __('Invalid webinar id.', '_wswebinar');
            $content = ob_get_clean();
            return $content;
        }

        $meta_btn_txt = get_post_meta($attrs['id'], '_wswebinar_regp_ctatext', true);
        $registerButtonText = (!empty($attrs['button']) ? $attrs['button'] : (!empty($meta_btn_txt) ? $meta_btn_txt : __('Sign Up', '_wswebinar')));

        $postId = $attrs['id'];
        $url = $attrs['url'];

        $webinar = WebinarSysteemWebinar::create_from_id($postId);

        if ($webinar == null) {
            return '';
        }

        $registration_disabled = get_post_meta($postId, '_wswebinar_gener_regdisabled_yn', true);

        if (!empty($registration_disabled)) {
            ?>
            <div class="text-center round-border-full signup">
                <h1><?php esc_html_e('Registration is closed for this webinar.', '_wswebinar') ?></h1>
            </div>
            <?php
            $content = ob_get_clean();
            return $content;
        }
        ?>

        <form method="POST" name="wpws_webinar_register">
            <?php if (!empty($url)) { ?>
                <input type="hidden" name="redirect" value="<?php echo esc_url($url) ?>">
            <?php } ?>
            <input type="hidden" name="webinar_id" value="<?php echo intval($postId) ?>">
            <input class="form-control forminputs wswebinarsys-registration-name-input" name="inputname" required
                   placeholder="<?php esc_html_e('Your Name', '_wswebinar') ?>" type="text" />

            <input class="form-control forminputs wswebinarsys-registration-email-input" name="inputemail" required
                   placeholder="<?php esc_html_e('Your Email Address', '_wswebinar') ?>" type="email" />

            <?php
            $fields = $webinar->get_custom_fields();
            if (!empty($fields))
                foreach ($fields as $field) {
                    $customFieldIsText = $field->type != "checkbox";
                    ?>

                    <?php if ($customFieldIsText) { ?>
                        <input id="ws-<?php echo esc_attr($field->id) ?>" <?php echo isset($field->isRequired) && $field->isRequired ? 'required' : '' ?>
                               name="ws-<?php echo esc_attr($field->id) ?>" type="<?php echo esc_attr($field->type) ?>"
                               placeholder="<?php echo esc_attr($field->labelValue) ?>"
                               class="form-control forminputs custom-reg-field">
                    <?php } else { ?>
                        <label for="ws-<?php echo esc_attr($field->id) ?>" style="cursor: pointer; margin-bottom: 0px;">
                            <input id="ws-<?php echo esc_attr($field->id) ?>" name="ws-<?php echo esc_attr($field->id) ?>"
                                   type="<?php echo esc_attr($field->type) ?>" placeholder="<?php echo esc_attr($field->labelValue) ?>">
                            <?php echo esc_html($field->labelValue) ?> </label>
                        <?php
                    }
                }
            ?>

            <?php
            $regp_gdpr_optin_yn_value = get_post_meta($postId, '_wswebinar_regp_gdpr_optin_yn', true);
            $showGDPROptin = ($regp_gdpr_optin_yn_value == "yes") ? true : false;
            $regp_gdpr_optin_text_value = get_post_meta($postId, '_wswebinar_regp_gdpr_optin_text', true); ?>
            <div class="text-left regGdprOpted">
                <?php
                if ($showGDPROptin) { ?>
                    <input style="vertical-align: middle; position: relative; bottom: 1px;" type="checkbox"
                           id="regGdprOpted" name="regp_gdpr_optin" required value=""/>
                    <p style="vertical-align: middle; display: inline;"><?php echo esc_html($regp_gdpr_optin_text_value); ?></p>
                    <?php
                }
                ?>
            </div>
            <button class="forminputs wswebinarsys-registration-submit-btn"
                    type="submit"><?php echo esc_html($registerButtonText) ?></button>
        </form>

        <?php 
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (!empty($_REQUEST['error']) && $_REQUEST['error'] == 'notregisterd'): 
        ?>
        <span class="error"><?php esc_html_e('This email is not registered.', '_wswebinar') ?></span>
    <?php
    endif;
        $content = ob_get_clean();
        return $content;
    }

    static function login($attributes)
    {
        wp_enqueue_script('wpws-registration', ASSFURL . '/js/registration.js', array('jquery',), WPWS_PLUGIN_VERSION, false);

        wp_localize_script('wpws-external', 'wpws', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce(WebinarSysteemJS::get_nonce_secret())
        ));

        $attrs = shortcode_atts(array(
            'id' => "no_post_id",
            'button' => NULL
        ), $attributes);

        ob_start();

        if (get_post_status($attrs['id']) === FALSE) {
            __('Error: ') . __('Invalid webinar id.', '_wswebinar');
            $content = ob_get_clean();
            return $content;
        }

        $webinar_id = $attrs['id'];
        $metaLoginButtonText = get_post_meta($webinar_id, '_wswebinar_regp_loginctatext', true);
        $postId = $attrs['id'];

        $loginButtonText = (!empty($attrs['button']) ? $attrs['button']
            : (!empty($metaLoginButtonText)
                ? $metaLoginButtonText : 'Login'));

        ?>
        <form method="POST" name="wpws_webinar_login">
            <span class="error login_error">
                <?php esc_html_e('This email is not registered.', '_wswebinar') ?>
            </span>
            <input type="hidden" name="webinar_id" value="<?php echo INTVAL($postId) ?>">
            <input class="form-control forminputs wswebinarsys-login-email-input" name="inputemail"
                   placeholder="<?php esc_html_e('Your Email Address', '_wswebinar') ?>" type="email" />
            <button class="forminputs wswebinarsys-login-submit-btn"
                    type="submit"><?php echo esc_html($loginButtonText) ?></button>
        </form>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    /**
     * Add buttons to tinyMCE
     *
     * @param array $buttons
     * @return array
     */
    static function register_tinymce_buttons($buttons)
    {
        array_push($buttons, 'separator', 'login_register_shortcodes');
        return $buttons;
    }

    static function register_tinymce_javascript($plugin_array)
    {
        $plugin_array['wpwebinarsystem'] = plugins_url('/js/tinymce-custom.js', __FILE__);
        return $plugin_array;
    }

    static function register_tinymce_webinars()
    {
        global $post;

        $args = array(
            'posts_per_page' => -1,
            'offset' => 0,
            'category' => '',
            'category_name' => '',
            'orderby' => 'date',
            'order' => 'DESC',
            'include' => '',
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
            'exclude' => '',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_key' => '',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'meta_value' => '',
            'post_type' => 'wswebinars',
            'post_mime_type' => '',
            'post_parent' => '',
            'author' => '',
            'author_name' => '',
            'post_status' => 'publish',
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters
            'suppress_filters' => true
        );
        $posts_array = get_posts($args);

        ?>

        <script type="text/javascript">
            var wpwebinarsystem_shortcode_data = [[
                <?php foreach ($posts_array as $__p): ?>
                {
                    text: '<?php echo esc_js(addslashes($__p->post_title)); ?>', onclick: function () {
                        tinyMCE.activeEditor.insertContent('[webinarsystem_registration id="<?php echo esc_attr($__p->ID); ?>" url="" button=""] ');
                    }
                },
                <?php endforeach; ?>
            ],
                [
                    <?php foreach ($posts_array as $__p): ?>
                    {
                        text: '<?php echo esc_js(addslashes($__p->post_title)); ?>', onclick: function () {
                            tinyMCE.activeEditor.insertContent('[webinarsystem_login id="<?php echo esc_attr($__p->ID); ?>" url="" button=""] ');
                        }
                    },
                    <?php endforeach; ?>
                ]];
        </script>
        <?php
    }

    static function register_tinymce_forms()
    {
        global $post;

        $posts = get_posts([
            'posts_per_page' => -1,
            'orderby' => 'post_title',
            'post_type' => WebinarSysteemRegistrationWidget::$post_type,
            'post_status' => 'publish',
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters
            'suppress_filters' => true
        ]);
        ?>

        <script type="text/javascript">
            var __wpws_registration_widgets = [];
            <?php foreach ($posts as $widget) { ?>
            __wpws_registration_widgets.push({
                text: '<?php echo esc_attr(addslashes($widget->post_title)); ?>',
                onclick: function () {
                    tinyMCE.activeEditor.insertContent('[wpws_registration id="<?php echo esc_attr($widget->ID); ?>"]');
                }
            });
            <?php } ?>
        </script>
        <?php
    }

    static function registration_widget($attributes)
    {
        WebinarSysteemJS::embed_assets();

        $id = $attributes['id'];

        // get the form
        $params = WebinarSysteemRegistrationWidget::get_widget_params($id);

        if (!$params) {
            return '';
        }

        // get the webinar
        $webinar = WebinarSysteemWebinar::create_from_id($params->webinarId);
  
        if (!$webinar) {
            return '';
        }

        $webinar_info = WebinarSysteemRegistrationWidget::get_webinar_info($webinar);

        ob_start();
        ?>
        <div
            class="wpws_registration_widget"
            data-webinar='<?php echo esc_attr(str_replace('\'', '&apos;', wp_json_encode($webinar_info))) ?>'
            data-params='<?php echo esc_attr(str_replace('\'', '&apos;', wp_json_encode($params))) ?>'
            data-widgetId='<?php echo esc_attr($id) ?>'
        ></div>
        <?php
        return ob_get_clean();
    }

    static function woocommerce_user_webinars($attributes) {
        ob_start();
        WebinarSysteemWooCommerceIntegration::show_user_webinars();
        return ob_get_clean();
    }
}
