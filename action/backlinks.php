<?php

require('parse/html.php');
require('template/backlinks.php');
require('lib/headers.php');

// Show backlinks edit form.
function action_backlinks()
{
    global $page, $pagestore, $UserName;

    $pg = $pagestore->page($page);
    $pg->read();
    gen_headers($pg->time);

    template_backlinks(array(
        'page'      => $page,
        'backlinks' => $pagestore->getBacklinks($page),
        'haschildren' => $pagestore->getChildren($page) ? 1 : 0,
        'parents'   => $pagestore->getParents($page),
        'timestamp' => $pg->time,
        'edituser'  => $pg->username,
        'editver'   => ($UserName && $pg->mutable) ? 0 : -1
    ));
}
?>
