<?php

function action_lock()
{
    global $pagestore, $page, $dbh, $PgTbl;

    $pg = $pagestore->page($page);

    $sql = "UPDATE $PgTbl SET mutable='" . ( ($pg->mutable=="on") ? "off" : "on") . "' WHERE title='$page'";

    $pagestore->dbh->query($sql);
}

?>
