<?php

// This function emits the current template's stylesheet.
function action_style()
{
    global $StyleSheetOverride;

    header("Content-type: text/css");

    require('template/wiki.css');

    if ($StyleSheetOverride)
        require($StyleSheetOverride);
}
