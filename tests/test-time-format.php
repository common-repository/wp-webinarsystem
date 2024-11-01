<?php
class TestsTimeFormat extends WP_UnitTestCase {
    public function test_time_format() {
        $this->assertEquals(
            WebinarSysteemTimeFormat::seconds_to_human(0),
            '-');

        $this->assertEquals(
            WebinarSysteemTimeFormat::seconds_to_human(1),
            '1 minute');

        $this->assertEquals(
            WebinarSysteemTimeFormat::seconds_to_human(30),
            '1 minute');

        $this->assertEquals(
            WebinarSysteemTimeFormat::seconds_to_human(60),
            '1 minute');

        $this->assertEquals(
            WebinarSysteemTimeFormat::seconds_to_human(100),
            '1 minute');

        $this->assertEquals(
            WebinarSysteemTimeFormat::seconds_to_human(120),
            '2 minutes');

        $this->assertEquals(
            WebinarSysteemTimeFormat::seconds_to_human(179),
            '2 minutes');

        $this->assertEquals(
            WebinarSysteemTimeFormat::seconds_to_human(180),
            '3 minutes');

        $this->assertEquals(
            WebinarSysteemTimeFormat::seconds_to_human(60 * 60),
            '1 hour');

        $this->assertEquals(
            WebinarSysteemTimeFormat::seconds_to_human(60 * 60 * 4),
            '4 hours');
    }
}
