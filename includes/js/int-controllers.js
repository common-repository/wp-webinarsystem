/* global wswebinarsysteemMJP */

isProcessingRequest = false;
disableScrolling = false;
pollingCount = 0;
userRegistrationCode = 0;
var lastSeenUpdatedAt = Date.now();
var lastSeenUpdateInterval = 0;

function getCookie(name) {
  var value = "; " + document.cookie;
  var parts = value.split("; " + name + "=");
  if (parts.length == 2) {
      return parts
        .pop()
        .split(";")
        .shift();
  }
  return null;
}

function getUserRegistrationCode() {
  return getCookie('_wswebinar_regrandom_key');
}

function startUpdateLastSeenTimer() {
  setTimeout(updateLastSeen, lastSeenUpdateInterval);
}

function isOnLivePage() {
  if (typeof theWebinarId === 'undefined' || !theWebinarId) {
    return false;
  }

  if (typeof cacheUrl === 'undefined') {
    return false;
  }

  if (typeof theWebinarstatus === 'undefined') {
    return false;
  }

  return true;
}

function updateLastSeen() {
  if (!isOnLivePage()) {
    return;
  }

  var secondsSinceLastSeenUpdate = lastSeenUpdatedAt
    ? Math.ceil((Date.now() - lastSeenUpdatedAt) / 1000)
    : 0;

  jQuery.ajax({
    url: wpwebinarsystem.ajaxurl,
    data: {
      action: 'wpws-update-last-seen',
      webinar_id: theWebinarId,
      webinar_st: theWebinarstatus,
      page_state: pageCategory,
      random_key: getUserRegistrationCode(),
      seconds_since_last_update: secondsSinceLastSeenUpdate,
      security: wpwebinarsystem.security
    },
    dataType: 'json',
    type: 'POST',
    success: function (res) {
      // call every 5 minutes
      startUpdateLastSeenTimer();

      lastSeenUpdatedAt = Date.now();

      if (!res.data.has_valid_session) {
        var url = window.location.href;
        location.href = url + "?logout=true";
      }
    },
    error: function (jqXHR, textStatus, errorThrown) {
        // Errors handled.
    }
  });
}

function fetchLiveData() {
  if (!isOnLivePage()) {
    return;
  }

  jQuery.ajax({
    url: cacheUrl + '?r=' + Date.now(),
    dataType: 'json',
    type: 'GET',
    success: function (data) {
      setTimeout(fetchLiveData, 5000);

      if (!isProcessingRequest) {
        populateAttendeeNameList(
          data.online_attendees.attendees,
          data.online_attendees.raisehandset,
          data.online_attendees.count);

        incentiveStatusChange(data.incentive_status.isShow);
        setCTAStatus(data.cta_status, data.cta_show_after);
        setHostAndDescBox(data.hostdesc_status);
        setActionBoxStatus(data.actionbox_status);
        setColumnSizes();
      }

      showChats(data.chats);

      pollingCount++;
    },
    error: function (jqXHR, textStatus, errorThrown) {
        // MDDO always reset unless its a 404 error?
      setTimeout(fetchLiveData, 5000);
    }
  });
}

var COUNT = 0;
function setColumnSizes() {
  //Check if left boxes are showing.
  var showing_lbox = false;
  jQuery('.left-box').each(function () {
    var visibility = jQuery(this).is(':visible');
    if (visibility) {
      showing_lbox = true;
    }
  });

  var short_col_clases = "col-lg-7 col-sm-6 col-xs-12 box-column";
  var long_col_classes = "col-lg-12 col-sm-12 col-xs-12 box-column";
  var cur_col_elem = jQuery('.box-column');

  if (showing_lbox) {
    cur_col_elem.removeClass();
    cur_col_elem.addClass(short_col_clases);
  } else {
    cur_col_elem.removeClass();
    cur_col_elem.addClass(long_col_classes);
  }
}

jQuery(document).on('click', '#gift_icon', function (event) {
  isProcessingRequest = true;
  event.preventDefault();
  startAnimation(jQuery(this).attr('id'));
  updateIncentive();
});

function setActionBoxStatus(isShow) {
  if (isShow) {
    jQuery('.raise-hand-box').show();
  } else {
    jQuery('.raise-hand-box').hide();
  }
}

