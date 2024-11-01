/* global tinymce, wpwebinarsystem_shortcode_data */

(function() {
  if (typeof __wpws_registration_widgets != 'undefined') {
    tinymce.create('tinymce.plugins.wpwebinarsystem', {
      init: function(ed, url) {
        ed.addButton('login_register_shortcodes', {
          type: 'menubutton',
          menu: __wpws_registration_widgets,
          image: url + '/../images/webinarv2.ico'
        });
      }
    });
    tinymce.PluginManager.add("wpwebinarsystem", tinymce.plugins.wpwebinarsystem);
  }
})();