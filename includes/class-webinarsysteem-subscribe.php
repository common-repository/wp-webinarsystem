<?php

class WebinarSysteemSubscribe
{
    public static function subscribe($post_id, $email, $name)
    {
    }

    public static function subscribe_mailoctopus($post_id, $email, $name)
    {
        $webinar = WebinarSysteemWebinar::create_from_id($post_id);

        if ($webinar->get_mail_provider() != 'mailoctopus') {
            return false;
        }

        $settings = WebinarSysteemSettings::instance();

        $client = new MailOctopusSimpleClient(
            $settings->get_mailoctopus_key()
        );

        $expolde_name = WebinarSysteemSubscribe::explode_name($name);
        $fname = $expolde_name['fname'];
        $lname = $expolde_name['lname'];

        return $client->add_contact($webinar->get_mail_list_id(), $fname, $lname, $email);
    }

    public static function subscribe_mautic($post_id, $email, $name) {
        $webinar = WebinarSysteemWebinar::create_from_id($post_id);

        if ($webinar->get_mail_provider() != 'mautic') {
            return false;
        }

        $mautic = new MauticSimpleClient();
        return $mautic->subscribe($email, $name);
    }
}
