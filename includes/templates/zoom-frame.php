<?php

$zoom_js = WebinarSysteemJS::get_plugin_path() . 'includes/js/zoom/zoom.js?v='.WPWS_ZOOM_SDK_VERSION;
$bootstrap_css_url = WebinarSysteemJS::get_plugin_path() . 'includes/js/zoom/bootstrap.css';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if (!isset($_REQUEST['params'])) {
  die('Invalid params');
}

// Make a call to live.getwebinarpress.com to get the signature
// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$signature = WebinarSysteemMediaServer::get_zoom_signature($_REQUEST['params']);

if ($signature == null) {
  die('Failed to load Zoom signature, please contact support');
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#000000">
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
    <link type="text/css" rel="stylesheet" href="<?php echo esc_url($bootstrap_css_url) ?>" />
    <title><?php echo esc_attr(get_the_title()); ?></title>
    <script
        src="https://code.jquery.com/jquery-3.4.1.min.js"
        integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
        crossorigin="anonymous">
    </script>
  </head>
  <body>
    <noscript>
      You need to enable JavaScript to run this app.
    </noscript>

    <input type="hidden" id="signature" value="<?php echo esc_attr($signature->signature); ?>">
    <input type="hidden" id="clientId" value="<?php echo esc_attr($signature->clientId); ?>">
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
    <script src="<?php echo esc_url($zoom_js); ?>"></script>
  </body>
</html>