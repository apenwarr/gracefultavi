<?php

require('template/subscriptions.php');

// Allows page subscriptions management for a user
function action_subscriptions()
{
    global $page, $pagestore, $PrefsScript, $subscribed_pages, $UserName;

    $subscriptions = $pagestore->getSubscribedPages($UserName);

    if (isset($subscribed_pages)) {
        $pages = array_diff($subscriptions, $subscribed_pages);

        $pagestore->lock();                 // Ensure atomicity.

        $pagestore->unsubscribePages($UserName, $pages);

        $pagestore->unlock();               // End "transaction".

        header("Location: $PrefsScript");
    } else {
        template_subscriptions(array('subscriptions' => $subscriptions));
    }
}
?>
