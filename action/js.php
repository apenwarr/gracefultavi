<?php
// $Id: js.php,v 1.3 2004/08/26 22:30:36 mich Exp $

// This function emits the current template's stylesheet.
function action_js()
{
    global $HTTP_GET_VARS;

    if ($HTTP_GET_VARS['file'] == "tabsort")
        require('js/tabsort.js');
    else if ($HTTP_GET_VARS['file'] == "script")
        require('js/script.js');
}
