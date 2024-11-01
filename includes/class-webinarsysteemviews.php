<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class-webinarsysteemviews
 *
 * @author Thambaru
 */
class WebinarSysteemViews {
    static function get_color($post_id, $field) {
        global $post;
        $color = get_post_meta($post_id, $field, true);
        return WebinarSysteemHelperFunctions::add_hash_to_color($color);
    }

    static function get_livepage_data($post, $status) {
        $page = ($status == 'live' || $status == 'liv') ? 'livep_' : 'replayp_';
        setup_postdata($post);
        WebinarSysteem::setPostData($post->ID);
        $attendee = WebinarSysteemAttendees::get_attendee($post->ID);

        return [
            'data_title_show_yn' => (string)get_post_meta($post->ID, '_wswebinar_' . $page . 'title_show_yn', true),
            'data_title_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'title_clr', true),
            'data_backg_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'bckg_clr', true),

            'data_backg_img' => get_post_meta($post->ID, '_wswebinar_' . $page . 'bckg_img', true),
            'data_imgvid_type' => get_post_meta($post->ID, '_wswebinar_' . $page . 'vidurl_type', true),
            'data_imgvid_url' => get_post_meta($post->ID, '_wswebinar_' . $page . 'vidurl', true),
            'data_show_presenter' => get_post_meta($post->ID, '_wswebinar_' . $page . 'hostbox_yn', true),
            'data_show_desc' => get_post_meta($post->ID, '_wswebinar_' . $page . 'webdes_yn', true),
            'data_show_ques' => get_post_meta($post->ID, '_wswebinar_' . $page . 'askq_yn', true),
            'data_show_incentive' => get_post_meta($post->ID, '_wswebinar_' . $page . 'incentive_yn', true),
            'data_defImgUrl' => plugins_url('/images/clapper.jpg', __FILE__),
            'data_hostnames' => WebinarSysteemHosts::getHostsArray($post->ID),
            'data_hostcount' => (int)WebinarSysteemHosts::hostCount($post->ID),
            'data_autoplay' => get_post_meta($post->ID, '_wswebinar_' . $page . 'video_auto_play_yn', true),
            'data_controls' => get_post_meta($post->ID, '_wswebinar_' . $page . 'video_controls_yn', true),
            'data_fullscreen' => get_post_meta($post->ID, '_wswebinar_' . $page . 'fullscreen_control', true),
            'data_hideBigPlayButton' => get_post_meta($post->ID, '_wswebinar_' . $page . 'bigplaybtn_yn', true),
            'data_simulate_video' => get_post_meta($post->ID, '_wswebinar_' . $page . 'simulate_video_yn', true),
            'data_askq_title_text_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'askq_title_text_clr', true),
            'data_livep_askq_bckg_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'askq_bckg_clr', true),
            'data_livep_leftbox_bckg_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'leftbox_bckg_clr', true),
            'data_livep_descbox_title_bckg_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'descbox_title_bckg_clr', true),
            'data_livep_descbox_title_text_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'descbox_title_text_clr', true),
            'data_livep_descbox_content_text_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'descbox_content_text_clr', true),
            'data_livep_hostbox_title_bckg_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'hostbox_title_bckg_clr', true),
            'data_livep_hostbox_title_text_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'hostbox_title_text_clr', true),
            'data_livep_hostbox_content_text_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'hostbox_content_text_clr', true),
            'data_livep_incentive_bckg_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'incentive_bckg_clr', true),
            'data_livep_incentive_border_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'incentive_border_clr', true),
            'data_livep_incentive_title_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'incentive_title_clr', true),
            'data_show_action_box' => get_post_meta($post->ID, '_wswebinar_'.$page.'show_actionbox', true),
            'data_livep_leftbox_border_clr' => self::get_color($post->ID, '_wswebinar_livep_leftbox_border_clr', true),
            'data_livep_button_bg_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'button_bg_clr', true),
            'data_livep_buttonhover_bg_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'buttonhover_bg_clr', true),
            'data_livep_button_border_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'button_border_clr', true),
            'data_livep_buttonhover_border_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'buttonhover_border_clr', true),
            'data_livep_button_text_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'button_text_clr', true),
            'data_livep_buttonhover_text_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'buttonhover_text_clr', true),
            'data_livep_button_radius' => get_post_meta($post->ID, '_wswebinar_' . $page . 'button_radius', true),
            'data_livep_incentive_title' => get_post_meta($post->ID, '_wswebinar_' . $page . 'incentive_title', true),
            'data_livep_incentive_content' => get_post_meta($post->ID, '_wswebinar_' . $page . 'incentive_content', true),
            'data_livep_incentive_title_bckg_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'incentive_title_bckg_clr', true),
            'data_livep_incentive_content_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'incentive_content_clr', true),
            'data_livep_chatbox_show' => get_post_meta($post->ID, '_wswebinar_' . $page . 'show_chatbox', true),
            'data_livep_chat_timestamp' => get_post_meta($post->ID, '_wswebinar_' . $page . 'show_chatbox_timestmp', true),
            'data_livep_chatbox_bgclr' => self::get_color($post->ID, '_wswebinar_' . $page . 'chtb_bckg_clr', true),
            'data_livep_chatbox_txtclr' => self::get_color($post->ID, '_wswebinar_' . $page . 'chtb_title_text_clr', true),
            'data_livep_chatbox_borderclr' => self::get_color($post->ID, '_wswebinar_' . $page . 'chtb_chat_border_clr', true),
            
            
            'data_livep_bgclr_chatbtn' => get_post_meta($post->ID, '_wswebinar_' . $page . 'bgclr_chatbtn', true),
            'data_livep_chatbtn_txtclr' => self::get_color($post->ID, '_wswebinar_' . $page . 'txtclr_chatbtn', true),
            
            
            'data_livep_questiontab_title_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'chtb_quebox_title_text_clr', true),
            'data_livep_questiontab_title_bgclr' => self::get_color($post->ID, '_wswebinar_' . $page . 'chtb_quebox_bkg_text_clr', true),
            'data_livep_questiontab_title' => get_post_meta($post->ID, '_wswebinar_' . $page . 'chtb_quebox_title', true),
            'data_livep_questiontab_borderclr' => self::get_color($post->ID, '_wswebinar_' . $page . 'chtb_quebox_border_clr', true),
            'data_livep_askq_border_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'askq_border_clr', true),
            'data_livep_chtbx_border_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'chtb_border_clr', true),
            'data_livep_questiontab_chat_title' => get_post_meta($post->ID, '_wswebinar_' . $page . 'chtb_chat_title', true),
            'data_livep_questiontab_chat_tcolor' => self::get_color($post->ID, '_wswebinar_' . $page . 'chtb_chat_title_text_clr', true),
            'data_livep_questiontab_chat_bcolor' => self::get_color($post->ID, '_wswebinar_' . $page . 'chtb_chat_bkg_text_clr', true),
            
            'data_livep_cta_type' => get_post_meta($post->ID, '_wswebinar_' . $page . 'call_action_ctatype', true),
            'data_livep_cta_text' => get_post_meta($post->ID, '_wswebinar_' . $page . 'ctatxt_txt', true),
            'data_livep_livep_cta_show_after' => get_post_meta($post->ID, '_wswebinar_' . $page . 'cta_show_after', true),
            'data_livep_livep_call_actiontype' => get_post_meta($post->ID, '_wswebinar_' . $page . 'call_action', true),
            
            'data_livep_ctabtn_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'ctabtn_clr', true),
            'data_livep_ctabtn_hover_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'ctabtn_hover_clr', true),
            'data_livep_cta_btn_borderclr' => self::get_color($post->ID, '_wswebinar_' . $page . 'ctabtn_border_clr', true),
            'data_livep_cta_btn_txtclr' => self::get_color($post->ID, '_wswebinar_' . $page . 'ctabtn_txt_clr', true),
            'data_livep_ctabtn_txt' => get_post_meta($post->ID, '_wswebinar_' . $page . 'ctabtn_txt', true),
            'data_livep_ctabtn_url' => get_post_meta($post->ID, '_wswebinar_' . $page . 'ctabtn_url', true),
            'data_livep_manual_show_cta' => get_post_meta($post->ID, '_wswebinar_' . $page . 'manual_show_cta', true),
            'data_livep_ctabtn_hover_txtclr' => self::get_color($post->ID, '_wswebinar_' . $page . 'ctabtn_hover_txt_clr', true),
            'data_livep_ctabtn_brdr_radius' => get_post_meta($post->ID, '_wswebinar_' . $page . 'ctabtn_brdr_radius', true),
            
            'data_cta_txt_bgclr' => self::get_color($post->ID, '_wswebinar_' . $page . 'ctatxt_fld_bckg_clr', true),
            'data_cta_txt_brdclr' => self::get_color($post->ID, '_wswebinar_' . $page . 'ctatxt_fld_border_clr', true),
            'data_cta_txt_cntnt_clr' => self::get_color($post->ID, '_wswebinar_' . $page . 'ctatxt_fld_content_clr', true),
            
            'action_hand_color' => self::get_color($post->ID,'_wswebinar_' . $page . 'action_raise_hand_clr', true),
            'action_hand_hover_color' => self::get_color($post->ID,'_wswebinar_' . $page . 'action_raise_hand_hover_clr', true),
            'action_hand_active_color' => self::get_color($post->ID,'_wswebinar_' . $page . 'action_raise_hand_act_clr', true),
            
            'data_actionbox_border' => self::get_color($post->ID, '_wswebinar_' . $page . 'action_box_border_clr', true),
            'data_actionbox_background' => self::get_color($post->ID, '_wswebinar_' . $page . 'action_bckg_clr', true),
            'data_page_cat' => $page,
            'cache_url' => WebinarSysteemWebinarCache::get_cache_url($post->ID),
            'start_time' => WebinarSysteem::get_webinar_time($post->ID, $attendee),
            'language' => get_translations_for_domain(WebinarSysteem::$lang_slug)->get_header('Language'),
            'translations' => (object)array_map(function($entry){
                return $entry->translations[0];
            }, get_translations_for_domain(WebinarSysteem::$lang_slug)->entries),
            'header_script' => get_post_meta($post->ID, '_wswebinar_' . $page . 'script_head', true),
            'body_script' => get_post_meta($post->ID, '_wswebinar_' . $page . 'script_body', true),
        ];
    }
}
