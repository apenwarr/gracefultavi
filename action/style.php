<?php
// $Id: style.php,v 1.1.1.1 2003/03/15 03:53:58 apenwarr Exp $

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
