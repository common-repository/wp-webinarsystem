<?php

class WebinarSysteemPermissions {
    public static function user_is_team_member() {
        return current_user_can('manage_options');
    }

    public static function can_manage_questions() {
        return current_user_can('_wswebinar_managequestions');
    }

    public static function can_manage_attendees() {
        return current_user_can('_wswebinar_managesubscribers');
    }

    public static function can_manage_chats() {
        return current_user_can('_wswebinar_managechatlogs');
    }

    public static function can_manage_settings() {
        return current_user_can('_wswebinar_webinarsettings');
    }

    public static function can_create_webinars() {
        return current_user_can('_wswebinar_createwebinars');
    }

    public static function set_role_permissions()
    {
        global $wp_roles;
        $roles = $wp_roles->get_names();
        foreach ($roles as $slug => $name) {
            $role = get_role($slug);
            $createWebinars = $slug == 'administrator'
                ? 'on'
                : get_option('_wswebinar_createwebinars_' . $slug);

            $manageSubscribers = $slug == 'administrator'
                ? 'on'
                : get_option('_wswebinar_managesubscribers_' . $slug);

            $accessControlbar = $slug == 'administrator'
                ? 'on'
                : get_option('_wswebinar_accesscontrolbar_' . $slug);

            $manageQuestions = $slug == 'administrator'
                ? 'on'
                : get_option('_wswebinar_managequestions_' . $slug);

            $manageChatlogs = $slug == 'administrator'
                ? 'on'
                : get_option('_wswebinar_managechatlogs_' . $slug);

            $manageWebinarSettings = $slug == 'administrator'
                ? 'on'
                : get_option('_wswebinar_webinarsettings_' . $slug);

            //Add caps
            if ($createWebinars == "on") {
                $role->add_cap('_wswebinar_createwebinars');
                $role->add_cap('edit_wswebinar');
                $role->add_cap('delete_wswebinar');
                $role->add_cap('read_wswebinar');
                $role->add_cap('publish_wswebinars');
                $role->add_cap('edit_wswebinars');
                $role->add_cap('edit_others_wswebinars');
                $role->add_cap('read_private_wswebinars');
                $role->add_cap('delete_wswebinars');
            } else {
                $role->remove_cap('_wswebinar_createwebinars');
                $role->remove_cap('edit_wswebinar');
                $role->remove_cap('delete_wswebinar');
                $role->remove_cap('read_wswebinar');
                $role->remove_cap('publish_wswebinars');
                $role->remove_cap('edit_wswebinars');
                $role->remove_cap('edit_others_wswebinars');
                $role->remove_cap('read_private_wswebinars');
                $role->remove_cap('delete_wswebinars');
            }

            $manageSubscribers == "on"
                ? $role->add_cap('_wswebinar_managesubscribers')
                : $role->remove_cap('_wswebinar_managesubscribers');

            $accessControlbar == "on"
                ? $role->add_cap('_wswebinar_accesscbar')
                : $role->remove_cap('_wswebinar_accesscbar');

            $manageQuestions == "on"
                ? $role->add_cap('_wswebinar_managequestions')
                : $role->remove_cap('_wswebinar_managequestions');

            $manageChatlogs == "on"
                ? $role->add_cap('_wswebinar_managechatlogs')
                : $role->remove_cap('_wswebinar_managechatlogs');

            $manageWebinarSettings == "on"
                ? $role->add_cap('_wswebinar_webinarsettings')
                : $role->remove_cap('_wswebinar_webinarsettings');
        }
    }
}