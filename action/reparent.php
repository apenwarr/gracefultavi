<?php

require('template/reparent.php');

// Commit a reparent to the database.
function action_reparent()
{
    global $HTTP_POST_VARS, $page, $pagestore, $UserName;

    if ($UserName)
    {
        if (isset($HTTP_POST_VARS['parents'])) $parents = $HTTP_POST_VARS['parents'];

        $pagestore->lock();                 // Ensure atomicity.

        $pagestore->reparent($page, $parents);

        $pagestore->unlock();               // End "transaction".
    }

    template_reparent(array('page' => $page));
}
?>
