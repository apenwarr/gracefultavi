<?php
// $Id: js.php,v 1.1 2004/08/26 20:44:49 kjrose Exp $

// This function emits the current template's stylesheet.
function action_style()
{
    global $HTTP_GET_VARS;

    if ($HTTP_GET_VARS('file') == "tabsort")
        require('js/tabsort.js');
    else if ($HTTP_GET_VARS('file') == "script")
        require('js/script.js');
}
