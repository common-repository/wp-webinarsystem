<?php

global $post;

// set the attendee as attended and update the last seen time
$attendee = WebinarSysteemAttendees::get_attendee($post->ID);

// get the webinar
$webinar = WebinarSysteemWebinar::create_from_id($post->ID);

// if we don't have a valid attendee redirect to the registration page..
if ($attendee == null) {
    wp_redirect($webinar->get_url());
    die();
}


$attendee_name = empty($attendee)
    ? ''
    : $attendee->name;

$attendee_name = explode(' ', $attendee_name);

$ajax_url = admin_url('admin-ajax.php');
$cache_url = WebinarSysteemWebinarCache::get_cache_url($post->ID, 2);
$reduce_server_load = false;

$webinar_start_time = WebinarSysteem::get_webinar_time($post->ID, $attendee);
$server_time_with_timezone = strtotime(WebinarSysteem::getTimezoneTime($post->ID));
$webinar_time_in_seconds = $server_time_with_timezone - $webinar_start_time;

// is this an automated replay?
$now = $webinar->get_now_in_timezone();
$timezone_offset = $webinar->get_timezone_offset();

if ($now > $webinar_start_time + $webinar->get_duration() && $webinar->get_automated_replay_enabled()) {
    $webinar_start_time = $now;
    $webinar_time_in_seconds = 0;

    // If the one-time date/time is before the last summer time change the offset
    // can be incorrect so we should use the curren time for the offset
    $timezone_offset = $webinar->get_timezone_offset_for_date($now);
}

// Manual replies should start from now
if ($webinar->get_status() === 'rep') {
    $webinar_start_time = $now;
    $webinar_time_in_seconds = 0;

    // If the one-time date/time is before the last summer time change the offset
    // can be incorrect so we should use the curren time for the offset
    $timezone_offset = $webinar->get_timezone_offset_for_date($now);
}

// update the last active time for this webinar
$webinar->update_last_active_time();

// re-write the cache
WebinarSysteemWebinarCache::write($post->ID);

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

// Global Script Tags
$global_countdown_script = WebinarSysteemSettings::get_global_script(
    WebinarSysteemSettings::GLOBAL_SCRIPT_COUNTDOWN_PAGE,
    'bodyScriptTag'
);
$global_webinar_script = WebinarSysteemSettings::get_global_script(
    WebinarSysteemSettings::GLOBAL_SCRIPT_WEBINAR_PAGE,
    'bodyScriptTag'
);
$custom_css = $webinar->get_params();
$custom_css = $custom_css['live']['custom_css'];

$params = [
    'cache_url' => $cache_url,
    'secure_room_name' => $webinar->get_secure_room_name(),
    'secure_room_key' => $webinar->get_secure_room_key(),
    'reduce_server_load' => $reduce_server_load,
    'webinar_time_in_seconds' => $webinar_time_in_seconds,
    'webinar_start_time' => $webinar_start_time,
    'duration' => $webinar->get_duration(),
    'timezone_offset' => $timezone_offset * 60,
    'attendee' => [
        'id' => (int) $attendee->id,
        'name' => $attendee->name,
        'email' => $attendee->email,
        'is_team_member' => $is_team_member
    ],
    'translations' => WebinarSysteemSettings::instance()->get_translations(),
    'scripts' => [
        'countdown' => $webinar->get_countdown_body_script_tag(),
        'webinar' => $webinar->get_live_page_body_script_tag()
    ],
    'global_scripts' => [
        'countdown' => $global_countdown_script,
        'webinar' => $global_webinar_script
    ],
    'polls' => WebinarSysteemPolls::list(),
    'zoomsdk' => base64_encode(get_option('_wswebinar_client_id_zoomsdk').':'.get_option('_wswebinar_client_secret_zoomsdk')),
];

?>
<!DOCTYPE html>
<html class="wpws">
    <head>
        <title><?php echo esc_attr(get_the_title()); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta property="og:title" content="<?php esc_attr(the_title()); ?>">
        <meta property="og:url" content="<?php echo esc_attr(get_permalink($post->ID)); ?>">

        <?php echo esc_html(WebinarSysteemHelperFunctions::get_favicon()) ?>
        <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500">
        <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <style>
            html, body {
                height: 100%;
            }
        </style>
        <?php if (WebinarSysteemJS::get_css_path()) { 
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
            <link rel='stylesheet' href="<?php echo esc_url($style) ?>" type='text/css' media='all'/>
        <?php } ?>
        <?php if ($webinar->get_live_media_type() === 'twitch') { 
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
            <script src="https://player.twitch.tv/js/embed/v1.js"></script>
        <?php } ?>
        <?php if ($webinar->get_live_media_type() === 'jitsi') { 
            // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
            <script src='https://meet.jit.si/external_api.js'></script>
        <?php } ?>
        <style type="text/css">
            <?php echo esc_html($custom_css);?>
        </style>
    </head>

    <body>
        <div
            id="wpws-live"
            data-params='<?php echo esc_attr(str_replace('\'', '&apos;', wp_json_encode($params))) ?>'
        ></div>
        <script>
            ___wpws = <?php echo wp_json_encode($boot_data) ?>;
        </script>
        <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
        <script src="<?php echo esc_url($polyfill_script) ?>"></script>
        <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
        <script src="<?php echo esc_url($script) ?>"></script>

        <!-- placeholder for the body script tag -->
        <div id="body_script"></div>
    </body>
</html>
