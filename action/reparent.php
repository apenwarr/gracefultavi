<?php
// $Id: save.php,v 1.7 2002/01/07 16:28:32 smoonen Exp $

require('template/reparent.php');

// Commit a reparent to the database.
function action_reparent()
{
    global $page, $pagestore, $HTTP_POST_VARS;

    if (isset($HTTP_POST_VARS['parents'])) $parents = $HTTP_POST_VARS['parents'];

    $pagestore->lock();                 // Ensure atomicity.

    $pagestore->reparent($page, $parents);

    $pagestore->unlock();               // End "transaction".

    template_reparent(array('page' => $page));
}
?>
