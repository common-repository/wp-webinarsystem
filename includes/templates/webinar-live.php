<?php
global $post;
global $is_live_page;

$is_live_page = TRUE;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$status = isset($_GET['page']) ? $_GET['page'] : get_post_meta($post->ID, '_wswebinar_gener_webinar_status', true);

$data = WebinarSysteemViews::get_livepage_data($post, $status);

extract($data);

$autoplay = empty($data_autoplay) ? 0 : 1;
$controls = empty($data_controls) ? 0 : 1;
$fullscreen = empty($data_fullscreen) ? 0 : 1;

$hideBigPlayButton = $data_hideBigPlayButton != 'yes';
$the_livep_title_color = empty($data_title_clr) ? '#000' : $data_title_clr;

$show_both_tabs = ($data_show_ques == 'yes' && $data_livep_chatbox_show == 'yes');
$attendee = WebinarSysteemAttendees::get_attendee($post->ID);

$attendee_name = empty($attendee)
    ? ''
    : $attendee->name;

$attendee_name = explode(' ', $attendee_name);

if ($attendee && !$attendee->attended) {
    WebinarSysteemAttendees::update_last_seen($attendee->id);
    WebinarSysteemAttendees::update_joined_at($attendee->id);

    // fire events
    WebinarSysteemWebHooks::send_attended($post->ID, $attendee);
    WebinarSysteemActions::fire_attended($post->ID, $attendee);
}

$user_can = (current_user_can('manage_options') || current_user_can('_wswebinar_accesscbar'));
$ajax_url = admin_url('admin-ajax.php');

// Global Script Tags
$global_header_script = WebinarSysteemSettings::get_global_script(
    WebinarSysteemSettings::GLOBAL_SCRIPT_REGISTRATION_PAGE,
    'headerScriptTag'
);
$global_body_script = WebinarSysteemSettings::get_global_script(
    WebinarSysteemSettings::GLOBAL_SCRIPT_REGISTRATION_PAGE,
    'bodyScriptTag'
);

// Script Tags
$header_script = get_post_meta($post->ID, '_wswebinar_' . $data_page_cat . 'script_head', true);
$body_script = get_post_meta($post->ID, '_wswebinar_' . $data_page_cat . 'script_body', true);

$webinar_start_time = WebinarSysteem::get_webinar_time($post->ID, $attendee);
$server_time_with_timezone = strtotime(WebinarSysteem::getTimezoneTime($post->ID));
$webinar_time_in_seconds = max($server_time_with_timezone - $webinar_start_time, 0);

$reduce_server_load = get_option('_wswebinar_reduce_server_load') == 'on';

// re-write the cache
WebinarSysteemWebinarCache::write($post->ID);

?>

