/* globals wpwebinarsystem, jQuery, document */

var user_data = [];
var elem;

//------- Meta box tabs

jQuery(function () {
  jQuery("#tabs").tabs({heightStyle: "content"}).addClass("ui-tabs-vertical ui-helper-clearfix");
  jQuery("#tabs li").removeClass("ui-corner-top").addClass("ui-corner-left");
  jQuery("#tabs").tabs("option", "heightStyle", "content");
  jQuery('#webinarMetaBox .ui-tabs-nav').height(jQuery('#webinarMetaBox .inside').height());
});

jQuery(document).on('click', '#tabs ul li', function () {
  jQuery('#webinarMetaBox .ui-tabs-nav').height(jQuery('#webinarMetaBox .inside').height());
});

jQuery(document).on('click', '[name="livep_call_action_ctatype"]', function () {
  const selected = jQuery(this).val();
  if (selected == 'button') {
    jQuery('#livep_callto_action_button').slideDown();
    jQuery('#livep_callto_action_txtfied').hide();
  } else if (selected === 'txt_field') {
    jQuery('#livep_callto_action_button').hide();
    jQuery('#livep_callto_action_txtfied').slideDown();
  }
});

jQuery(document).on('click', '[name="livep_call_action"]', function () {
  var selected_ = jQuery(this).val();
  if (selected_ == 'aftertimer') {
    jQuery('#livep_call_action_atertime').slideDown();
    jQuery('#livep_call_action_manual').hide();
  } else if (selected_ == 'manual') {
    jQuery('#livep_call_action_atertime').hide();
    jQuery('#livep_call_action_manual').slideDown();
  }
});

/*
 * Toggle email field by user's preference to have an email on every attendee question.
 */
jQuery(function ($) {
  $('input[name="livep_askq_send_email_yn"]').on('switchChange.bootstrapSwitch', function (event, state) {
    jQuery('[name="livep_askq_send_email"]').parent()[(state ? 'remove' : 'add') + 'Class']("hidden");
  });

  $('input[name="replayp_askq_send_email_yn"]').on('switchChange.bootstrapSwitch', function (event, state) {
    jQuery('[name="replayp_askq_send_email"]').parent()[(state ? 'remove' : 'add') + 'Class']("hidden");
  });
});


// --------------------------- Media uploader

// Uploading files
var file_frame;
/*
jQuery('.wswebinar_uploader').live('click', function (event) {
  var resultId = jQuery(this).attr('resultId');
  var theButtonClicked = this;
  event.preventDefault();
  // If the media frame already exists, reopen it.
  if (file_frame) {
    file_frame.open();
    return false;
  }
  // Create the media frame.
  file_frame = wp.media.frames.file_frame = wp.media({
    title: jQuery(this).attr('uploader_title'),
    button: {
      text: jQuery(this).attr('uploader_button_text'),
    },
    multiple: false  // Set to true to allow multiple files to be selected
  });

  // When an image is selected, run a callback.
  file_frame.on('select', function () {
    // We set multiple to false so only get one image from the uploader
    attachment = file_frame.state().get('selection').first().toJSON();
    jQuery('#' + resultId).val(attachment.url);
    var isCheckType = jQuery(theButtonClicked).attr("checktype");
    //if (isCheckType == "yes")
    //runTypeSelectionWatch('#' + resultId);

    file_frame = null;
    // Do something with attachment.id and/or attachment.url here
  });
  // Finally, open the modal
  file_frame.open();
});
*/

function checkImageOrVideoType(theText) {
  if (theText.length < 1)
    return false;
  return(theText.match(/\.(jpeg|jpg|gif|png)$/) != null);
}


setTypeSelectionWatch('#regp_vidurl');
setTypeSelectionWatch('#tnxp_tnxmsgvid');
//setTypeSelectionWatch('#replayp_vidurl');


/*
 * 
 * Add the watch for "set the type of Image or Video fields."
 * 
 */
function setTypeSelectionWatch(tThis) {
  jQuery(document).on('focusin', jQuery(tThis), function () {
    var inContent = jQuery(tThis).val();
    jQuery(document).on('focusout', tThis, function (event) {
      var theTexts = jQuery(tThis).val();
      if (inContent == theTexts)
        return false;
      //runTypeSelectionWatch(tThis);
    });
  });
}

/*
 * 
 * Set the type of Image or Video fields.
 * 
 */

function runTypeSelectionWatch(tThis) {
  var tTarget = tThis + '_type';
  var theText = jQuery(tThis).val();
  var reslt = checkImageOrVideoType(theText);
  if (reslt)
    jQuery(tTarget).val('image');
  else
    jQuery(tTarget).val('video');

}

/*
 * 
 * Content Type select box functionality
 * 
 */

jQuery(document).on('change', '.lookoutImageButton', function () {
  var ButtnId = jQuery(this).attr('imageUploadButton');
  var ValueFieldId = jQuery(this).attr('valueField');
  if (ButtnId.length < 3)
    return false;
  var selected = jQuery(this).val();
  if (selected !== 'image') {
    jQuery('#' + ButtnId).hide();
  } else {
    jQuery('#' + ButtnId).show();
  }
  jQuery('#' + ValueFieldId).val('');
  jQuery('.' + ValueFieldId + '_desc').hide();
  jQuery('.' + ValueFieldId + '_for_' + selected).show();
});

/*
 * 
 * Download attendee list CSV for a give webinar id.
 * 
 */

jQuery(document).on('click', '.exportcsv', function () {
  var thId = jQuery(this).attr('postid');
  window.open("index.php?wswebinar_createcsv=wswebinars&postid=" + thId, '_blank');
});
jQuery(document).on('click', '.connect-aweber', function () {
  window.open("index.php?wswebinar_aweber_connect=wswebinars", '_blank');
});
jQuery(document).on('click', '.wswebinar-revoke-aweber', function () {
  jQuery.ajax({
    url: wpwebinarsystem.ajaxurl,
    type: "GET",
    data: {
      action: 'revoke-aweber-config',
    },
    success: function () {
      location.reload();
    },
    error: function (jqXHR, text, status) {
      //alert(JSON.stringify(jqXHR) + " Error");
    }});
});

