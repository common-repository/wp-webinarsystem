<?php

class WebinarSysteemUtils {
    public static function show_admin_pointer(
        $selector,
        $title,
        $content_ = '',
        $primary_button = false,
        $primary_action = '',
        $secondary_button = false,
        $secondary_action = '',
        $options = array()) {

        if (!current_user_can('administrator') || (get_bloginfo( 'version' ) < '3.3')) {
            return;
        }

        $content  = '';
        $content .= '<h3>' . $title . '</h3>';
        $content .= '<p>' . $content_ . '</p>';

        ?>
        <script type="text/javascript">
          //<![CDATA[
          jQuery(function($) {
            var wpwsPointer = $('<?php echo esc_js($selector); ?>' ).pointer({
              'content': <?php echo wp_json_encode( $content ); ?>,
              'position': { 'edge': '<?php echo esc_attr(isset( $options['edge'] ) ? $options['edge'] : 'top'); ?>',
                'align': '<?php echo esc_attr(isset($options['align']) ? $options['align'] : 'center'); ?>',
              },
              'buttons': function(e, t) {
                  <?php if (!$secondary_button): ?>
                return $('<a id="wpws-pointer-b1" class="button button-primary">' + '<?php echo esc_attr($primary_button); ?>' + '</a>');
                  <?php else: ?>
                return $('<a id="wpws-pointer-b2" class="button" style="margin-right: 15px;">' + '<?php echo esc_attr($secondary_button); ?>' + '</a>');
                  <?php endif; ?>
              }
            }).pointer('open');

              <?php if ($secondary_button): ?>

            $('#wpws-pointer-b2').before('<a id="wpws-pointer-b1" class="button button-primary">' + '<?php echo esc_attr($primary_button); ?>' + '</a>');
            $('#wpws-pointer-b2').click(function(e) {
              e.preventDefault();
                <?php if ( $secondary_action ): ?>
                <?php 
                  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                  echo $secondary_action; ?>
                <?php endif; ?>
              wpwsPointer.pointer( 'close' );
            });

              <?php endif; ?>

            $('#wpws-pointer-b1').click(function(e) {
              e.preventDefault();
                <?php if ( $primary_action ): ?>
                <?php
                  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                  echo $primary_action; ?>
                <?php endif; ?>
              wpwsPointer.pointer( 'close' );
            });
          });
          //]]>
        </script>
        <?php
    }

    public static function get_cache_path($name) {
        $cache_directory = plugin_dir_path(dirname(__FILE__)).'cache/';
        $cache_directory = rtrim(apply_filters('wpws_get_cache_path', $cache_directory), '/').'/';

        if (!@is_dir($cache_directory)) {
            if (!@wp_mkdir_p($cache_directory)) {
                $error = error_get_last();
                $message = $error['message'];
                WebinarSysteemLog::log("Failed to create cache folder at $cache_directory: $message");
            }
        }

        return $cache_directory.$name.'.json';;
    }

    public static function get_cache_url($name) {
        // get the cache key for this webinar
        $cache_url = plugin_dir_url(dirname(__FILE__)).'cache/';
        $cache_url = rtrim(apply_filters('wpws_get_cache_url', $cache_url), '/').'/';

        // generate the filename
        return $cache_url.$name.'.json';
    }

    public static function write_cache($filename, $contents) {
      // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $output_file = fopen($filename, 'w');

        if ($output_file) {
          // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
            fwrite($output_file, wp_json_encode($contents));
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            fclose($output_file);
        }
    }
}
