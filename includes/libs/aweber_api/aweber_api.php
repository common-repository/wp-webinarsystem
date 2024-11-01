<?php

if (class_exists('WSAWeberAPI')) {
    // trigger_error("Duplicate: Another AWeberAPI client library is already in scope.", E_USER_WARNING);
    // Commented out for stop conflicting among the wswebinar system and Aweber web forms plugin.
}
else {
    require_once('aweber.php');
}
