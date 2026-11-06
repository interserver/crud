<?php
    // mirror of MyAdmin's include/ajax/crud.php: function_exists() alone also let php
    // built-ins through (crud=phpinfo) and ignored admin-only page registrations
    $crud = \MyAdmin\App::variables()->request['crud'] ?? '';
    if (!is_string($crud) || $crud === '') {
        http_response_code(400);
        return;
    }
    function_requirements($crud);
    if (class_exists('\MyAdmin\Security\PageDispatchGuard')) {
        $allowed = \MyAdmin\Security\PageDispatchGuard::allows($crud);
    } else {
        $allowed = in_array(strtolower($crud), get_defined_functions()['user'], true);
    }
    if (!$allowed) {
        http_response_code(403);
        return;
    }
    call_user_func($crud);