jQuery(document).on('click', '.exportbcc', function () {
  var thId = jQuery(this).attr('postid');
  window.open("index.php?wswebinar_createbcc=wswebinars&postid=" + thId, '_blank');
});
jQuery(document).ready(function () {
  var settab = jQuery('.connect-aweber').attr('data-tab');
  if (settab == 'true') {
    jQuery("a[href='#tabs-3']").trigger('click');
    jQuery('.nav-tab').each(function () {
      jQuery(this).removeClass('nav-tab-active');
    });
    jQuery('.nav-tab-content').each(function () {
      jQuery(this).hide();
    });
    jQuery('#tabs-3').show();
    jQuery("a[href='#tabs-3']").addClass('nav-tab-active');
  }
});
jQuery(document).on('change', '.quickstatusupdater', function() {
  var webid = jQuery(this).attr('webinar');
  var stat = jQuery(this).val();
  var security = wpwebinarsystem.security;
  var datas = {
    action: 'quickchangestatus',
    webinar_id: webid,
    status: stat,
    security: security
  };
  jQuery('#waitingIcon_' + webid).show();
  jQuery.ajax({
    type: 'POST',
    data: datas,
    url: wpwebinarsystem.ajaxurl,
    dataType: 'json'
  }).done(function(data) {
    if (data.success) {
      jQuery('#waitingIcon_' + webid).hide();
      jQuery('#checkIcon_' + webid).fadeIn('slow');
      setTimeout(function() {
        jQuery('#checkIcon_' + webid).fadeOut('slow');
      }, 3000);
    }
  });
});

/*
 * 
 * Send Preview Mails - Webinar Settings
 * 
 */
jQuery(function () {
  var previewEmailisInvalid = 1;

  jQuery(".button[data-mail-type='_wswebinar_newreg']").click(function () {
    IsEmail(jQuery("input[type='email'][data-mail-type='_wswebinar_newreg']").val());
    sendPreviewEmailRequest(jQuery(this).data("mail-type"), jQuery("input[type='email'][data-mail-type='_wswebinar_newreg']").val(), this);
  });

  jQuery(".button[data-mail-type='_wswebinar_regconfirm']").click(function () {
    IsEmail(jQuery("input[type='email'][data-mail-type='_wswebinar_regconfirm']").val());
    sendPreviewEmailRequest(jQuery(this).data("mail-type"), jQuery("input[type='email'][data-mail-type='_wswebinar_regconfirm']").val(), this);
  });

  jQuery(".button[data-mail-type='_wswebinar_24hrb4']").click(function () {
    IsEmail(jQuery("input[type='email'][data-mail-type='_wswebinar_24hrb4']").val());
    sendPreviewEmailRequest(jQuery(this).data("mail-type"), jQuery("input[type='email'][data-mail-type='_wswebinar_24hrb4']").val(), this);
  });

  jQuery(".button[data-mail-type='_wswebinar_1hrb4']").click(function () {
    IsEmail(jQuery("input[type='email'][data-mail-type='_wswebinar_1hrb4']").val());
    sendPreviewEmailRequest(jQuery(this).data("mail-type"), jQuery("input[type='email'][data-mail-type='_wswebinar_1hrb4']").val(), this);
  });

  jQuery(".button[data-mail-type='_wswebinar_wbnreplay']").click(function () {
    IsEmail(jQuery("input[type='email'][data-mail-type='_wswebinar_wbnreplay']").val());
    sendPreviewEmailRequest(jQuery(this).data("mail-type"), jQuery("input[type='email'][data-mail-type='_wswebinar_wbnreplay']").val(), this);
  });

  jQuery(".button[data-mail-type='_wswebinar_wbnstarted']").click(function () {
    IsEmail(jQuery("input[type='email'][data-mail-type='_wswebinar_wbnstarted']").val());
    sendPreviewEmailRequest(jQuery(this).data("mail-type"), jQuery("input[type='email'][data-mail-type='_wswebinar_wbnstarted']").val(), this);
  });

  function sendPreviewEmailRequest(whatToRun, emailToSendPreviewEmail, buttonClicked) {
    if (previewEmailisInvalid == 0) {
      jQuery(buttonClicked).attr('disabled', 'disabled');
      jQuery.ajax({
        url: wpwebinarsystem.ajaxurl,
        data: {action: 'wpws_send_email_preview', run: whatToRun, email: emailToSendPreviewEmail},
        success: function (data) {
          animateSendPreviewEmailButton(buttonClicked);
        },
        error: function (a, b, c) {
          animateSendPreviewEmailButton(buttonClicked, c);
        }
      });
    } else {
      animateSendPreviewEmailButton(buttonClicked);
    }
  }
  function animateSendPreviewEmailButton(buttonToAnimate, status) {
    jQuery(buttonToAnimate).fadeOut(200).delay(300).fadeIn(300).delay(500).fadeOut(200).delay(200).fadeIn(200);
    setTimeout(
      function () {
        if (previewEmailisInvalid == 1) {
          jQuery(buttonToAnimate).val("Invalid Email");
        } else {
          if (status != undefined) {
            jQuery(buttonToAnimate).val("ERROR: " + status);
          } else {
            jQuery(buttonToAnimate).val("Preview Sent");
          }
        }
      }
      , 250);
    setTimeout(
      function () {
        jQuery(buttonToAnimate).val("Send Preview");
        jQuery(buttonToAnimate).removeAttr('disabled');
      }
      , 1500);
  }

  function IsEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    if (regex.test(email)) {
      previewEmailisInvalid = 0;
    } else {
      previewEmailisInvalid = 1;
    }
  }

  jQuery(document).on('keypress', '.preview-email-textbox', function (e) {
    var emailType = jQuery(this).data("mail-type");
    var email = jQuery(this).val();
    var button = jQuery(".button[data-mail-type='" + emailType + "']");
    if (e.which == 13) {
      e.preventDefault();
      sendPreviewEmailRequest(emailType, email, button);
    }
  });

  /*
   *
   * Mailing list provider and option relation
   *
   */

  jQuery(document).on('change', '#_wswebinar_mailinglist_provider_selector', function () {
    var selection = jQuery(this).val();
    jQuery('.mailing-provider-options').fadeOut();
    jQuery('.mailing-provider-' + selection).fadeIn();
  });

});
/*
 * Check API Drip API key
 */
