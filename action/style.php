<?php

// This function emits the current template's stylesheet.
function action_style()
{
    global $StyleSheetOverride, $csstype;

    header("Content-type: text/css");

    require('template/wiki.css');

    if ($csstype == 'print') {
        require('template/wikiprint.css');
    }

    if ($StyleSheetOverride) {
        require($StyleSheetOverride);
    }
}
