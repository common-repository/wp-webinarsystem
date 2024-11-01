/*Custom register/login tabs*/
/* global wpws_ajaxurl, wswebinarsysteemMJP, theWebinarId */

jQuery(document).on('click', '#custom-tabs li a', function ()
{
    /*Remove active*/
    jQuery('#custom-tabs li').removeClass('active');
    jQuery('.content-wraper .tab-content').addClass('hide');

    /*add active*/
    jQuery(this).parent('li').addClass('active');
    jQuery('.content-wraper .tab-content:eq(' + jQuery(this).parent('li').index() + ')').removeClass('hide');
    jQuery('[name="wsweb_private_chat"]').bootstrapSwitch();

});

ARRAY_PUSH_COUNT = 0;
function pushtoArray(style_class, name, question, timestamp) {
    QUESTIONS_ARRAY[ARRAY_PUSH_COUNT] = {};
    QUESTIONS_ARRAY[ARRAY_PUSH_COUNT]['style'] = style_class;
    QUESTIONS_ARRAY[ARRAY_PUSH_COUNT]['name'] = name;
    QUESTIONS_ARRAY[ARRAY_PUSH_COUNT]['question'] = question;
    QUESTIONS_ARRAY[ARRAY_PUSH_COUNT]['timestamp'] = timestamp;
    ARRAY_PUSH_COUNT++;
}

jQuery(document).ready(function ($) {
    jQuery('.tmp-live').on('contextmenu', function (e) {
        return false;
    });
    
    $('video.vjs-tech').on('contextmenu', function (e) {
        e.preventDefault();
    });

//    Remove Monarch plugin Styles
    jQuery("[class^='et_']").hide();
});

/**
 * Simulate content
 * When a user enters the webinar late (for example 10 minutes after start), then the video source will be playing/simulated from minute 10, so it seems like the webinar is really live. When the attendee is in the middle of the webinar, and he refreshes the page after 30 minutes, then the video should continue after that 30 minutes also.
 */
jQuery(function ($) {

    if (typeof simulationEnabled == "undefined" || !simulationEnabled)
        return;

    var position = readCookie(theWebinarId);

    if (position)
        var simulationTimer = setInterval(function () {
            if (typeof wswebinarsysteemMJP !== "undefined") {
                wswebinarsysteemMJP.setCurrentTime(parseInt(position));
                clearTimeout(simulationTimer);
            }
        }, 1000);


    $(window).bind('beforeunload', function () {
        var currentTime = Math.round(wswebinarsysteemMJP.currentTime);
        if (currentTime != 0)
            createCookie(theWebinarId, currentTime);
    });

    //Force show MediaElement player overlay
    setInterval(function () {
        $('.mejs-layers .mejs-overlay.mejs-layer').eq(1).show();
    }, 5000);
});