jQuery(document).on('click', '#webinar_drip_check', function() {
  var APIKEY = jQuery('#drip_api_key').val();
  jQuery('#webinar_drip_check').attr('style', 'display: none;');
  jQuery('#webinar_drip_loader').attr('style', 'display: block;');
  jQuery.ajax({
    url: wpwebinarsystem.ajaxurl,
    type: "GET",
    data: {
      action: 'check-drip-api-key',
      key: APIKEY
    },
    success: function(returned) {
      jQuery('#webinar_drip_loader').fadeOut();
      var object = JSON.parse(returned);
      if (object['error']) {
        setTimeout(function () {
          jQuery('#webinar_drip_incorrect').fadeIn();
          jQuery('.drip_apichecker').show();
        }, 200);
      }
      else
      {
        setTimeout(function () {
          jQuery('#webinar_drip_correct').fadeIn();
        }, 200);
      }
    },
    error: function (exception) {
      alert("ex:"+exception);
    }
  });
});
jQuery(document).on('click', '#drip_api_key', function () {
  jQuery('#webinar_drip_invalid_api').html('');
  jQuery('#webinar_drip_check').attr('style', 'display: block;');
  jQuery('#webinar_drip_loader').attr('style', 'display: none;');
  jQuery('#webinar_drip_correct').hide();
  jQuery('#webinar_drip_incorrect').hide();
});
jQuery(document).ready(function($){
  jQuery('#_wswebinar_drip_accounts').on('change', function(){
    var acc = jQuery(this).val();
    jQuery('#webinar_drip_campaign_loader').attr('style', 'display: block;');
    if(acc) {
      jQuery.ajax({
        'type': 'GET',
        'url': wpwebinarsystem.ajaxurl,
        data: {
          action: 'get-drip-campaigns',
          account_id: acc
        },
        success: function(result){
          jQuery('#webinar_drip_campaign_loader').attr('style', 'display: none;');
          options = '';
          var data = JSON.parse(result);
          for(var i in data)
          {
            if(data[i].value == '' || data[i].label == ''){
              options += '<option value="" disabled="disabled" selected="selected">Select a Campaign</option>';
            }
            else
            {
              options += '<option value="'+ data[i].value +'">'+ data[i].label +'</option>';
            }
          }
          options += '<option value="no">Do not add subscriber to Campaign</option>';
          jQuery('#_wswebinar_drip_campaigns').html(options);
        },
        error: function(exception){
          //alert(exception);
        }
      });
    }

  });
});


/*
 * Check API Enomail API key
 */
jQuery(document).on('click', '#webinar_enormail_check', function () {
  var APIKEY = jQuery('#enormail_api_key').val();
  jQuery('#webinar_enormail_check').attr('style', 'display: none;');
  jQuery('#webinar_enormail_loader').attr('style', 'display: block;');
  jQuery.ajax({
    url: wpwebinarsystem.ajaxurl,
    type: "GET",
    data: {
      action: 'check-enomail-api-key',
      key: APIKEY
    },
    success: function (returned) {
      jQuery('#webinar_enormail_loader').fadeOut();
      var object = JSON.parse(returned);
      if (object['error']) {
        setTimeout(function () {
          jQuery('#webinar_enormail_error').fadeIn();
          jQuery('#webinar_enormail_user_name').html(object['content']);
        }, 200);

      } else {
        setTimeout(function () {
          jQuery('#webinar_enormail_correct').fadeIn();
          jQuery('#webinar_enormail_user_name').html(object['content']);
        }, 200);
      }
    },
    error: function (jqXHR, text, status) {
      //alert(JSON.stringify(jqXHR) + " Error");
    }});
});
jQuery(document).on('click', '#enormail_api_key', function () {
  jQuery('#webinar_enormail_user_name').html('');
  jQuery('#webinar_enormail_check').attr('style', 'display: block;');
  jQuery('#webinar_enormail_loader').attr('style', 'display: none;');
  jQuery('#webinar_enormail_correct').hide();
  jQuery('#webinar_enormail_error').hide();
});

/*
 * Generate system report.
 */
jQuery(document).on('click', '.webinar_debug_report', function () {
  var debug_classes = ["WordPress Environment", "Plugin Environment", "Server Environment", "Server Locale", "Active Plugins", "Theme"];
  var parse_report = "";
  for (var loopvar = 0; loopvar < debug_classes.length; loopvar++) {
    var cur_prop = debug_classes[loopvar];
    var count = true;

    jQuery("tr[data-info='" + cur_prop + "']").each(function () {

      if (count) {
        count = false;
        parse_report = parse_report + (loopvar == 0 ? "----" + cur_prop + "----\n" : "\n\n----" + cur_prop + "---- \n");
      }

      if (jQuery(this).attr('data-has-a') === 'true') {
        var data_value = jQuery(this).find('td').text();
      } else {
        var data_value = jQuery(this).find('td').html();
      }
      var data_head = jQuery(this).find('th').attr('data-value');

      parse_report = parse_report + data_head.trim() + " : " + data_value.trim() + "\n";

    });
  }

  jQuery(".webinar_systeem_sys_report_textarea").val(parse_report);
  jQuery(".webinar_systeem_sys_report_textarea").slideDown();
  jQuery(".webinar_systeem_sys_report_copy_btn").slideDown();
  jQuery(".webinar_debug_report").slideUp();
  jQuery(".webinar_systeem_sys_report_textarea").select();
});