function setHostAndDescBox(isShow) {
  if (isShow) {
    jQuery('#host_box').addClass('show');
    jQuery('#description_box').addClass('show');
    jQuery('#cuspage_host_box').show();
    jQuery('#show_multi_boxes').addClass('message-center-newmsg');
  } else {
    jQuery('#host_box').removeClass('show');
    jQuery('#description_box').removeClass('show');
    jQuery('#host_box').hide();
    jQuery('#description_box').hide();
    jQuery('#cuspage_host_box').hide();
    jQuery('#show_multi_boxes').removeClass('message-center-newmsg');
  }
}

function setCTAStatus(ctaStatus, showAfter) {

  // check show after minutes
  var ctaTriggeredAfterMinutes = false;
  if (showAfter && webinarStartTime) {
    var minutesPassed = (Date.now() - webinarStartTime.getTime()) / 60000;
    ctaTriggeredAfterMinutes = minutesPassed >= showAfter;
  }

  if (ctaStatus || ctaTriggeredAfterMinutes) {
    jQuery('.cta-view').addClass('show');
    jQuery('#show_cta_action').addClass('message-center-newmsg');
    if (jQuery('.cta-view').hasClass('show')) {
      jQuery('.cta-view').fadeIn();
    }
  } else {
    jQuery('.cta-view').removeClass('show');
    jQuery('#show_cta_action').removeClass('message-center-newmsg');
    if (!jQuery('.cta-view').hasClass('show')) {
      jQuery('.cta-view').fadeOut();
    }
  }
}

function incentiveStatusChange(isShow) {
  if (isShow === true) {
    jQuery('#show_incentive').show();
    jQuery('#gift_icon').css('color', '#ff002c');
    jQuery('#data_show_incentive').val('');
  } else {
    jQuery('#show_incentive').hide();
    jQuery('#gift_icon').css('color', ' #4c4c4c');
    jQuery('#data_show_incentive').val('yes');
  }
}

function updateIncentive() {
  setColumnSizes();
  jQuery.ajax({
    type: 'POST',
    url: wpwebinarsystem.ajaxurl,
    data: {
      action: 'update-incentive',
      webinar_id: theWebinarId,
      status: theWebinarstatus,
      security: wpwebinarsystem.security
    },
    success: function (data, textStatus, jqXHR) {
        stopAnimation();
        var data_show_incentive = jQuery('#data_show_incentive').val();
        incentiveStatusChange(data_show_incentive == 'yes');
        isProcessingRequest = false;
    }
  });
}

function populateAttendeeNameList(data, handRaises, count) {
  jQuery('#webinar-live-viewers').html(count);

  //data-attendee
  var list_li = '';
  var raisedHandsCount = 0;
  for (x = 0; x < data.length; x++) {
    var raisedHand = data[x].hand_raised;
    var handIcon = (raisedHand ? "<i class='fa fa-hand-paper-o pull-right hand-raised'></i>" : "<i class='fa fa-hand-paper-o pull-right '></i>");
    raisedHandsCount = (raisedHand ? ++raisedHandsCount : raisedHandsCount);

    var curAttendee = jQuery('.raise-hand-lg').attr('data-attendee');
    if (curAttendee == data[x].id) {
      if (raisedHand) {
        jQuery('.raise-hand-lg').addClass('hand-raised');
      } else {
        jQuery('.raise-hand-lg').removeClass('hand-raised');
      }
    }

    var name = data[x].name;
    name = (name.length > 16 ? name = name.substring(0, 14) + "..." : name);

    list_li += '<li><a href="#">' + name + '</a>' + handIcon + '</li>';
    jQuery('#attendee-online-list').html(list_li);
  }

  if (raisedHandsCount > 0) {
    jQuery('#adminbar-handraised').addClass('hand-raised-admin');
  } else {
    jQuery('#adminbar-handraised').removeClass('hand-raised-admin');
  }
}
/*
 * Send chat
 */
var showTimestamps = false;
var isUserAdmin = false;
var attendeeId = false;
var QUESTIONS_ARRAY = new Array();
var OLD_QUESTION_SIZE = 0;

jQuery(document).on('keypress', '[name="webinar-chat-content"]', function (e) {
  if (e.which == 13) {
    jQuery('.webinar-chat-push').trigger('click');
  }
});

