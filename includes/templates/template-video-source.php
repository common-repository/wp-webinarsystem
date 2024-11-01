<?php

class WebinarSysteemVideoSources {

    public static function getSourceCode($type, $url, $controls, $autoplay, $hideBigPlayButton = false, $fullscreen = true) {
        $iosdevice = false;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if(strstr($_SERVER['HTTP_USER_AGENT'],'iPhone') || strstr($_SERVER['HTTP_USER_AGENT'],'iPad')){
            $iosdevice = true;
        }
    
        if ($iosdevice) {
            ?>
            <label class="enable-sound"><?php esc_html_e('Enable Sound', '_wswebinar') ?></label>
            <input type="hidden" name="store-status" value="yes">
            <input class="rv_listen_enability" type="checkbox" name="wsweb_enable_sound" id="wsweb_enable_sound" data-on-color="webinar-bswitchon" value="no" data-on-text="On" data-off-text="off"> 
            <video id="wpws-video-player-mep"  playsinline autoplay muted>
                <source type="video/<?php echo esc_attr($type) ?>" src="<?php echo $type == 'youtube' ? 'https://www.youtube.com/watch?v=' . esc_url($url).'&rel=0&showinfo=0' : esc_url(urldecode($url)) ?>">
            </video>
            <?php } else { ?>
            <video id="wpws-video-player-mep">
                <source type="video/<?php echo esc_attr($type) ?>" src="<?php echo $type == 'youtube' ? 'https://www.youtube.com/watch?v=' . esc_url($url).'&rel=0&showinfo=0' : esc_url(urldecode($url)) ?>">
            </video>
            <?php } ?>
            <script>
                jQuery('[name="wsweb_enable_sound"]').bootstrapSwitch();
                jQuery(function ($) {
                $('#wpws-video-player-mep').mediaelementplayer({
                    pluginPath: '<?php echo esc_url(site_url()); ?>/wp-includes/js/mediaelement/',
                    classPrefix: 'mejs-',
                    isClickable: false,
                    enableAutosize: true,
                    stretching: 'responsive',
                    enablePseudoStreaming: true,
                    loop: false,
                    autoRewind: false,
                    enableKeyboard: false,
            <?php if ($controls == 0 && $fullscreen == 1 && !is_user_logged_in()) { ?>
                    features: ['fullscreen'],
            <?php } ?>
            <?php if ($controls == 0 && is_user_logged_in()) { ?>
                    features: ['playpause', 'volume'],
            <?php } ?>
                success: function (mediaElement, domObject) {
            <?php if ($autoplay == 1) { ?>
                    mediaElement.play();
            <?php } ?>
                wswebinarsysteemMJP = mediaElement;
                    mediaElement.addEventListener('playing', function(e) {
                    $('#livep-play-button').removeClass('wbnicon-play').addClass('wbnicon-pause');
                    }, false);
                    mediaElement.addEventListener('pause', function(e) {
                    $('#livep-play-button').removeClass('wbnicon-pause').addClass('wbnicon-play');
                    }, false);
                },
            });
        });
        </script>

        <script>
        jQuery(document).on('switchChange.bootstrapSwitch',".rv_listen_enability", function (event, checked) {
            if (checked) {
                wswebinarsysteemMJP.setMuted(false);
            } else {
                wswebinarsysteemMJP.setMuted(true);
            }
        });
        </script>

        <?php if ($controls == 0 && !is_user_logged_in() && $fullscreen == 0) { ?>
            <style>
                .mejs-container .mejs-controls{ 
                    visibility:hidden !important; 
                }
            </style>
        <?php } ?>

        <?php if ($hideBigPlayButton) { ?>
            <style>
                .mejs-overlay-play{ 
                    visibility:hidden !important; 
                }
            </style>
        <?php } 
    }
}