jQuery(document).on('click', '.webinar_systeem_sys_report_copy_btn', function () {
  copy(jQuery(".webinar_systeem_sys_report_textarea").html());
  jQuery('.webinar_systeem_sys_report_copy_status').fadeIn();
  setTimeout(function () {
    jQuery('.webinar_systeem_sys_report_copy_status').fadeOut();
  }, 2000);
});

function copy(value) {
  var input = jQuery(".webinar_systeem_sys_report_textarea");
  input.value = value;
  input.focus();
  input.select();
  document.execCommand('Copy');
}


/*
 * Collect user styles for download.
 */
jQuery(document).on('click', '#webinar_imp_exp_action_export', function () {
  var name_array = [];
  var value_array = [];
  jQuery('[data-style-collect="true"]').each(function () {
    if (jQuery(this).is(':checkbox')) {
      name_array.push(jQuery(this).attr('name'));
      value_array.push(jQuery(this).is(':checked'));
    } else if (jQuery(this).is(':text')) {
      name_array.push(jQuery(this).attr('name'));
      value_array.push(jQuery(this).val());
    } else if (jQuery(this).prop('tagName').toLowerCase() == 'select') {
      name_array.push(jQuery(this).attr('name'));
      value_array.push(jQuery(this).val());
    } else if (jQuery(this).is(':radio')) {
      var elem_name = jQuery(this).attr('name');
      var elem_value = jQuery("input[name='" + elem_name + "']:checked").val();
      name_array.push(elem_name);
      value_array.push(elem_value);
    } else {
      name_array.push(jQuery(this).attr('name'));
      value_array.push(jQuery(this).val());
    }

  });

  var parse = JSON.stringify([name_array, value_array]);
  download(parse, 'webinar_styles.json', 'text/json');
});

function download(strData, strFileName, strMimeType) {
  var D = document,
    a = D.createElement("a");
  strMimeType = strMimeType || "application/octet-stream";


  if (navigator.msSaveBlob) { // IE10
    return navigator.msSaveBlob(new Blob([strData], {type: strMimeType}), strFileName);
  } /* end if(navigator.msSaveBlob) */


  if ('download' in a) { //html5 A[download]
    a.href = "data:" + strMimeType + "," + encodeURIComponent(strData);
    a.setAttribute("download", strFileName);
    a.innerHTML = "downloading...";
    D.body.appendChild(a);
    setTimeout(function () {
      a.click();
      D.body.removeChild(a);
    }, 66);
    return true;
  } /* end if('download' in a) */


  //do iframe dataURL download (old ch+FF):
  var f = D.createElement("iframe");
  D.body.appendChild(f);
  f.src = "data:" + strMimeType + "," + encodeURIComponent(strData);

  setTimeout(function () {
    D.body.removeChild(f);
  }, 333);
  return true;
}

jQuery(document).on('click', '#webinar_imp_exp_action_import', function () {
  jQuery('#webinar_imp_exp_action_import_hidden').trigger('click');
});

jQuery(document).on('change', '#webinar_imp_exp_action_import_hidden', function (event) {
  var uploadedFile = event.target.files[0];
  var file = jQuery(this).val();
  var ext = file.split('.').pop();
  if (ext == 'json') {
    if (uploadedFile) {
      jQuery('#webinar_imp_exp_action_import').fadeOut('slow', function () {
        jQuery('#webinar_import_loader').fadeIn();
      });
      var readFile = new FileReader();
      readFile.onload = function (e) {
        var contents = e.target.result;
        var json = JSON.parse(contents);
        executeFile(json);
      };
      readFile.readAsText(uploadedFile);
    }
  }
});


function executeFile(json) {
  var names = json[0];
  var values = json[1];
  var image_names = [];
  var image_values = [];
  if (!jQuery.isEmptyObject(names) && !jQuery.isEmptyObject(values)) {
    if (names.length === values.length) {
      for (var fetch = 0; fetch < names.length; fetch++) {
        var isInput = jQuery('input[name="' + names[fetch] + '"]').attr('type') == 'text';
        var isTextArea = jQuery('input[name="' + names[fetch] + '"]').is("textarea");
        var isRadio = jQuery('input[name="' + names[fetch] + '"]').is(':radio');
        var tagName = jQuery('[name="' + names[fetch] + '"]').prop('tagName');
        var isSelectBox = tagName + "".toLowerCase() == 'select';
        var isCheckbox = jQuery('input[name="' + names[fetch] + '"]').is(':checkbox');
        if (isCheckbox) {

        }
        if (isInput) {
          jQuery('input[name="' + names[fetch] + '"]').attr('value', values[fetch]);
          var extension = values[fetch].split('.').pop();
          console.log(extension);
          extension = extension.toLowerCase();
          if (extension == 'jpg' | extension == 'png') {
            image_names.push(names[fetch]);
            image_values.push(values[fetch]);
            jQuery('input[name="' + names[fetch] + '"]').attr('value', '');
          }
        } else if (isTextArea) {
          jQuery('[name="' + names[fetch] + '"]').html(values[fetch]);

        } else if (isSelectBox) {
          var element = jQuery('[name="' + names[fetch] + '"]').val(values[fetch]);
          jQuery(element.children('option')).each(function () {
            jQuery(this).prop('checked', false);
          });
        } else if (isRadio) {
          jQuery('[name="' + names[fetch] + '"]').each(function () {
            if (jQuery(this).val() == values[fetch]) {
              jQuery(this).prop('checked', true);
            }
          });
        } else if (isCheckbox) {
          var element = jQuery('[name="' + names[fetch] + '"]');
          element.prop('checked', values[fetch]);
        } else {
          jQuery('[name="' + names[fetch] + '"]').val(values[fetch]);
        }
      }
    }
  }
  var processing = false;
  var security = wpwebinarsystem.security;
  if (image_names.length > 0 && image_values.length > 0 && image_names.length == image_values.length) {
    processing = true;
    jQuery.ajax({
      url: wpwebinarsystem.ajaxurl,
      type: "POST",
      data: {
        action: 'sync-import-imgs',
        img_values: image_values,
        img_names: image_names,
        security: security
      },
      success: function (returned) {
        var json_returned = JSON.parse(returned);
        var loopman = 0;
        jQuery(json_returned['names']).each(function () {
          jQuery('input[name="' + json_returned['names'][loopman] + '"]').attr('value', json_returned['values'][loopman]);
          loopman++;
        });
        reloadPage();
      },
      error: function (jqXHR, text, status) {
        jQuery('#webinar_import_loader').fadeOut('slow', function () {
          jQuery('#webinar_imp_exp_action_import').fadeIn();
        });
        alert("Something went wrong when Importing your settings. Please try again!");
      }});
  }
  if (processing == false) {
    reloadPage();
  }
}