jQuery(document).on('click', '.webinar-chat-push', function () {
  var webinar_id = jQuery(this).attr('data-webinarid');
  var attendee_name = jQuery(this).attr('data-attendeename');
  var attendee_id = jQuery(this).attr('data-attendeeid');
  var message = jQuery('[name="webinar-chat-content"]').val();
  message = message.replace(/<\/?[^>]+(>|$)/g, "");
  var admin = jQuery(this).attr('data-isadmin');
  var pvt_chat = (jQuery('[name="wsweb_private_chat"]').prop('checked') == true ? 'true' : 'false');
  jQuery('[name="webinar-chat-content"]').val('');

  if (message.length > 1) {
    jQuery('[name="webinar-chat-content"]').prop('disabled', true);
    var dataSet = {
      action: 'wpws-send-chat',
      webinar_id: webinar_id,
      attendee_id: attendee_id,
      message: message,
      is_admin: admin,
      is_private: pvt_chat,
      security: wpwebinarsystem.security
    };
    jQuery.ajax({
      type: 'POST',
      data: dataSet,
      url: wpwebinarsystem.ajaxurl,
      dataType: 'json',
      success: function (data) {
        var timestamp = data.timestamp;
        jQuery('[name="webinar-chat-content"]').prop('disabled', false);
        jQuery('[name="webinar-chat-content"]').focus();
        if (pvt_chat === 'false') {
          populateMyChat(attendee_name, message, timestamp);
        } else {
          var elem = populateAdminMessage(data.attendee_id, timestamp);
          var chat_box = jQuery('.weninar-chat-showbox');
          chat_box.html(chat_box.html() + elem);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        jQuery('[name="webinar-chat-content"]').prop('disabled', false);
        // Exceptions handled!
      }
    });
  }
});

jQuery(document).ready(function () {
  lastSeenUpdateInterval = typeof reduceServerLoad !== 'undefined' && reduceServerLoad
    ? 1000 * 60 * 5
    : 1000 * 60;

  var timestamp = jQuery('.webinar-chat-push')
    .attr('data-show-timestamp');  

  showTimestamps = (timestamp == 'true' ? true : false);
  isUserAdmin = (jQuery('.webinar-chat-push').attr('data-isadmin') == 'true' ? true : false);
  attendeeId = jQuery('.webinar-chat-push').attr('data-attendeeid');

  setTimeout(function () {
    jQuery('.bootstrap-switch-wrapper').addClass('webinar-bswitch');
  }, 200);

  fetchLiveData();

  // start at a random point between now and 5 mins to spread the load
  setTimeout(updateLastSeen, (lastSeenUpdateInterval) * Math.random());
});

function populateMyChat(name, content, timestamp) {
  timestamp = (timestamp == null ? getFormattedDate() : timestamp);
  var chat_box = jQuery('.weninar-chat-showbox');
  var element = "<div class='webinar-my-chat'><b>" + (showTimestamps ? timestamp : '') + " " + name + " :</b> " + content + "</div>";
  chat_box.html(chat_box.html() + element);
  chat_box.scrollTop(chat_box[0].scrollHeight);
}

function populateAdminMessage(attendee_id, timestamp) {
  if (attendee_id != attendeeId) {
    return '';
  }
  timestamp = (timestamp == null ? getFormattedDate() : timestamp);
  var element = "<span class='webinar-admin-message'><b>" + (showTimestamps ? timestamp : '') + " System Bot :</b> You sent a message to Webinar host.</span>";
  return element;
}

function showChats(data) {
  QUESTIONS_ARRAY = new Array();
  ARRAY_PUSH_COUNT = 0;

  var my_id = jQuery('.webinar-chat-push').attr('data-attendeeid');
  var chat_box = jQuery('.weninar-chat-showbox');
  var big_bag = "";
  var count = 0;
  var admin_messages_count = 0;
  var dataset = data.chats;

  setBoxEnabled(data.show_chatbox, data.show_questionbox);
  getQuestions(data.questions);

  var isModerator = jQuery('.webinar-chat-push').attr('data-isadmin') == 'true';

  jQuery(dataset).each(function () {
    var row = dataset[count];
    var closeBtnContent = "<span data-chatid='" + row.id + "' class='chat_delete fa fa-close'></span>";
    var rowCName = (row.name == null ? '' : row.name);
    var name = rowCName.split(' ')[0];
    var attendee = dataset[count].attendee_id;

    if (row.private == 0) {
        var isAdmin = row.admin;

        if (attendee == my_id) {
          var content = row.content;
          big_bag = big_bag + "<div data-chatrow-id='" + row.id + "' class='chat-parent' ><span data-message='" + row.id + "' class='webinar-my-chat'><b>" + (showTimestamps ? row.timestamp : '') + " Me :</b> " + content + "</span>" + (isModerator ? closeBtnContent : "") + "</div>";
        } else {
          big_bag = big_bag + "<div data-chatrow-id='" + row.id + "' class='chat-parent' ><span data-message='" + row.id + "' class='webinar-other-chat " + (isAdmin == 1 ? "webinar-moderator" : "") + " '><b>" + (showTimestamps ? row.timestamp : '') + " " + name + " " + (isAdmin == 1 ? "(moderator)" : "") + " :</b> " + row.content + "</span>" + (isModerator ? closeBtnContent : "") + "</div>";
        }

    } else if (isUserAdmin) {
      var style_class = (admin_messages_count % 2 === 0 ? 'webinar_privcht_light' : 'webinar_privcht_dark');
      pushtoArray(style_class, name, row.content, row.timestamp);
      admin_messages_count++;
    } else {
      big_bag = big_bag + populateAdminMessage(attendee, row.timestamp);
    }

    count++;
  });

  chat_box.html(big_bag);
  setQuestions();

  if (!disableScrolling) {
    chat_box.scrollTop(chat_box[0].scrollHeight);
  }
}
jQuery(document).on('mouseenter', '.weninar-chat-showbox', function () {
    disableScrolling = true;
});
jQuery(document).on('mouseleave', '.weninar-chat-showbox', function () {
    disableScrolling = false;
});

jQuery(document).on('mouseenter', '.chat-parent', function (event) {
    var msg = jQuery(this).attr('data-chatrow-id');
    jQuery('.chat_delete[data-chatid="' + msg + '"]').fadeIn();
});

jQuery(document).on('mouseleave', '.chat-parent', function () {
    var msg = jQuery(this).attr('data-chatrow-id');
    jQuery('.chat_delete[data-chatid="' + msg + '"]').fadeOut();
});

function getFormattedDate() {
    var date = new Date();
    var day = date.getDate();
    day = (day < 10 ? "0" + day : day);
    var str = date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + day + " " + date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds();
    return str;
}

jQuery('[name="wsweb_private_chat"]').bootstrapSwitch();

function getQuestions(questions) {
    var q_count = 0;
    jQuery(questions).each(function () {
        var que_row = questions[q_count];
        var name = que_row.name.split(' ')[0];
        var style_class = (q_count % 2 === 0 ? 'webinar_privcht_light' : 'webinar_privcht_dark');
        pushtoArray(style_class, name, que_row.question, que_row.time);
        var child_element = "<div class='" + style_class + "'><span><b>" + name + "</b> : " + que_row.question + "</span><br></div>";
        q_count++;
    });
}

function setQuestions() {
    QUESTIONS_ARRAY.sort(function (a, b) {
        var keyA = new Date(a.timestamp),
                keyB = new Date(b.timestamp);
        // Compare the 2 dates
        if (keyA < keyB)
            return -1;
        if (keyA > keyB)
            return 1;
        return 0;
    });
    var arr_length = QUESTIONS_ARRAY.length - 1;

    var message_box = "";
    for (var count = arr_length; count >= 0; count--) {
        var set = QUESTIONS_ARRAY[count];
        message_box = message_box + "<div class='" + set.style + "'><span><b>" + set.name + "</b> : " + set.question + "</span><br></div>";
    }

    if (arr_length == -1) {
        message_box = message_box + "<div id='webinar_no_messages' class='webinar_privcht_system'><span><b>System Bot</b> : No messages to show</span><br></div>";
    } else {
        jQuery('#webinar_no_messages').remove();
    }

    var list_element = jQuery('#wswebinar_private_que');
    list_element.html(message_box);


    if (pollingCount > 1 && OLD_QUESTION_SIZE < ARRAY_PUSH_COUNT) {
        jQuery('.webinar-message-center').addClass('message-center-newmsg');
    }
    OLD_QUESTION_SIZE = QUESTIONS_ARRAY.length;
    return true;
}

jQuery(document).on('click', '#adminbar-handraised', function (event) {
  event.preventDefault();
});

jQuery(document).on('click', '.webinar-message-center', function (event) {
  event.preventDefault();
  jQuery('#wswebinar_private_que').toggleClass('display-block');
  jQuery(this).removeClass('message-center-newmsg');
  jQuery(this).toggleClass('message-center-active');
});

jQuery(document).on('click', '#webinar_show_chatbox', function (event) {
  isProcessingRequest = true;
  event.preventDefault();
  jQuery(this).toggleClass('message-center-newmsg');

  var ajaxurl = jQuery(this).attr('data-ajaxurl');
  var has_elem = jQuery("#webinar_show_chatbox").length;
  var webinar_id = jQuery(this).attr('data-webinarid');

  startAnimation(jQuery(this).attr('id'));

  if (has_elem == 1) {
    var active = jQuery(this).hasClass('message-center-newmsg');
    jQuery.ajax({
        type: 'POST',
        data: {active: active, action: 'set-enabled-chats', webinar_id: webinar_id, page_category: pageCategory, security: wpwebinarsystem.security},
        dataType: 'json',
        url: wpwebinarsystem.ajaxurl,
        success: function (res) {
          setBoxEnabled(res.data.show_chatbox, res.data.show_questionbox);
          stopAnimation();
          isProcessingRequest = false;
          // It's Done
        },
        error: function (jqXHR, textStatus, errorThrown) {
            // Exceptions handled!
            isProcessingRequest = false;
        }
    });
  }
});

function setBoxEnabled(chatbox, questionbox) {
  if (questionbox) {
    // When questionbox active
    jQuery('#webinar_quesbox_tabhead').fadeIn('fast');
    jQuery('#webinar_questionbox').fadeIn('fast');
  } else {
    jQuery('#webinar_quesbox_tabhead').fadeOut('fast');
    jQuery('#webinar_questionbox').fadeOut('fast');
  }

  if (chatbox) {
    jQuery('#webinar_chatbox_tabhead').fadeIn('fast');
    jQuery('#webinar_chatbox').fadeIn('fast');
  } else {
    jQuery('#webinar_chatbox_tabhead').fadeOut('fast');
    jQuery('#webinar_chatbox').fadeOut('fast');
  }

  if (chatbox && !questionbox) {
    // When chatbox enabled and questionbox desabled.
    jQuery('#webinar_chatbox_tabhead a').trigger('click');
    jQuery('#webinar_chatbox').removeClass('hide');
  }

  if (!chatbox && questionbox) {
    // When chatbox disabled and questionbox enabled.
    jQuery('#webinar_chatbox').addClass('hide');
    jQuery('#webinar_quesbox_tabhead a').trigger('click');
    jQuery('#webinar_quesbox_tabhead').addClass('active');
    jQuery('#webinar_quesbox_tabhead').fadeIn();
  }

  if (!chatbox) {
    jQuery('#webinar_chatbox').removeClass('show');
  }

  return true;
}

jQuery(document).on('click', '.webinar_live_viewers', function (event) {
  event.preventDefault();
});

jQuery(document).on('click', '#webinar_show_questionbox', function (event) {
  isProcessingRequest = true;
  event.preventDefault();

  jQuery(this).toggleClass('message-center-newmsg');
  var ajaxurl = jQuery('#webinar_show_chatbox').attr('data-ajaxurl');
  var webinar_id = jQuery(this).attr('data-webinarid');
  var active = jQuery(this).hasClass('message-center-newmsg');
  startAnimation(jQuery(this).attr('id'));

  jQuery.ajax({
    type: 'POST',
    data: {active: active, action: 'set-enabled-questions', webinar_id: webinar_id, page_category: pageCategory, security: wpwebinarsystem.security},
    dataType: 'json',
    url: wpwebinarsystem.ajaxurl,
    success: function (res) {
      // It's Done
      setBoxEnabled(res.data.show_chatbox, res.data.show_questionbox);
      stopAnimation();
      isProcessingRequest = false;
    },
    error: function (jqXHR, textStatus, errorThrown) {
      // Exceptions handled!
      isProcessingRequest = false;
    }
  });
});

var theSaveQuestionButton;
var theSaveQuestionButtonVal;

jQuery(document).on('click', '#saveQuestion', function (e) {
    e.preventDefault();
    var ques_name = jQuery('#que_name').val();
    var ques_email = jQuery('#que_email').val();
    var quest = jQuery('#addQuestion').val();
    if (ques_email.length < 3 || !validateEmail(ques_email) || ques_name.length < 1 || quest.length < 1) {
        alert(questionFormerror);
        return false;
    }

    var data = {
      action: 'wpws-save-question',
      question: quest,
      name: jQuery('#que_name').val(),
      email: jQuery('#que_email').val(),
      webinar_id: theWebinarId,
      security: wpwebinarsystem.security
    };

    theSaveQuestionButton = jQuery(this);
    theSaveQuestionButtonVal = theSaveQuestionButton.val();
    jQuery(this).val(questionWait);
    jQuery(this).attr('disabled', 'disabled');

    jQuery.ajax({
      data: data,
      url: wpwebinarsystem.ajaxurl,
      dataType: 'json',
      type: 'POST'
    }).done(function (res) {
      jQuery('#ques_load').prepend(jQuery('<p class="myquestion"><span>' + res.data.time + '</span>' + quest + '</p>').hide().fadeIn(2000));

      jQuery('#myQuestions').show();
      theSaveQuestionButton.val(theSaveQuestionButtonVal);
      theSaveQuestionButton.removeAttr('disabled');
      jQuery('#addQuestion').val('');

    });
    //e.preventDefault();
});

function validateEmail(email) {
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}
/*
 * Raise hand
 */
jQuery(document).on('click', '.raise-hand-box', function () {
    jQuery('#action_hand').hide();
    jQuery('.actionbox-loader').fadeIn();
    jQuery.ajax({
      url: wpwebinarsystem.ajaxurl,
      data: {action: 'raise-hand', webinar_id: theWebinarId, security: wpwebinarsystem.security},
      dataType: 'json',
      type: 'POST',
    }).done(function (response) {
      jQuery('#action_hand').fadeIn();
      jQuery('.actionbox-loader').hide();
      jQuery('.raise-hand-lg').toggleClass('hand-raised');
    });
});

jQuery(document).on('click', '#adminbar-handraised', function () {
  startAnimation(jQuery(this).attr('id'));
  jQuery.ajax({
    url: wpwebinarsystem.ajaxurl,
    data: {action: 'unraise-hands', webinar_id: theWebinarId, security: wpwebinarsystem.security},
    dataType: 'json',
    type: 'POST',
  }).done(function (response) {
    jQuery('#adminbar-handraised').removeClass('hand-raised-admin');
    jQuery('.raise-hand-lg').removeClass('hand-raised');
    stopAnimation();
  });
});

jQuery('[name="wsweb_private_chat"]').bootstrapSwitch();

/*
 * Show manually the CTA box.
 */
jQuery(document).on('click', '#show_cta_action', function () {
  isProcessingRequest = true;
  setColumnSizes();
  startAnimation(jQuery(this).attr('id'));
  jQuery('.cta-view').toggleClass('show-cta');
  var ctaStatus = (jQuery('.cta-view').hasClass('show-cta') ? 'yes' : 'no');

  jQuery.ajax({
    url: wpwebinarsystem.ajaxurl,
    data: { action: 'show-cta', webinar_id: theWebinarId, cta_status: ctaStatus, webinar_status: pageCategory, security: wpwebinarsystem.security },
    dataType: 'json',
    type: 'POST',
    success: function (res, textStatus, jqXHR) {
      jQuery('#show_cta_action').toggleClass('message-center-newmsg');
      if (!jQuery('.cta-view').hasClass('show')) {
        jQuery('.cta-view').fadeOut();
      }
      setCTAStatus(res.data.showStatus);
      stopAnimation();
      isProcessingRequest = false;
    }
  });
});

/*
 * Show/Hide multiple boxes in live or replay page.
 */

jQuery(document).on('click', '#show_multi_boxes', function (event) {
  event.preventDefault();
  isProcessingRequest = true;
  jQuery('#show_multi_boxes').toggleClass('message-center-newmsg');
  var isShow = jQuery('#show_multi_boxes').hasClass('message-center-newmsg');
  startAnimation(jQuery(this).attr('id'));

  setColumnSizes();
  var updateBoxes = (isShow ? 'yes' : 'no');
  jQuery.ajax({
    url: wpwebinarsystem.ajaxurl,
    data: {action: 'host-desc-boxes', webinar_id: theWebinarId, box_status: updateBoxes, webinar_status: pageCategory, security: wpwebinarsystem.security},
    type: 'POST',
    success: function (data, textStatus, jqXHR) {
      if (isShow) {
        jQuery('#host_box').addClass('show');
        jQuery('#description_box').addClass('show');
        jQuery('#cuspage_host_box').show();
      } else {
        jQuery('#host_box').removeClass('show');
        jQuery('#description_box').removeClass('show');

        jQuery('#host_box').hide();
        jQuery('#description_box').hide();

        jQuery('#cuspage_host_box').hide();
      }
      setColumnSizes();
      stopAnimation();
      isProcessingRequest = false;
    }
  });
});

jQuery(document).on('click', '#livep-play-button', function (e) {
    e.preventDefault();
    wswebinarsysteemMJP[jQuery(this).hasClass('wbnicon-play') ? 'play' : 'pause']();
});

jQuery(document).on('click', '#action_box_handle', function (event) {
  isProcessingRequest = true;
  event.preventDefault();
  startAnimation(jQuery(this).attr('id'));
  jQuery(this).toggleClass('message-center-newmsg');
  var isShow = jQuery(this).hasClass('message-center-newmsg');
  var actionBox = (isShow ? 'yes' : 'no');
  setColumnSizes();

  jQuery.ajax({
    url: wpwebinarsystem.ajaxurl,
    data: {
      action: 'action-box-status',
      webinar_id: theWebinarId,
      box_status: actionBox,
      webinar_status: pageCategory,
      security: wpwebinarsystem.security
    },
    dataType: 'json',
    type: 'POST',
    success: function (data, textStatus, jqXHR) {
      var isShow = jQuery('#action_box_handle')
        .hasClass('message-center-newmsg');

      if (isShow) {
        jQuery('.raise-hand-box').show();
      } else {
        jQuery('.raise-hand-box').hide();
      }

      setColumnSizes();
      stopAnimation();

      isProcessingRequest = false;
    }
  });
});

jQuery(document).on('click', '.webinar-my-chat a', function (event) {
  event.preventDefault();
  window.open(jQuery(this).attr('href'), '_blank');
});

jQuery(document).on('click', '.chat_delete', function (event) {
  event.preventDefault();
  var chat_id = jQuery(this).attr('data-chatid');
  jQuery('span[data-message="' + chat_id + '"]').css('text-decoration', 'line-through');

  var messages = [chat_id];

  jQuery.ajax({
    url: wpwebinarsystem.ajaxurl,
    data: {
      action: 'delete-chats',
      messages: messages,
      webinar_id: theWebinarId,
      security: wpwebinarsystem.security
    },
    dataType: 'json',
    type: 'POST'
  }).done(function (data) {
    jQuery('div[data-chatrow-id="' + chat_id + '"]').remove();
  });
});

function startAnimation(anchorID) {
  var classes_to_remove = [];

  jQuery("#" + anchorID).removeClass(function (index, classNames) {
    var current_classes = classNames.split(" ");
    jQuery.each(current_classes, function (index, class_name) {
      if (!class_name.indexOf('fa') | !class_name.indexOf('glyphicon')) {
        classes_to_remove.push(class_name);
      }
    });
  });

  var animImg = "<img id='adminbar_loader' data-iconclass='" + classes_to_remove.join(" ") + "' class='loading_img_adminbar' data-parent='" + anchorID + "' src='" + loadingImg + "'>";
  var anchorElement = jQuery("#" + anchorID);
  anchorElement.html(animImg);

  jQuery("#" + anchorID).removeClass(classes_to_remove.join(" "));
  return classes_to_remove.join(" ");
}
function stopAnimation() {
  var parent = jQuery('#adminbar_loader').attr('data-parent');
  var clases = jQuery('#adminbar_loader').attr('data-iconclass');
  jQuery('#adminbar_loader').remove();
  jQuery('#' + parent).addClass(clases);
}

jQuery(function () {
  jQuery('[data-toggle="tooltip"]').tooltip();
});

jQuery(document).on('click', '.cta-button', function (event) {
  event.preventDefault();
});

jQuery(document).on('click', '#cta_action_btn_openurl', function (event) {
  event.preventDefault();
  var URL = jQuery(this).attr('href');
  if (URL != '#') {
    window.open(URL);
  }
});
