<?php

/*
 * 
 * Importing class files
 * 
 */

require_once('libs/SimpleWebClient/Client.php');

// set the table names
require 'utils/class-webinarsysteem-tables.php';
require 'class-webinar-press.php';
require 'class-webinarsysteem-post-type-base.php';
require 'class-webinarsysteem-log.php';
require 'class-webinarsysteem.php';
require 'class-webinarsysteem-helper-functions.php';
require 'class-webinarsysteem-dbmigrations.php';
require 'class-webinarsysteemhosts.php';
require 'class-webinarsysteem-emails.php';
require 'class-webinarsysteemattendees.php';
require 'class-webinarsysteemviews.php';
require 'class-webinarsysteem-questions.php';
require 'class-webinarsysteem-subscribe.php';
require 'class-webinarsysteem-ajax.php';
require 'class-webinarsysteem-woocommerce-integration.php';
require 'class-webinarsysteem-mailinglist-integrations.php';
require 'class-webinarsysteem-promotional-notices.php';
require 'class-webinarsysteem-shortcodes.php';
require 'class-woocommerce-custom-webinar-product.php';
require 'class-webinarsysteem-userpages.php';
require 'class-webinarsysteem-requirements.php';
require 'class-webinarsysteem-cache-webinar.php';
require 'class-webinarsysteem-webhooks.php';
require 'class-webinarsysteem-js.php';
require 'class-webinarsysteem-registration-widget.php';
require 'class-webinarsysteem-webinar.php';
require 'class-webinarsysteem-datetime.php';
require 'class-webinarsysteem-settings.php';
require 'class-webinarsysteem-confirmation-token-parser.php';
require 'class-webinarsysteem-base64.php';
require 'class-webinarsysteem-actions.php';
require 'class-webinarsysteem-sessions.php';
require 'class-webinarsysteem-pages.php';
require 'class-webinarsysteem-mediaserver.php';
require 'class-webinarsysteem-webinar-messages.php';
require 'class-webinarsysteem-exports.php';
require 'class-webinarsysteem-permissions.php';
require 'class-webinarsysteem-utils.php';
require 'class-webinarsysteem-hashes.php';
require 'class-webinarsysteem-unsubscribe.php';
require 'class-webinarsysteem-running-processes.php';
require 'class-webinarsysteem-text-override.php';
require 'class-webinarsysteem-webinar-session.php';
require 'class-webinarsysteem-polls.php';
require 'class-webinarsysteem-db.php';

// utils
require 'utils/class-webinarsysteem-time-format.php';
require 'utils/class-webinarsysteem-cron.php';
require 'utils/class-webinarsysteem-templates.php';

/*
 * Import short codes
 */

require 'shortcodes/class-webinarsysteem-confirmation-shortcodes.php';

/*
 * Importing Widget Classes
 */

require 'widgets/class-webinarsysteem-upcoming-widget.php';
require 'widgets/class-webinarsysteem-past-widget.php';

/*
 * 
 * Importing template files
 * 
 */

require 'templates/template-video-source.php';

/*
 * 
 * Importing library files
 * 
 */

require 'libs/aweber_api/aweber_api.php';

if (!class_exists('EM_Account')) {
    require_once 'libs/enormail/rest.php';
    require_once 'libs/enormail/base.php';
    require_once 'libs/enormail/lists.php';
    require_once 'libs/enormail/account.php';
    require_once 'libs/enormail/contacts.php';
}

if (!class_exists('GetResponseSimpleClient')) {
    require_once ('libs/Getresponse/SimpleClient.php');
}

require_once 'libs/Mailchimp/SimpleClient.php';
require_once 'libs/Mailrelay/SimpleClient.php';
require_once 'libs/MailerLite/SimpleClient.php';
require_once 'libs/MailOctopus/SimpleClient.php';
require_once 'libs/Mautic/SimpleClient.php';

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if (is_plugin_active('mailpoet/mailpoet.php') && !class_exists('MailPoet\API\API'))
    require_once plugin_dir_path(__FILE__) . '../../mailpoet/mailpoet.php';

if (!class_exists('WPWS_ActiveCampaign'))
    require_once 'libs/activecampaign/WPWSActiveCampaign.class.php';

if (!class_exists('WP_GetDrip_API')) {
    require_once 'libs/Drip/class-wp-getdrip-api.php';
}

// ConvertKit
require_once 'libs/ConvertKit/base.php';
require_once 'libs/ConvertKit/the_interface.php';
require_once 'libs/ConvertKit/forms.php';

// System snapshot
if (!class_exists('WPWS_System_Snapshot_Report')) {
    require_once 'libs/system-snapshot-report.php';
}