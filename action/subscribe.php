<?php
require('template/subscribe.php');

// Toggle page subscription for a user
function action_subscribe()
{
    global $page, $pagestore, $UserName;

    $pagestore->lock();                 // Ensure atomicity.

    $pg = $pagestore->page($page);
    $pg->toggleSubscribe($UserName);

    template_subscribe(array('page' => $page));

    $pagestore->unlock();               // End "transaction".
}
?>
