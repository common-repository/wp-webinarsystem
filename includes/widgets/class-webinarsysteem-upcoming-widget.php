<?php

class WebinarSysteemUpcomingWebinars extends WP_Widget {

    public function __construct() {
        $widget_ops = array(
            'classname' => 'WebinarSysteemUpcomingWebinars',
            'description' => 'Show the Upcoming Webinars.',
        );
        parent::__construct('WebinarSysteemUpcomingWebinars', 'WebinarPress - Upcoming webinars', $widget_ops);
    }

    public function widget($args, $instance) {
        ?>
        <form method="post">
            <div class="widget">
                <h2 class="widget-title"><?php echo esc_html(empty($instance['wswebinar_upcomin_widget_title']) ? 'Upcoming Webinars' : $instance['wswebinar_upcomin_widget_title']) ?></h2>
                <?php
                $webinar_rightnow_posts_set = array();
                $webinar_other_posts_set = array();
                $show_count = (empty($instance['upcoming_widget_post_count']) | ($instance['upcoming_widget_post_count'] == '0') ? 100 : $instance['upcoming_widget_post_count']);
                $date_format = get_option('date_format');
                $time_format = get_option('time_format');
                $format = $date_format . ' ' . $time_format;
                $count = 0;
                $args = array(
                    'post_type' => 'wswebinars',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'ignore_sticky_posts' => 1
                );
                $query_post = new WP_Query($args);
                $webinar_systeem = new WebinarSysteem();
                if ($query_post->have_posts()) {
                    while ($query_post->have_posts()) {
                        if ($show_count > $count) {

                            $query_post->the_post();
                            $cur_time = WebinarSysteemWebinar::get_now_in_webinar_timezone(get_the_ID());
                            $webinar_time = $webinar_systeem->get_webinar_time(get_the_ID());
                            $isRecurring = $webinar_systeem->is_recurring_webinar(get_the_ID());
                            $gener_time_occur_saved = get_post_meta(get_the_ID(), '_wswebinar_gener_time_occur', true);

                            $webinar_timezone = $webinar_systeem->getWebinarTimezone(get_the_ID());
                            $is_right_now = WebinarSysteem::isRightnow(get_the_ID());
                            $closed_webinar = get_post_meta(get_the_ID(),'_wswebinar_gener_webinar_status',true);
                            if ($isRecurring | $webinar_time > $cur_time && $closed_webinar != 'clo') {
                                $webinar_timezone = $webinar_systeem->getWebinarTimezone(get_the_ID());
                                
                                if($gener_time_occur_saved == 'recur') {
								    $next_rec_time = $webinar_systeem->getNextRecurringTime(get_the_ID());
								} else if($gener_time_occur_saved == 'jit') {	
								    $next_rec_time = $webinar_systeem->getNextJITRecurringTime(get_the_ID());
								}

                                if ($is_right_now) {
                                    // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                                    array_push($webinar_rightnow_posts_set, array('content' => "<p>" . get_the_title() . " " . ($is_right_now ? ' ' : ' ' . ($isRecurring ? date($format, $next_rec_time) : ($webinar_time != 0 ? date($format,$webinar_time) : '')) . ' (' . $webinar_timezone . ')') . " " . "<a target='_blank' href='" . get_permalink() . "'>" . ($is_right_now ? 'Join Right Now!' : 'Join Now') . "</a></p>",
                                        'title' => get_the_title(),
                                    ));
                                } else {
                                    $timeslot = '0';
                                    if($isRecurring){
                                        if ($gener_time_occur_saved == 'recur') {
										    $inc_times = WebinarSysteem::getRecurringInstancesInTime(get_the_ID());
										} else if($gener_time_occur_saved == 'jit') {
										    $inc_times = WebinarSysteem::getJITInstancesInTime(get_the_ID());
										}

                                        if (isset($inc_times[0])) {
                                            $timeslot_row = $inc_times[0];
                                            $timeslot = $timeslot_row['time'];
                                        }
                                    }
                                    // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                                    array_push($webinar_other_posts_set, array('content' => "<p>" . get_the_title() . " " . ($is_right_now ? ' ' : ' ' . ($isRecurring ? date($format, $next_rec_time) : date($format, $webinar_time)) . ' (' . $webinar_timezone . ')') . " " . "<a target='_blank' href='" . get_permalink() . "'>" . ($is_right_now ? 'Join Right Now!' : 'Join Now') . "</a></p>",
                                        'title' => get_the_title(),
                                        'timeslot' => $timeslot
                                    ));
                                }
                                ?>

                                <?php
                                $count++;
                            }
                        } else {
                            break;
                        }
                        wp_reset_query();
                    }
                }
                asort($webinar_rightnow_posts_set);
                asort($webinar_other_posts_set);
                
                foreach ($webinar_rightnow_posts_set as $post) {
                    echo esc_html($post['content']);
                }
                foreach ($webinar_other_posts_set as $post){
                    echo esc_html($post['content']);
                }
                if ($count == 0) {
                    echo ('<p>No upcoming webinars.</p>');
                }
                ?>
            </div>
        </form>
        <?php
    }

    public function form($instance) {
        ?>
        <div id="wswebinar_widget_admin">
            <p>
                <label for="upcomin_widget_title">Widget Title:</label> 
                <input class="widefat" id="upcomin_widget_title" name="<?php echo esc_attr($this->get_field_name('wswebinar_upcomin_widget_title')); ?>" type="text" value="<?php echo esc_attr(empty($instance['wswebinar_upcomin_widget_title']) ? 'Upcoming Webinars' : $instance['wswebinar_upcomin_widget_title']); ?>">
            </p>
            <p>
                <label for="upcoming_widget_post_count">Webinar Limit:</label> 
                <input class="widefat" id="upcomin_widget_title" name="<?php echo esc_attr($this->get_field_name('upcoming_widget_post_count')); ?>" type="number" max="100" min="0" value="<?php echo esc_attr(empty($instance['upcoming_widget_post_count']) ? '0' : $instance['upcoming_widget_post_count']); ?>">
            </p>

        </div>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['wswebinar_upcomin_widget_title'] = $new_instance['wswebinar_upcomin_widget_title'];
        $instance['upcoming_widget_post_count'] = $new_instance['upcoming_widget_post_count'];
        return $instance;
    }

}
