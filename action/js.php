<?php

// This function emits the current template's stylesheet.
function action_js()
{
    global $HTTP_GET_VARS;

    if ($HTTP_GET_VARS['file'] == "tabsort")
        require('js/tabsort.js');
    else if ($HTTP_GET_VARS['file'] == "script")
        require('js/script.js');
    else if ($HTTP_GET_VARS['file'] == "tablesort")
        require('js/tablesort.js');
    else if ($HTTP_GET_VARS['file'] == "common")
        require('js/common.js');
}