function reloadPage() {
  jQuery('#webinar_import_loader span').html('Applying Changes...');
  var saveas = jQuery('#webinar_imp_exp_action_import_hidden').attr('data-saveas');
  setTimeout(function () {
    if (saveas == 'publish') {
      jQuery('#publish').trigger('click'); // Submit Page.
    } else {
      jQuery('#save-post').trigger('click'); // Save as draft
    }
  }, 100);
}

/*
 *
 * Get response API checker
 *
 */

jQuery(document).on('click', '.getresponse-apichecker', function () {
  var done = false;
  jQuery(this).hide();
  jQuery('.webinar_getresponse_error').fadeOut();
  jQuery('.webinar_getresponse_loader').fadeIn();
  var key = jQuery('.getresponse-apikey').val();
  jQuery.ajax({
    url: wpwebinarsystem.ajaxurl,
    data: {action: "check-getresponse-apikey", getresponse_apikey: key},
    type: 'GET'
  }).done(function (data) {
    var object = JSON.parse(data);
    if (object['error'] == false) {
      done = true;
      jQuery('.webinar_getresponse_loader').fadeOut();
      jQuery('.webinar_getresponse_correct').fadeIn();
    } else {
      jQuery('.webinar_getresponse_loader').fadeOut();
      jQuery('.webinar_getresponse_error').fadeIn();
      jQuery('.getresponse-apichecker').show();
    }
  }).error(function (jqXHR, text, status) {
    //alert(jqXHR+" "+text+" "+status);
  });

  setTimeout(function () {
    if (done == false) {
      jQuery('.webinar_getresponse_loader').fadeOut();
      jQuery('.webinar_getresponse_error').fadeIn();
      jQuery('.getresponse-apichecker').show();
    }
  }, 6000);
});

/*
 *
 * ConvertKit API checker
 *
 */

jQuery(document).on('click', '.convertkit-apichecker', function () {
  var done = false;
  jQuery(this).hide();
  jQuery('.webinar_convertkit_error').fadeOut();
  jQuery('.webinar_convertkit_loader').fadeIn();
  var key = jQuery('.convertkit-apikey').val();

  jQuery.ajax({
    url: wpwebinarsystem.ajaxurl,
    data: {action: "wpws_check_convertkit_apikey", api_key: key},
    type: 'GET'
  }).done(function (data) {
    var object = JSON.parse(data);
    if (object['error'] == false) {
      done = true;
      jQuery('.webinar_convertkit_loader').fadeOut();
      jQuery('.webinar_convertkit_correct').fadeIn();
    } else {
      jQuery('.webinar_convertkit_loader').fadeOut();
      jQuery('.webinar_convertkit_error').fadeIn();
      jQuery('.convertkit-apichecker').show();
    }
  }).error(function (jqXHR, text, status) {
  });

  setTimeout(function () {
    if (done == false) {
      jQuery('.webinar_convertkit_loader').fadeOut();
      jQuery('.webinar_convertkit_error').fadeIn();
      jQuery('.convertkit-apichecker').show();
    }
  }, 6000);
});

/*
 * 
 * Options Page: Integrations
 * 
 */

jQuery(function ($) {

  $(document).on('click', '.webinar-mailing-list-api-check', function () {
    checkAPIdetailsMailingLists($(this));
  });

  //ActiveCampaign
  $('#activecampaign-api-key').bind("input", function () {
    //Toggle Active Campign API URL field.
    $('._wswebinar_activecampaign_setting_section:eq(1)')['slide' + ($(this).val() === "" ? 'Up' : 'Down')]();
    if ($(this).val() === "")
      $('#activecampaign-api-url').val("");
    //Update data attrs.
    $('.webinar-mailing-list-api-check[data-name="activecampaign"]').data({key: $(this).val()});
  });
  $('#activecampaign-api-url').bind("input", function () {
    //Update data attrs.
    $('.webinar-mailing-list-api-check[data-name="activecampaign"]').data({url: $(this).val()});
  });


  /**
   * AJAX request with API credentials to check if they're correct.
   * All `data-` attributes will be sent to corresponding method.
   * `data-action` is required.
   *
   * @param {object} buttonElement
   * @returns {boolean}
   */
  function checkAPIdetailsMailingLists(buttonElement) {
    var dataAttrs = buttonElement.data(),
      icoLoading = $('#webinar-mailing-list-' + dataAttrs.name + '-loading'),
      icoCorrect = $('#webinar-mailing-list-' + dataAttrs.name + '-correct'),
      icoIncorrect = $('#webinar-mailing-list-' + dataAttrs.name + '-incorrect'),
      statusScreen = $('#webinar-mailing-list-' + dataAttrs.name + '-status');

    $(buttonElement).hide();
    $('#webinar-mailing-list-' + dataAttrs.name + '-loading').fadeIn();
    $.ajax({
      url: wpwebinarsystem.ajaxurl,
      type: 'POST',
      data: {action: dataAttrs.action, data: dataAttrs},
      success: function (rawData) {
        var data = $.parseJSON(rawData);

        icoLoading.hide();
        //Show appropriate icon
        data.error ? icoIncorrect.fadeIn() : icoCorrect.fadeIn();
        //Show status in text
        statusScreen.text(data.content);
        reset();
      },
      error: function (jqXHR, textStatus, errorThrown) {
        icoLoading.hide();
        icoIncorrect.fadeIn();
        statusScreen.text(errorThrown);
        reset();
      }
    });
    function reset() {
      setTimeout(function () {
        statusScreen.text('');
        $('#webinar-mailing-list-' + dataAttrs.name + '-correct, #webinar-mailing-list-' + dataAttrs.name + '-incorrect').hide();
        $(buttonElement).fadeIn();
      }, 2000);
    }
  }
});

