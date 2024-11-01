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
$params = $webinar->get_confirmation_page_params();

$attendee = WebinarSysteemAttendees::get_attendee($post->ID);
$attend_time = $attendee != null
    ? WebinarSysteem::get_webinar_time($post->ID, $attendee)
    : time();

$webinar_url = $attendee != null
    ? $webinar->get_url_with_auth($attendee->email, $attendee->secretkey)
    : $webinar->get_url();

$webinar_extended = [
    'time' => $attend_time,
    'url' => $webinar_url
];
// Global Script Tags
$global_header_script = WebinarSysteemSettings::get_global_script(
    WebinarSysteemSettings::GLOBAL_SCRIPT_CONFIRMATION_PAGE,
    'headerScriptTag'
);
$global_body_script = WebinarSysteemSettings::get_global_script(
    WebinarSysteemSettings::GLOBAL_SCRIPT_CONFIRMATION_PAGE,
    'bodyScriptTag'
);

?>
<!DOCTYPE html>
<html class="wpws">
    <head>   
        <title><?php echo esc_html(get_the_title()); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta property="og:title" content="<?php the_title(); ?>">
        <meta property="og:url" content="<?php echo esc_url(get_permalink($post->ID)); ?>">
        <?php echo esc_html(WebinarSysteemHelperFunctions::get_favicon()) ?>

        <?php if (WebinarSysteemJS::get_css_path()) { 
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
            <link rel='stylesheet' href="<?php echo esc_url($style) ?>" type='text/css' media='all'/>
        <?php } ?>

        <?php echo esc_html($global_header_script); ?>

        <?php echo esc_html($webinar->get_confirmation_header_script_tag()) ?>

        <style>
            html, body {
                height: 100%;
            }
            body {
            <?php if (isset($params->backgroundColor) && strlen($params->backgroundColor) > 0) { ?>
                background-color: <?php echo esc_attr($params->backgroundColor) ?> !important;
            <?php } else { ?>
                background-color: #eeefee !important;
            <?php } ?>
            }
        </style>
    </head>

    <body>
        <div 
            id="wpws-confirmation"
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
        <script src="<?php echo esc_url($script) ?>"></script>
        <?php echo esc_html($global_body_script); ?>
        <?php echo esc_html($webinar->get_confirmation_body_script_tag()) ?>
    </body>
</html>