<html>
<head>
    <title><?php echo esc_attr(get_the_title()); ?></title>
    <meta property="og:title" content="<?php the_title(); ?>">
    <meta property="og:url" content="<?php echo esc_url(get_permalink($post->ID)); ?>">
    <meta property="og:description" content="<?php echo esc_attr(substr(wp_strip_all_tags(get_the_content(), true), 0, 500)); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=9; IE=8;"/>
    <style>
        html, body {
            height: 100%;
        }
        .bootstrap-switch {
            float: right;
            margin-top: -40px;
        }

        body.tmp-live {
        <?php
                       echo (empty($data_backg_clr)) ? '' : 'background-color:' . esc_attr($data_backg_clr) . ';';
                       echo (empty($data_backg_img)) ? '' : 'background-image: url(' . esc_attr($data_backg_img) . ');';
                   ?>
        }

        body.tmp-live {
        <?php
                       echo (empty($data_backg_clr)) ? '' : 'background-color:' . esc_attr($data_backg_clr) . ';';
                       echo (empty($data_backg_img)) ? '' : 'background-image: url(' . esc_attr($data_backg_img) . ');';
                   ?>
        }

        <?php if ($data_show_ques == 'yes'): ?>
        input[type="submit"]#saveQuestion {
            box-shadow: none;
            border-style: solid;
            border-width: 1px;
        <?php
                           echo (!empty($data_livep_button_radius) ) ? 'border-radius:' . esc_attr($data_livep_button_radius) . ';' : '';
                           echo (!empty($data_livep_button_bg_clr) ) ? 'background-color:' . esc_attr($data_livep_button_bg_clr) . ';' : '';
                           echo (!empty($data_livep_button_border_clr) ) ? 'border-color:' . esc_attr($data_livep_button_border_clr) . ';' : '';
                           echo (!empty($data_livep_button_text_clr) ) ? 'color:' . esc_attr($data_livep_button_text_clr) . ';' : '';
                           ?>
        }

        input[type="submit"]#saveQuestion:hover {
        <?php
                       echo (!empty($data_livep_buttonhover_bg_clr) ) ? 'background-color:' . esc_attr($data_livep_buttonhover_bg_clr) . ';' : '';
                       echo (!empty($data_livep_buttonhover_border_clr) ) ? 'border-color:' . esc_attr($data_livep_buttonhover_border_clr) . ';' : '';
                       echo (!empty($data_livep_buttonhover_text_clr) ) ? 'color:' . esc_attr($data_livep_buttonhover_text_clr) . ';' : '';
                       ?>
        }

        <?php endif; ?>
        <?php
        if (isset($is_live_page) && $is_live_page && $user_can):
            ?>
        html {
            margin-top: 32px !important;
        }

        <?php endif; ?>

        #action_hand {
            color: <?php echo (empty($action_hand_color) ? '#FFF' : esc_attr($action_hand_color)); ?>;
        }

        #action_hand:hover {
            color: <?php echo (empty($action_hand_hover_color) ? '#090' : esc_attr($action_hand_hover_color)); ?>;
        }

        .hand-raised {
            color: <?php echo (empty($action_hand_active_color) ? '#009900' : esc_attr($action_hand_active_color)) ?> !important;
        }

        .livep-content {
            color: <?php echo (empty($data_livep_incentive_content_clr) ? '#FFF' : esc_attr($data_livep_incentive_content_clr)); ?>
        }

        ;
        .cta-button {
            width: 100%;
            padding: 5px;
        }

        .cta-button button {
            width: 100%;
            margin: 15px 0px 5px 0px;
            background-color: <?php echo esc_attr($data_livep_ctabtn_clr); ?>;
            border: 4px solid<?php echo esc_attr($data_livep_cta_btn_borderclr); ?>;
            color: <?php echo esc_attr($data_livep_cta_btn_txtclr); ?>;
            border-radius: <?php echo esc_attr($data_livep_ctabtn_brdr_radius); ?>px;
        }

        .cta-button button:hover {
            background-color: <?php echo esc_attr($data_livep_ctabtn_hover_clr); ?>;
            color: <?php echo esc_attr($data_livep_ctabtn_hover_txtclr); ?>;
        }

        .cta-button button:visited {
            background-color: <?php echo esc_attr($data_livep_ctabtn_hover_clr); ?>;
        }

        .cta-button button:selected {
            background-color: <?php echo esc_attr($data_livep_ctabtn_hover_clr); ?>;
        }

        .cta-button button:active {
            background-color: <?php echo esc_attr($data_livep_ctabtn_hover_clr); ?>;
        }

        .cta-button button:focus {
            background-color: <?php echo esc_attr($data_livep_ctabtn_clr); ?>;
        }

        #cta_txt_view {
            margin-top: 10px;
            background-color: <?php echo esc_attr($data_cta_txt_bgclr) ?>;
            border-color: <?php echo esc_attr($data_cta_txt_brdclr); ?>;
        }

        #cta_txt_view div {
            color: <?php echo esc_attr($data_cta_txt_cntnt_clr); ?>;
        }

        .show {
            display: block;
        }

        .livep-content ul {
            line-height: 10px;
            margin: 0px 0px -20px 40px;
        }

        .livep-content ol {
            line-height: 10px;
            margin: 0px 0px -20px 40px;
        }

        #show_incentive ul {
            line-height: initial;
            margin: 0px 0px 10px 40px;
        }

        #show_incentive ol {
            line-height: initial;
            margin: 0px 0px 10px 40px;
        }

        .webinar-chat-push {
            background-color: <?php echo (empty($data_livep_bgclr_chatbtn) ? '#009900' : esc_attr($data_livep_bgclr_chatbtn)); ?> !important;
        }

        .webinar-chat-push {
            border-color: <?php echo (empty($data_livep_bgclr_chatbtn) ? '#009900' : esc_attr($data_livep_bgclr_chatbtn)); ?> !important;
        }

        .webinar-chat-push {
            color: <?php echo (empty($data_livep_chatbtn_txtclr) ? '#fff' : esc_attr($data_livep_chatbtn_txtclr)); ?> !important;
        }

    </style>
    <?php wp_head(); ?>
    <?php echo esc_html($global_header_script) ?>
    <?php echo esc_html($header_script) ?>
