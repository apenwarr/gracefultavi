<?php

require('parse/html.php');
require('template/children.php');
require('lib/headers.php');

// Show children of a page.
function action_children()
{
    global $page, $pagestore, $UserName;

    $pg = $pagestore->page($page);
    $pg->read();
    gen_headers($pg->time);

    template_children(array(
        'page'     => $page,
        'children' => $pagestore->getChildren($page),
        'timestamp' => $pg->time,
        'edituser'  => $pg->username,
        'editver'   => ($UserName && $pg->mutable) ? 0 : -1
    ));
}
?>
