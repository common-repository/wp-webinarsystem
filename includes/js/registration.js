jQuery(document).ready(function () {
  jQuery('.login_error').hide();
  jQuery('form[name=wpws_webinar_login]').on('submit', function() {
    var form = jQuery(this);
    form.find('button').prop('disabled', true);

    var email = form.find('input[name=inputemail]').val();
    var webinarId = form.find('input[name=webinar_id]').val();

    if (!email) {
      return false;
    }

    jQuery
      .ajax({
        'type': 'POST',
        'url' : wpws.ajaxUrl,
        data: {
          action: 'wpws_login_attendee',
          webinar_id: webinarId,
          email: email,
          security: wpws.security
        }
      })
      .done(function (res) {
        window.location.href = res.data.url;
      })
      .error(function (err) {
        form.find('.login_error').show();
        form.find('button').prop('disabled', false);
      });

    return false;
  });

  jQuery('form[name=wpws_webinar_register]').on('submit', function() {
    var form = jQuery(this);
    form.find('button').prop('disabled', true);

    var session = jQuery('select[name=session_datetime]').val();
    var name = form.find('input[name=inputname]').val();
    var email = form.find('input[name=inputemail]').val();
    var webinarId = form.find('input[name=webinar_id]').val();
    var redirect = form.find('input[name=redirect]').val();

    if (!name || !email) {
      return false;
    }

    var data = {
      action: 'wpws_register_attendee',
      webinar_id: webinarId,
      name: name,
      email: email,
      security: wpws.security,
      session_datetime: session
    };

    // get custom fields
    form.find('[name^=ws-]').each(function () {
      data[jQuery(this).attr('id')] = jQuery(this).val();
    });
  
    jQuery
      .ajax({
        'type': 'POST',
        'url' : wpws.ajaxUrl,
        data: data
      })
      .done(function (res) {
        window.location.href = redirect || res.data.url;
      })
      .error(function () {
        form.find('button').prop('disabled', false);
      });
  
    return false;
  });
});