</head>
<body class="tmp-live">
<div class="container">
    <!--[if lt IE 9]>
    <div style='row'>
        <div class="col-xs-6 col-xs-offset-2">
            <a href="http://www.microsoft.com/windows/internet-explorer/default.aspx">
                <img src="<?php echo esc_url(plugins_url('../images/iecheck.jpg', __FILE__)); ?>" border="0" height="42"
                     width="820" alt=""/>
            </a>
        </div>
    </div>
    <![endif]-->
    <?php if (empty($data_title_show_yn)): ?>
        <div class="row">
            <div class="col-lg-12 col-xs-12">
                <div>
                    <h1 class="text-center"
                        style="font-weight: 400; color: <?php echo esc_attr($the_livep_title_color) ?>;"><?php the_title(); ?></h1>
                </div>
            </div>
        </div>
    <?php endif ?>
    <div class="row" style="margin-top: 40px;">
        <div class="col-lg-12 col-sm-12 col-xs-12">
            <div id="embed">
                <?php
                if (empty($data_imgvid_url)) {
                    ?>
                    <img src="<?php echo esc_url($data_defImgUrl); ?>" width="100%" height="315">
                    <?php
                } else {
                    switch ($data_imgvid_type) {
                        case 'image':
                            echo '<img src="' . esc_url($data_imgvid_url) . '" width="100%" height="500px">';
                            break;
                        case 'youtube':
                        case 'hoa':
                            $link = $data_imgvid_url;
                            $youtubeid = WebinarSysteem::getYoutubeIdFromUrl($link);
                            WebinarSysteemVideoSources::getSourceCode('youtube', $youtubeid, $controls, $autoplay, $hideBigPlayButton, $fullscreen);
                            break;
                        case 'vimeo':
                            echo '<iframe src="https://player.vimeo.com/video/' . esc_url($data_imgvid_url) . '?autoplay=' . esc_attr($autoplay) . '" class="livep_vidheight" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
                            break;
                        case 'file':
                            WebinarSysteemVideoSources::getSourceCode('mp4', $data_imgvid_url, $controls, $autoplay, $hideBigPlayButton, $fullscreen);
                            break;
                        case 'rtmp':
                            WebinarSysteemVideoSources::getSourceCode('rtmp', $data_imgvid_url, $controls, $autoplay, $hideBigPlayButton, $fullscreen);
                            break;
                        case 'hls':
                            WebinarSysteemVideoSources::getSourceCode('hls', $data_imgvid_url, $controls, $autoplay, $hideBigPlayButton, $fullscreen);
                            break;
                        case 'iframe':
                            echo '<iframe width="100%" height="563" src="' . esc_url($data_imgvid_url) . '" frameborder="0" allowfullscreen></iframe>';
                            break;
                        case 'youtubelive':
                            $youtubeid = WebinarSysteem::getYoutubeIdFromUrl($data_imgvid_url);
                            $yturl = $youtubeid ? "//www.youtube.com/embed/$youtubeid" : $data_imgvid_url;
                            WebinarSysteemVideoSources::getSourceCode('youtube', $yturl, $controls, $autoplay, $hideBigPlayButton = false, $fullscreen);
                            break;
                        case 'wistia':
                            echo esc_url($data_imgvid_url);
                            break;
                    }
                }
                ?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <?php
            $timed_showing = ($data_livep_livep_call_actiontype == 'aftertimer' ? true : false);
            if ($data_livep_cta_type == 'button'):
                ?>
                <a id="cta_action_btn_openurl"
                   href="<?php echo esc_url(empty($data_livep_ctabtn_url) ? '#' : $data_livep_ctabtn_url); ?>" <?php echo esc_attr(empty($data_livep_ctabtn_url) ? '' : 'target="_blank"') ?>
                   class="cta-button cta-view <?php echo(WebinarSysteemAjax::get_cta_status($post->ID, $data_page_cat) ? 'show show-cta' : ''); ?>"
                   style="<?php echo(WebinarSysteemAjax::get_cta_status($post->ID, $data_page_cat) ? 'display:block;' : 'display: none;'); ?>">
                    <button><?php echo(!empty($data_livep_ctabtn_txt) ? esc_attr($data_livep_ctabtn_txt) : 'CTA Button'); ?></button>
                </a>
            <?php elseif ($data_livep_cta_type == 'txt_field'): ?>
                <div id="cta_txt_view"
                     class="signup round-border cta-view <?php echo(WebinarSysteemAjax::get_cta_status($post->ID, $data_page_cat) ? 'show show-cta' : ''); ?>"
                     style="<?php echo(WebinarSysteemAjax::get_cta_status($post->ID, $data_page_cat) ? 'display:block;' : 'display: none;'); ?> ">
                    <div class="livep-content"><?php echo esc_html(apply_filters('the_content', $data_livep_cta_text)); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5 col-sm-6 col-xs-12">
            <?php
            $actionbox_bg_color = get_post_meta($post->ID, '_wswebinar_' . $data_page_cat . 'action_bckg_clr', true);
            $actionbox_border_clr = get_post_meta($post->ID, '_wswebinar_' . $data_page_cat . 'action_box_border_clr', true);
            ?>

            <div class="live-box raise-hand-box left-box"
                 style="<?php echo($data_show_action_box == 'yes' ? 'display:block' : 'display:none;'); ?>; <?php echo(!empty($actionbox_border_clr) ? 'border: 3px solid ' . esc_attr($actionbox_border_clr) . ';' : 'border: 3px solid #333;') ?> ; background-color: <?php echo(isset($actionbox_bg_color) && !empty($actionbox_bg_color) ? esc_attr($actionbox_bg_color) : 'rgba(0, 0, 0, 0.67)'); ?>;">
                <img class="actionbox-loader" style="display: none;"
                     src="<?php echo esc_url(plugins_url('../images/loding_large64x64.GIF', __FILE__)); ?>">
                <span id="action_hand" data-attendee="<?php echo intval($attendee->id); ?>"
                      class="fa fa-hand-paper-o pull-left raise-hand-lg <?php echo(WebinarSysteemAttendees::get_attendee($post->ID)->high_five == 1 ? 'hand-raised' : ''); ?>"></span>
            </div>

            <div class="live-box signup round-border left-box" id="cuspage_host_box"
                 style="margin-top: 10px; background-color: <?php echo esc_attr($data_livep_leftbox_bckg_clr) ?>; border-color:<?php echo esc_attr($data_livep_leftbox_border_clr); ?>; <?php echo($data_show_desc == 'yes' || $data_show_presenter == 'yes' ? 'display:block;' : 'display:none;') ?>">
                <div style="<?php echo($data_show_presenter == 'yes' ? 'display: block;' : 'display: none;'); ?>"
                     class="<?php echo($data_show_presenter == 'yes' ? 'show' : ''); ?>" id="host_box">
                    <div class="live-title"
                         style="color:<?php echo esc_attr($data_livep_hostbox_title_text_clr) ?>; background-color: <?php echo esc_attr($data_livep_hostbox_title_bckg_clr) ?>;"><?php $host_text = _n('Host', 'Hosts', $data_hostcount, '_wswebinar'); echo esc_html($host_text); ?></div>
                    <div class="livep-content" style="color:<?php echo esc_attr($data_livep_hostbox_content_text_clr) ?>;">
                        <?php
                        foreach ($data_hostnames as $hostname) {
                            echo esc_attr($hostname) . '<br/>';
                        }
                        ?>
                    </div>
                </div>
                <div id="description_box" class="<?php echo($data_show_desc == 'yes' ? 'show' : ''); ?>"
                     style="<?php echo($data_show_desc == 'yes' ? 'display: block;' : 'display: none;'); ?>">
                    <div class="live-title"
                         style="color:<?php echo esc_attr($data_livep_descbox_title_text_clr) ?>;background-color:<?php echo esc_attr($data_livep_descbox_title_bckg_clr) ?>;"><?php esc_html_e('Information', '_wswebinar') ?></div>
                    <div class="livep-content"
                         style="color:<?php echo esc_attr($data_livep_descbox_content_text_clr); ?>"><?php the_content(); ?></div>
                </div>
            </div>
        </div>
        <?php if ($data_show_desc == 'yes' || $data_show_presenter == 'yes' || $data_show_action_box == 'yes'): ?>
        <div id="short_column" class="col-lg-7 col-sm-6 col-xs-12 box-column" style="margin-bottom: 80px;">
            <?php else : ?>
            <div id="long_column" class="col-lg-12 col-sm-12 col-xs-12 box-column" style="margin-bottom: 80px;">
                <?php endif; ?>
                <!--
                Chatbox and Questionbox
                -->
                <?php
                ?>
                <ul class="nav nav-tabs webinar-livep-navs" id="custom-tabs"
                    style="margin-bottom: -10px; margin-top: 10px;">
                    <li class="wp_livep_tabhead" id="webinar_quesbox_tabhead"
                        style="border-color: <?php echo esc_attr($data_livep_questiontab_borderclr) ?> ; display: none;"><a
                                style="color: <?php echo esc_attr($data_livep_questiontab_title_clr) ?>; background-color: <?php echo esc_attr($data_livep_questiontab_title_bgclr); ?>;"><?php echo(!empty($data_livep_questiontab_title) ? esc_attr($data_livep_questiontab_title) : esc_html__('Question Box', '_wswebinar')) ?></a>
                    </li>
                    <li class="wp_livep_tabhead" id="webinar_chatbox_tabhead"
                        style="border-color:<?php echo esc_attr($data_livep_chatbox_borderclr); ?> ; display: none;"><a
                                style="color: <?php echo esc_attr($data_livep_questiontab_chat_tcolor) ?>; background-color:<?php echo esc_attr($data_livep_questiontab_chat_bcolor); ?>;"><?php echo(!empty($data_livep_questiontab_chat_title) ? esc_attr($data_livep_questiontab_chat_title) : esc_html__('Chat box', '_wswebinar'));
                            ?></a></li>
                </ul>

                <div class="content-wraper">
                    <div id="webinar_questionbox" class="tab-content question" style="display: none;">
                        <div class="round-border signup"
                             style="margin-top: 10px; background-color: <?php echo esc_attr($data_livep_askq_bckg_clr) ?>;border-color:<?php echo esc_attr($data_livep_askq_border_clr) ?>">
                            <h2 style="color:<?php echo esc_attr($data_askq_title_text_clr); ?>;"
                                class="live-title-sub"><?php esc_html_e('Ask your question!', '_wswebinar') ?></h2>
                            <div style="margin-left: 10px;">
                                <form id="addQuestionForm">
                                    <div class="form-group">
                                        <input type="text" class="form-control"
                                               placeholder="<?php esc_html_e('Your name', '_wswebinar'); ?>"
                                               id="que_name" name="que_name"
                                               value="<?php echo(empty($attendee) ? '' : esc_attr($attendee->name)); ?>">
                                    </div>
                                    <div class="form-group">
                                        <input type="text" class="form-control"
                                               placeholder="<?php esc_html_e('Your email', '_wswebinar'); ?>"
                                               id="que_email"
                                               value="<?php echo(empty($attendee) ? '' : esc_attr($attendee->email)); ?>">
                                    </div>
                                    <div class="form-group">
                                        <textarea rows="4" cols="50" class="form-control" id="addQuestion"
                                                  placeholder="<?php esc_html_e('Type your question here..', '_wswebinar'); ?>"
                                                  draggable></textarea>
                                    </div>
                                    <input type="submit" id="saveQuestion" class="btn btn-success"
                                           value="<?php esc_html_e('Ask Question!', '_wswebinar') ?>">
                                </form>
                            </div>
                            <div id="myQuestions" style="display:none;">
                                <h3 class="live-title-sub"><?php esc_html_e('My Questions', '_wswebinar') ?></h3>
                                <span id="ques_load"></span>
                            </div>
                        </div>
                    </div>
                    <div id="webinar_chatbox"
                         class="tab-content text-center round-border chat <?php echo($data_livep_chatbox_show == 'yes' && $show_both_tabs == false ? 'show' : 'hide'); ?>"
                         style=" margin-top: 10px; border-color:<?php echo esc_attr($data_livep_chtbx_border_clr); ?>;">
                        <div class="signup"
                             style="margin-top: 10px; background-color: <?php echo esc_attr($data_livep_chatbox_bgclr) ?>;border-color:<?php echo esc_attr($data_livep_askq_border_clr) ?>; text-align: left; margin: 0px 0px 0px 0px;">
                            <h2 style="color:<?php echo esc_attr($data_livep_chatbox_txtclr); ?>;"
                                class="live-title-sub"><?php esc_html_e('Live Chat', '_wswebinar') ?></h2>
                            <?php if (!current_user_can('manage_options')): ?>
                                <input data-off-color="webinar-bswitchoff" data-on-color="webinar-bswitchon"
                                       data-on-text="<?php esc_html_e('Private', '_wswebinar') ?>"
                                       data-off-text="<?php esc_html_e('Public', '_wswebinar') ?>" type="checkbox"
                                       name="wsweb_private_chat">
                            <?php endif; //webinar-bswitchoff     ?>
                            <div class="weninar-chat-showbox box_shadow">

                            </div>
                            <div id="webinar-chat-action">
                                <div class="row">
                                    <div class="container-fluid">
                                        <div class="col-lg-10 col-sm-10 col-xs-10 col-md-10 wswebinar_zero_padding">
                                            <input type="text" name="webinar-chat-content"
                                                   class="webinar-chat-content box_shadow" style="color: #000;">
                                        </div>
                                        <div class="col-lg-2 col-sm-2 col-xs-2 col-md-2 wswebinar_zero_padding">
                                            <button type="button" class="webinar-chat-push btn btn-success box_shadow"
                                                <?php
                                                ?>
                                                    data-webinarid="<?php echo intval($post->ID); ?>"
                                                    data-attendeeid="<?php echo intval(empty($attendee) ? '1' : $attendee->id); ?>"
                                                    data-attendeename="Me"
                                                    data-ajaxurl="<?php echo esc_url($ajax_url); ?>"
                                                    data-isadmin="<?php echo(current_user_can('manage_options') ? 'true' : 'false'); ?>"
                                                    data-show-timestamp="<?php echo($data_livep_chat_timestamp == 'yes' ? 'true' : 'false'); ?>"
                                            >
                                                <?php esc_html_e('Send', '_wswebinar') ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--
                    End of Chatbox and Questionbox
                    -->

                    <!--
                        Incentive box
                    -->
                    <input type="hidden" value="<?php echo esc_attr($data_show_incentive); ?>" id="data_show_incentive"/>
                    <div class="live-box signup round-border" id="show_incentive"
                         style="display: <?php echo($data_show_incentive == 'yes' ? 'block' : 'none'); ?>; margin-top: 10px; background-color: <?php echo esc_attr($data_livep_incentive_bckg_clr) ?>; border-color: <?php echo esc_attr($data_livep_incentive_border_clr) ?>;">
                        <div class="live-title"
                             style="color:<?php echo esc_attr($data_livep_incentive_title_clr) ?>;background-color: <?php echo esc_attr($data_livep_incentive_title_bckg_clr) ?>;"><?php echo esc_attr($data_livep_incentive_title); ?></div>
                        <div class="livep-content"><?php echo esc_html(apply_filters('the_content', $data_livep_incentive_content)); ?></div>
                    </div>
                    <!--
                        End of Incentive box
                    -->
                </div>
            </div>
        </div>

        <div id="wswebinar_livep_footer"></div>

        <script>
            var theWebinarId = <?php echo intval($post->ID); ?>;
            var questionFormerror = '<?php esc_html_e('Something is wrong with your Add Questions form. Please re-check all fields are filled correctly', '_wswebinar') ?>';
            var questionWait = '<?php esc_html_e('Please wait..', '_wswebinar') ?>';
            var theWebinarstatus = '<?php echo esc_attr(empty($status) ? 'replay' : $status); ?>';
            var pageCategory = '<?php echo esc_attr($data_page_cat); ?>';
            var fetchValues = true;
            var callToActionTimeInterval = <?php echo esc_attr(empty($data_livep_livep_cta_show_after) ? '0' : $data_livep_livep_cta_show_after); ?>;
            var callToActionMode = <?php echo esc_attr($data_livep_livep_call_actiontype == 'aftertimer' ? 'true' : 'false'); ?>;
            var transferValues = true;
            var ajaxurl = '<?php echo esc_url($ajax_url); ?>';
            var loadingImg = '<?php echo esc_url(plugins_url('../images/loading_small.GIF', __FILE__)); ?>';
            var simulationEnabled = <?php echo esc_attr($data_simulate_video == 'yes' ? 'true' : 'false') ?>;
            var cacheUrl = '<?php echo esc_url($cache_url); ?>';
            var webinarTimeInSeconds = <?php echo esc_attr($webinar_time_in_seconds); ?>;
            var webinarStartTime = new Date(Date.now() - (webinarTimeInSeconds * 1000));
            var reduceServerLoad = <?php echo esc_attr($reduce_server_load ? 'true' : 'false') ?>;
        </script>
    </div>
    <?php wp_footer(); ?>
    <?php echo esc_html($global_body_script) ?>
    <?php echo esc_html($body_script) ?>
</body>
</html>
