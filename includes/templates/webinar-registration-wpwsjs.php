<?php

global $post;

// get the webinar
$webinar = WebinarSysteemWebinar::create_from_id($post->ID);

$polyfill_script = WebinarSysteemJS::get_polyfill_path();
$script = WebinarSysteemJS::get_js_path() . '?v=' . WebinarSysteemJS::get_version();
$style = WebinarSysteemJS::get_css_path() . '?v=' . WebinarSysteemJS::get_version();

$boot_data = [
    'locale' => get_locale(),
    'language' => 'en',
    'ajax' => admin_url('admin-ajax.php'),
    'security' => wp_create_nonce(WebinarSysteemJS::get_nonce_secret()),
    'base' => WebinarSysteemJS::get_asset_path(),
    'plugin' => WebinarSysteemJS::get_plugin_path(),
    'version' => WPWS_PLUGIN_VERSION,
    'isAdmin' => is_admin()
];

$is_team_member = current_user_can('manage_options');

$webinar_params = WebinarSysteemRegistrationWidget::get_webinar_info($webinar);
$params = $webinar->get_registration_page_params();
$webinar_extended = [
    'description' => $webinar->get_description()
];
// Global Script Tags
$global_header_script = WebinarSysteemSettings::get_global_script(
    WebinarSysteemSettings::GLOBAL_SCRIPT_REGISTRATION_PAGE,
    'headerScriptTag'
);
$global_body_script = WebinarSysteemSettings::get_global_script(
    WebinarSysteemSettings::GLOBAL_SCRIPT_REGISTRATION_PAGE,
    'bodyScriptTag'
);

$meta_title = $webinar->get_meta_title();
$meta_keywords = $webinar->get_meta_keywords();
$meta_description = $webinar->get_meta_description();

$open_graph_title = $webinar->get_open_graph_title();
$open_graph_description = $webinar->get_open_graph_description();
$open_graph_image_url = $webinar->get_open_graph_image_url();

?>
<!DOCTYPE html>
<html class="wpws">
    <head>   
        <title><?php echo esc_attr(strlen($meta_title) > 0 ? $meta_title : get_the_title()); ?></title>

        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta property="og:title" content="<?php esc_attr(the_title()); ?>">
        <meta property="og:url" content="<?php echo esc_url(get_permalink($post->ID)); ?>">

        <?php if (strlen($meta_keywords) > 0) { ?>
        <meta name="keywords" content="<?php echo esc_attr($meta_keywords) ?>" />
        <?php } ?>

        <?php if (strlen($meta_description) > 0) { ?>
        <meta name="description" content="<?php echo esc_attr($meta_description) ?>" />
        <?php } ?>

        <?php if (strlen($open_graph_title) > 0) { ?>
        <meta property="og:title" content="<?php echo esc_attr($open_graph_title) ?>" />
        <?php } ?>

        <?php if (strlen($open_graph_description) > 0) { ?>
        <meta property="og:description" content="<?php echo esc_attr($open_graph_description) ?>" />
        <?php } ?>

        <?php if (strlen($open_graph_image_url) > 0) { ?>
        <meta property="og:image" content="<?php echo esc_url($open_graph_image_url) ?>" />
        <?php } ?>

        <?php echo esc_html(WebinarSysteemHelperFunctions::get_favicon()) ?>

        <?php if (WebinarSysteemJS::get_css_path()) { 
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
            <link rel='stylesheet' href="<?php echo esc_url($style) ?>" type='text/css' media='all'/>
        <?php } ?>

        <?php echo esc_html($global_header_script); ?>

        <?php echo esc_html($webinar->get_registration_page_head_script_tag()) ?>

        <style>
            html, body {
                height: 100%;
            }
            body {
            <?php if (isset($params->contentBodyBackgroundColor) && strlen($params->contentBodyBackgroundColor) > 0) { ?>
                background-color: <?php echo esc_attr($params->contentBodyBackgroundColor) ?> !important;
            <?php } else { ?>
                background-color: #fff !important;
            <?php } ?>
            }
        </style>
    </head>

    <body>
        <div 
            id="wpws-register"
            data-params='<?php echo esc_attr(str_replace('\'', '&apos;', wp_json_encode($params))) ?>'
            data-webinar='<?php echo esc_attr(str_replace('\'', '&apos;', wp_json_encode($webinar_params))) ?>'
            data-webinar-extended='<?php echo esc_attr(str_replace('\'', '&apos;', wp_json_encode($webinar_extended))) ?>'
        ></div>
        <script>
            ___wpws = <?php echo wp_json_encode($boot_data) ?>;
        </script>
        <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
        <script src="<?php echo esc_url($polyfill_script) ?>"></script>
        <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
        <script src="<?php echo esc_attr($script) ?>"></script>

        <?php echo esc_html($global_body_script); ?>
        <?php echo esc_html($webinar->get_registration_page_body_script_tag()) ?>
    </body>
</html>
