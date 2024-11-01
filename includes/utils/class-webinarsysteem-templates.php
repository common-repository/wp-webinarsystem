<?php

class WebinarSysteemTemplates {
    /*
     * Allow users to override default theme files by placing a file of the same name in their
     * theme's subfolder, if the file exists this function will return that in favor of the default
     *
     * i.e. /wp-content/themes/twentynineteen/wpwebinarsystem/webinar-registration.php
     */

    public static function get_path($filename) {
        $theme_template_path = join('/', [
            get_template_directory(),
            basename(WPWS_PLUGIN_FOLDER),
            $filename,
        ]);

        if (file_exists($theme_template_path)) {
            return $theme_template_path;
        }

        return join('/', [
            WPWS_PLUGIN_FOLDER,
            'includes/templates',
            $filename
        ]);
    }
}
