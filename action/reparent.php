<?php
// $Id: save.php,v 1.7 2002/01/07 16:28:32 smoonen Exp $

require('template/reparent.php');

// Commit a reparent to the database.
function action_reparent()
{
    global $page, $pagestore, $parents;

    $pagestore->lock();                 // Ensure atomicity.

    $pagestore->reparent($page, $parents);

    template_reparent(array('page' => $page));

    $pagestore->unlock();               // End "transaction".
}
?>