/* 
 *
 * Remove mutliple attendees 
 * 
 */

 jQuery(document).on('click', '.removeAttendees.button', function (e) {
  if (jQuery('input.select-attendees:checked').length == 0) {
    alert("Select at least one attendee");
    return false;
  }
  var attendeeIds = new Array();
  jQuery('input.select-attendees:checked').each(function (index, element) {
    jQuery(this).parents("tr").addClass('deleteSelected');
    attendeeIds.push(this.value);
  });

  if (!confirm("Are you sure to delete selected attendees?")) {
    jQuery('input.select-attendees:checked').each(function (index, element)
    {
      jQuery(this).parents("tr").removeClass('deleteSelected');
    });
    return false;

  } else {

    jQuery.ajax({
      'url': wpwebinarsystem.ajaxurl,
      'data': {'action': 'remove-attendee', 'attid': attendeeIds},
      'dataType': 'json',
      'type': 'POST'
    }).done(function (data) {
      if (!data.error) {
        jQuery('input.select-attendees:checked').each(function (index, element) {
          jQuery(this).parents("tr").fadeOut();
        });
      }
      jQuery('input.select-attendees:checked').each(function (index, element) {
        jQuery(this).parents("tr").removeClass('deleteSelected');
      });
    });
    e.preventDefault();
  }
});


/*
 * 
 * Switch Button 
 * 
 */
jQuery(document).on('ready', function () {
  var options = {
    size: "mini",
    onColor: "green"
  };
  jQuery("input[type=\"checkbox\"][data-switch=\"true\"]").bootstrapSwitch(options);
});

/* Show or Hide Unsunscribe page field when enabling/disabling Activate Profile link switch */
jQuery(document).on('switchChange.bootstrapSwitch', '.wswebinar_subscription', function (e, s) {
  var val = jQuery(this).prop('checked');
  if(val) {
    jQuery('#wswebinar_unsubscribe').show();
  } else {
    jQuery('#wswebinar_unsubscribe').hide();
  }
});
/*
 * Show or hide HOA/Youtube description when selecting webinar type in Live page and Replay page.
 */
jQuery(document).on('change', 'select[name="livep_vidurl_type"]', function () {

  var source = jQuery(this).val();
  if(source == 'youtube' || source == 'youtubelive' || source == 'hoa'){
    jQuery('#livep_yt_description').css('display','block');
  } else {
    jQuery('#livep_yt_description').css('display','none');
  }

});

jQuery(document).on('change', 'select[name="replayp_vidurl_type"]', function () {

  var source = jQuery(this).val();
  if(source == 'youtube' || source == 'youtubelive' || source == 'hoa'){
    jQuery('#replayp_yt_description').css('display','block');
  } else {
    jQuery('#replayp_yt_description').css('display','none');
  }

});


jQuery(function ($) {

  var isNotAControllablePlayer = function ($this) {
    return jQuery($this).val() === 'iframe' || jQuery($this).val() === 'image';
  };

  jQuery(document).on('change', 'select[name="livep_vidurl_type"],select[name="replayp_vidurl_type"]', function () {
    jQuery('.video-auto-play-yn')[isNotAControllablePlayer($(this)) ? 'slideUp' : 'slideDown']('fast');
  });

  /*
   * Toggle Big Play Switch for MediaElementJS
   */
  var isMediaElementJS = function ($this) {
    return isNotAControllablePlayer($this) || jQuery($this).val() === 'youtubelive' || jQuery($this).val() === 'vimeo';
  };
  jQuery(document).on('change', 'select[id$="vidurl_type"]', function () {
    jQuery('.bigplaybtn-yn')[isMediaElementJS($(this)) ? 'slideUp' : 'slideDown']('fast');
  });
});

/*
 * Show|Hide tab layout accordian
 */
jQuery(document).on('change', '.wsweb_listen_fortabs', function () {
  var que = jQuery('[name="livep_askq_yn"]').prop('checked');
  var cht = jQuery('[name="livep_show_chatbox"]').prop('checked');
  if (que | cht) {
    jQuery('.wsweb_livep_tablayout').fadeIn();
  } else {
    jQuery('.wsweb_livep_tablayout').fadeOut();
  }
});

jQuery(document).ready(function () {
  jQuery('.wsweb_listen_fortabs').trigger('change');
});

jQuery(document).on('switchChange.bootstrapSwitch', '.wsweb_listen_fortabs', function (e, s) {
  var checksum = new Array();
  jQuery('.wsweb_listen_fortabs').each(function () {
    checksum.push(jQuery(this).prop('checked'));
  });
  if (checksum[0] | checksum[1]) {
    jQuery('.wsweb_livep_tablayout').fadeIn();
  } else {
    jQuery('.wsweb_livep_tablayout').fadeOut();
  }
});

jQuery(document).on('switchChange.bootstrapSwitch', "[name='livep_show_actionbox']", function (e, s) {
  var livepage_action = jQuery(this).prop('checked');
  jQuery("[name='livep_show_actionbox']").bootstrapSwitch('state', livepage_action);
});
//
jQuery(document).on('switchChange.bootstrapSwitch', ".wsweb_listen_enability", function (event, checked) {
  if (!checked) {
    jQuery("#regp_position_chooser").show();
    jQuery('#show_content_settings').slideUp();
  } else {
    jQuery("#regp_position_chooser").hide();
    jQuery('#show_content_settings').slideDown();
  }
});

/*Show Fullscreen control switch if Controls switch is set to off state*/

