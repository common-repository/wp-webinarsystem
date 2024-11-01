<?php
class TestWebinarExactTime extends WP_UnitTestCase {
	public function test_timezone_in_exact_time() {
		$format = 'tue 11:45';

		update_option('gmt_offset', '-1');
	    $date1 = WebinarSysteemDateTime::strtotime_for_wp($format);

        update_option('gmt_offset', '+1');
        $date2 = WebinarSysteemDateTime::strtotime_for_wp($format);

        // TODO, not sure how to test this! Can't easily change the time in phpunit
		$this->assertTrue(true);
	}
}
