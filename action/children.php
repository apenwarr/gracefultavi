<?php

require('parse/html.php');
require('template/children.php');
require('lib/headers.php');

// Show children of a page.
function action_children()
{
    global $page, $pagestore;

    $pg = $pagestore->page($page);
    $pg->read();
    gen_headers($pg->time);

    $children = $pagestore->getChildren($page);

    template_children(array('page'     => $page,
                            'children' => $pagestore->getChildren($page)));
}
?>
