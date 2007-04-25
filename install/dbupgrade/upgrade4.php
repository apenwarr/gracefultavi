<?php

/**
 * GracefulTavi Database Upgrade
 *
 * Version 4
 *
 * Occured in release: 1.08
 *
 * Author:
 *   Michel Emond
 *   Net Integration Technologies
 *   www.net-itech.com
 *
 * Description:
 *   Adds a "body" field in the "pages" table to implement a faster and more
 *   efficient search, and set this new field to the most recent value from the
 *   "content" table.
 */

global $PgTbl, $CoTbl;

$rs = mysql_query("SELECT body FROM $PgTbl", $db->handle);
if (!$rs)
{
    $db->query("ALTER TABLE $PgTbl
                ADD body TEXT NOT NULL
                AFTER updatetime");
}

$rs = $db->query("SELECT p.id, c.body " .
                 "FROM $PgTbl p, $CoTbl c " .
                 "WHERE p.id = c.page AND p.lastversion = c.version");
$bodies = array();
while (($row = $db->result($rs))) {
    $bodies[$row[0]] = $row[1];
}
foreach ($bodies as $id => $body) {
    $body = addslashes($body);
    $db->query("UPDATE $PgTbl " .
               "SET body = '$body', createtime = createtime, updatetime = updatetime " .
               "WHERE id = $id");
}

?>
