<?php

// This function emits the current template's stylesheet.
function action_style()
{
    global $StyleSheetOverride;

    header("Content-type: text/css");

    if ($StyleSheetOverride)
        require($StyleSheetOverride);
    else
        require('template/wiki.css');
}
