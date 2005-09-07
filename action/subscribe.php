<?php

require('template/subscribe.php');

// Toggle page subscription for a user
function action_subscribe()
{
    global $EnableSubscriptions, $page, $pagestore, $UserName;

    if ($EnableSubscriptions) {
        $pagestore->lock();                 // Ensure atomicity.

        $pg = $pagestore->page($page);
        $pg->toggleSubscribe($UserName);

        $pagestore->unlock();               // End "transaction".
    }

    template_subscribe(array('page' => $page));
}
?>