jQuery(document).on('switchChange.bootstrapSwitch', ".wsweb_livep_controls_listen_enability", function (event, checked) {
  if (checked) {
    jQuery('#livep_show_fullscreen_control_setting').slideUp();
  } else {
    jQuery('#livep_show_fullscreen_control_setting').slideDown();
  }
});

jQuery(document).on('switchChange.bootstrapSwitch', ".wsweb_replayp_controls_listen_enability", function (event, checked) {
  if (checked) {
    jQuery('#replayp_show_fullscreen_control_setting').slideUp();
  } else {
    jQuery('#replayp_show_fullscreen_control_setting').slideDown();
  }
});



jQuery(document).on('click', '.ws-accordian-title', function () {
  var father_el = jQuery(this).closest('.ws-accordian').closest('.panelContent').closest('[role="tabpanel"]');
  setTimeout(function () {
    jQuery('a[href="#' + father_el.attr('id') + '"]').each(function () {
      jQuery(this).trigger('click');
    });
  }, 500);
});

jQuery(document).on('click', '#wswebinar_chatlog tr td a', function (event) {
  event.preventDefault();
  window.open(jQuery(this).attr('href'), '_blank');
});

jQuery(document).on('click', '.select_chats', function () {
  var mode = jQuery(this).attr('data-select');
  var checked = jQuery(this).prop('checked');
  if (mode === 'all') {
    jQuery('.select_chats').each(function () {
      jQuery(this).prop('checked', checked);
    });
  } else {
    var count = jQuery('.select_chats[data-select="one"]').size();
    var checked_count = jQuery('.select_chats[data-select="one"]:checked').size();
    var uncheked_count = count - checked_count;
    if (uncheked_count == 0) {
      jQuery('.select_chats').each(function () {
        jQuery(this).prop('checked', true);
      });
    }
    if (uncheked_count > 0) {
      jQuery('.select_chats[data-select="all"]').each(function () {
        jQuery(this).prop('checked', false);
      });
    }
  }

  jQuery('.select_chats[data-select="one"]').each(function () {
    var chat_id = jQuery(this).attr('data-chatid');
    if (jQuery(this).prop('checked')) {
      jQuery('tr[data-chatid="' + chat_id + '"]').addClass('selected_table_row');
    } else {
      jQuery('tr[data-chatid="' + chat_id + '"]').removeClass('selected_table_row');
    }
  });
});

jQuery(document).on('click', '.chatlog_dashboard', function (event) {
  event.preventDefault();
  var button_type = jQuery(this).attr('data-clear');
  var chat_ids = Array();
  var chat_count = jQuery('.select_chats[data-select="one"]').size();
  if (button_type == 'selected') {
    var selected_count = jQuery('.select_chats[data-select="one"]:checked').size();
    if (selected_count > 0) {
      if (confirm(CLEAR_SEL_CHAT_CONF)) {
        jQuery('.select_chats[data-select="one"]:checked').each(function () {
          chat_ids.push(jQuery(this).attr('data-chatid'));
        });
      }
    }
  } else if (button_type == 'all' && chat_count > 0) {
    if (confirm(CLEAR_ALL_CHAT_CONF)) {
      jQuery('.select_chats[data-select="one"]').each(function () {
        chat_ids.push(jQuery(this).attr('data-chatid'));
      });
    }
  }
  deleteChats(chat_ids);
  chat_ids = [];
});

function deleteChats(messages_array) {
  const webinarId = jQuery('[name="webinar_id"]').val();
  if (messages_array.length > 0) {
    jQuery('.chatlog_loader').css('display', 'inline');
    jQuery.ajax({
      url: wpwebinarsystem.ajaxurl,
      data: {
        action: 'delete-chats',
        messages: messages_array,
        webinar_id: webinarId,
        security: wpwebinarsystem.security,
      },
      dataType: 'json',
      type: 'POST'
    }).done(function (data) {
      for (var item = 0; item < messages_array.length; item++) {
        jQuery('tr[data-chatid="' + messages_array[item] + '"]').fadeOut();
        jQuery('tr[data-chatid="' + messages_array[item] + '"]').remove();
      }
      messages_array = Array();
      jQuery('.chatlog_loader').css('display', 'none');
    });
  }
  return true;
}
/*
 * 
 * Dissmis Post admin notice.
 * 
 */

jQuery(document).on('click', '.close_post_notification', function () {
  jQuery.ajax("index.php?webinar_postnotf_dismiss=1").done(function (data) {
    if (data)
      jQuery('.wswebinar_adnotice_post').fadeOut('slow');
  });
  event.preventDefault();
});

jQuery(document).on('change', '#_wswebinar_accesstab_parent', function () {
  var selected_val = jQuery(this).val();
  var view_divs = ['everyone', 'user_roles', 'member_levels', 'user_ids'];
  jQuery(view_divs).each(function (count_int) {
    jQuery("#" + view_divs[count_int]).slideUp();
  });
  jQuery("#" + selected_val).slideDown();

  if (selected_val == 'everyone') {
    jQuery('#redirect_action_accordian').slideUp();
  } else {
    jQuery('#redirect_action_accordian').slideDown();
  }
  setTimeout(function () {
    jQuery('#webinarMetaBox .ui-tabs-nav').height(jQuery('#webinarMetaBox .inside').height());
  }, 400);
});

jQuery(document).on('click', '.ws_acc_selrole', function () {
  var values = [];
  jQuery('.ws_acc_selrole').each(function () {
    if (jQuery(this).prop('checked')) {
      values.push(jQuery(this).val());
    }
  });
  jQuery('[name="selected_user_role"]').val(values);
});
/*
 * 
 * Add new Attendee window
 * 
 */
jQuery(document).on('click', '.addAttendee', function (e) {
  e.preventDefault();
  if (user_data.length > 0){
    jQuery('#ws_save_new_attendees').css('display','block');
    jQuery('#ws_save_new_attendee').css('display','none');
  } else {
    jQuery('#ws_save_new_attendees').css('display','none');
  }
  tb_show("Add new attendee", "#TB_inline?height=380&width=400&overflow=visible&inlineId=hiddenContent&type=extended", false);
  jQuery('[name="newatt_name"]').focus();
  jQuery('#select-webinar').trigger('change');
});

