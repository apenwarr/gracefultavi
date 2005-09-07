<?php

function action_lock()
{
    global $dbh, $page, $pagestore, $PgTbl;

    $dbname = str_replace('\\', '\\\\', $page);
    $dbname = str_replace('\'', '\\\'', $dbname);

    $qry = "SELECT mutable FROM $PgTbl WHERE title='$dbname'";
    $qid = $pagestore->dbh->query($qry);
    $result = $pagestore->dbh->result($qid);

    if ($result[0]) {
        $mutable = ($result[0] == 'on') ? 'off' : 'on';
        $qry = "UPDATE $PgTbl SET mutable='$mutable' WHERE title='$dbname'";
        $pagestore->dbh->query($qry);
    }
}
?>
