<?php

// see if we have a right now time slot
$webinar = empty($webinar_id)
    ? WebinarSysteemWebinar::create_from_id($post->ID)
    : WebinarSysteemWebinar::create_from_id($webinar_id);

if ($webinar == null) {
    return;
}

if ($webinar->is_right_now()) {
    echo '<input type="hidden" value="1" name="right_now">';
    return;
}

?>
<div class="row">
    <div class="col-sm-12">
        <select class="form-control forminputs" name="session_days" id="session_days" disabled></select>
        <select class="form-control forminputs" name="session_datetime" id="session_datetime" disabled></select>
    </div>
</div>

<script>
    function setSelectOptions(select, values) {
        select.empty();
        jQuery.each(values, function(val, text) {
            select.append(new Option(text, val));
        });
    }

    function createMomentFromSession(session, timezoneOffset, locale) {
        const timeInUTC = session.date - (timezoneOffset * 60 * 60);
        var ret = moment(timeInUTC * 1000)
            .utcOffset(timezoneOffset)
            .local();

        ret.locale(locale);

        return ret;
    }

    function formatDate(date, locale) {
        return locale.indexOf('en') !== -1
            ? date.format('dddd (MMMM Do)')
            : date.format('dddd (MMMM DD)');
    }

    function load(data) {
        var sessions = data.sessions;
        var locale = data.locale;
        var timezoneOffset = data.timezone_offset;

        // get all the days
        var days = sessions.reduce(function (acc, session) {
            var dt = createMomentFromSession(session, timezoneOffset, locale);
            var date = dt.format('YYYY-MM-DD');
            acc[date] = formatDate(dt, locale); // dt.format('dddd (D MMMM)');
            return acc;
        }, {});

        function selectDay(selectedDate) {
            // find all the sessions with this day
            var hours = sessions
                .filter(function (session) {
                    var dt = createMomentFromSession(session, timezoneOffset, locale);
                    var date = dt.format('YYYY-MM-DD');
                    return selectedDate === date;
                })
                .reduce((acc, session) => {
                    var dt = createMomentFromSession(session, timezoneOffset, locale);
                    var time = dt.format();
                    acc[session.date] = dt.format('LT');
                    return acc;
                }, {})

            setSelectOptions(timesSelect, hours)
        }

        // remove current options
        var daysSelect = jQuery('#session_days');
        var timesSelect = jQuery('#session_datetime');

        daysSelect.prop('disabled', false);
        timesSelect.prop('disabled', false);

        daysSelect.on('change', function (val) {
            var selectedDate = daysSelect.val();
            selectDay(selectedDate);
        });

        setSelectOptions(daysSelect, days);

        if (sessions.length) {
            var firstDate = createMomentFromSession(sessions[0], timezoneOffset, locale);
            selectDay(firstDate.format('YYYY-MM-DD'));
        }
    }

    jQuery(document).on('ready', function () {
        jQuery
            .ajax({
                url: '<?php echo esc_url(admin_url('admin-ajax.php')) ?>',
                data: {
                action: 'wpws-get-upcoming-sessions',
                webinar_id: <?php echo esc_attr($webinar->id) ?>,
                security: '<?php echo esc_attr(wp_create_nonce(WebinarSysteemJS::get_nonce_secret())) ?>'
                },
                type: 'POST'
            })
            .done(function (res) {
                if (!res.success) {
                    return;
                }

                load(res.data);
            })
            .error(function (jqXHR, text, status) {});
    });
</script>
<?php
