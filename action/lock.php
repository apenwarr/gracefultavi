<?php

function action_lock()
{
    global $page, $pagestore, $PgTbl;

    $dbname = str_replace('\\', '\\\\', $page);
    $dbname = str_replace('\'', '\\\'', $dbname);

    $qry = "SELECT attributes FROM $PgTbl WHERE title='$dbname'";
    $qid = $pagestore->dbh->query($qry);
    $result = $pagestore->dbh->result($qid);

    if ($result[0]) {
        $qry = "UPDATE $PgTbl SET attributes=".($result[0]^MUTABLE_ATTR)." ".
               "WHERE title='$dbname'";
        $pagestore->dbh->query($qry);
    }
}
?>
