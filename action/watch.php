<?php
// $Id: save.php,v 1.7 2002/01/07 16:28:32 smoonen Exp $

require('template/watch.php');

// Toggle page watch for a user
function action_watch()
{
    global $page, $pagestore, $UserName;

    $pagestore->lock();                 // Ensure atomicity.

    $pg = $pagestore->page($page);
    $pg->toggleWatch($UserName);

    template_watch(array('page' => $page));

    $pagestore->unlock();               // End "transaction".
}
?>
