<?php

require('parse/macros.php');
require('parse/html.php');

// Execute a macro directly from the URL.
function action_macro()
{
    global $args, $macro, $page, $ViewMacroEngine;

    if (!empty($ViewMacroEngine[$macro])) {
        print $ViewMacroEngine[$macro]->parse($args, $page);
    }
}
?>
