<?php
    $crud = \MyAdmin\App::variables()->request['crud'];
    function_requirements($crud);
    if (function_exists($crud)) {
        call_user_func($crud);
    }