jQuery(document).on('click', '#ws_save_new_attendee', function (e) {
  e.preventDefault();
  jQuery("#ws_popup_error").hide();
  var name = jQuery('[name="newatt_name"]').val();
  var email = jQuery('[name="newatt_email"]').val();

  if (name.length > 2 && validateEmail(email)) {
    jQuery(this).closest('form').trigger('submit');
  } else {
    jQuery("#ws_popup_error").slideDown();
  }
});

function validateEmail(email) {
  var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(email);
}

jQuery(document).on('change', '#select-webinar', function () {
  var isRecurring = jQuery(this).find(":selected").attr('data-isrec');
  var webinar_id = jQuery(this).val();
  if (isRecurring == 'true') {
    var options = "<option disabled selected>Select...</option>";
    jQuery(WB_DATA).each(function (count) {
      var cur_row = WB_DATA[count];
      if (cur_row.webinar_id == webinar_id) {
        jQuery(cur_row.timeslots).each(function (c) {
          if (cur_row.timeslots[c].time != 0) {
            options = options + "<option>" + cur_row.timeslots[c].datetime + " (" + cur_row.timeslots[c].date + ")</option>";
          }
        });
      }
    });
    jQuery('#ws_newatpop_recurring_times').html(options);
    jQuery('#ws_newatpop_rec_div').slideDown();
  } else {
    jQuery('#ws_newatpop_rec_div').slideUp();
  }
});

jQuery(document).on('click', '#import_csv_attendees', function () {
  jQuery('#attendee_csv_file').trigger('click');
});

jQuery(document).on('change', '#attendee_csv_file', function (event) {
  jQuery('#single_attendee_data').slideUp();
  jQuery('#ws_save_new_attendee').css('display','none');
  jQuery('#ws_save_new_attendees').css('display','block');
  var uploadedFile = event.target.files[0];
  var file = jQuery(this).val();
  var elem = jQuery(this);
  var ext = file.split('.').pop();
  if (ext == 'csv') {
    if (uploadedFile) {
      var file_name_ar = file.split("\\");
      var file_name = file_name_ar[file_name_ar.length - 1];

      jQuery('#csv_file_show').html(file_name.substr(0, 15) + "...");
      var readFile = new FileReader();
      readFile.onload = function (e) {
        var contents = e.target.result;

        var file_cont_array = contents.split('\n');
        for (var index = 0; index < file_cont_array.length; index++) {
          if (index > 0 && index < file_cont_array.length - 1) {
            user_data.push(file_cont_array[index]);
          }
        }
      };
      readFile.readAsText(uploadedFile);
    }
  } else {
    jQuery('#csv_file_show').html("Invalid File.");
    jQuery('#single_attendee_data').slideDown();
    jQuery('#ws_save_new_attendee').css('display','block');
    jQuery('#ws_save_new_attendees').css('display','none');
  }
});

jQuery(document).on('click', '#ws_save_new_attendees', function (e) {
  e.preventDefault();
  var send_mails_state = jQuery("#new_at_sendconf").is(":checked");
  var rec_time = jQuery('#ws_newatpop_recurring_times').val();
  var webinar_id = jQuery('[name="webinar_id"]').val();
  jQuery('.new_at_loader').show();
  jQuery.ajax({
    url: ajaxurl,
    data: {action: 'new-attendee-csv', file_values: user_data, send_mails: send_mails_state, recurring_time: rec_time, webinar_id: webinar_id},
    dataType: 'json',
    type: 'POST'
  }).done(function (data) {
    location.reload();
  });
});


jQuery(document).ready(function () {
  jQuery(".chosen-select").chosen({
    width: "55%",
    enable_split_word_search: false,
  });
});

//replay page
jQuery(document).ready(function () {
  jQuery(document).on('click', '[name="replayp_call_action_ctatype"]', function () {
    var selected_elem = jQuery(this).val();
    if (selected_elem == 'button') {
      jQuery('#replayp_callto_action_button').slideDown();
      jQuery('#replayp_callto_action_txtfied').hide();
    } else if (selected_elem == 'txt_field') {
      jQuery('#replayp_callto_action_button').hide();
      jQuery('#replayp_callto_action_txtfied').slideDown();
    }
  });

  jQuery(document).on('click', '[name="replayp_call_action"]', function () {
    var selected_ = jQuery(this).val();
    if (selected_ == 'aftertimer') {
      jQuery('#replayp_call_action_atertime').slideDown();
      jQuery('#replayp_call_action_manual').hide();
    } else if (selected_ == 'manual') {
      jQuery('#replayp_call_action_atertime').hide();
      jQuery('#replayp_call_action_manual').slideDown();
    }
  });
});

//Metabox
jQuery(function ($) {
  $('[name="gener_air_type"]').click(function () {
    $('[status="cou"]').text($(this).attr('value') == 'rec' ? wpwsL10n.automated : wpwsL10n.countdown);
  });
});

// show/hide controls based on template type
jQuery(document).ready(function () {
  function updateControls(container, type) {
    switch (type) {
      case 'classic':
        container.find('.hide-studio-template').show();
        container.find('.hide-classic-template').hide();
        break;

      case 'studio':
        container.find('.hide-classic-template').show();
        container.find('.hide-studio-template').hide();
        break;

      default:
        break;
    }
  }

  // show/hide replay page
  jQuery('#replayp_page_template').on('change', function () {
    updateControls(
      jQuery('.replayp_meta'),
      jQuery('#replayp_page_template').val());
  });

  // show/hide live page
  jQuery('#livep_page_template').on('change', function () {
    updateControls(
      jQuery('.livep_meta'),
      jQuery('#livep_page_template').val());
  });

  updateControls(
    jQuery('.livep_meta'),
    jQuery('#livep_page_template').val());

  updateControls(
    jQuery('.replayp_meta'),
    jQuery('#replayp_page_template').val());
});
