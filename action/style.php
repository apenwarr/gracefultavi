<?php

require('lib/headers.php');

// This function emits the current template's stylesheet.
function action_style()
{
    global $StyleSheetOverride, $csstype;

    header("Content-type: text/css");
    static_cache_headers();

    if ($csstype == 'print') {
        require('template/wikiprint.css');
    } else {
	require('template/wiki.css');
    }

    if ($StyleSheetOverride) {
        require($StyleSheetOverride);
    }
}
