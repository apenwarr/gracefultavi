<?php

/**
 * GracefulTavi Database Upgrade
 *
 * Version 3
 *
 * Occured in release: 1.06
 *
 * Author:
 *   Michel Emond
 *   Net Integration Technologies
 *   www.net-itech.com
 *
 * Description:
 *   Adds a new field in the "remote_pages" table.
 *   Creates the "version" table for the new database versioning system.
 *   Turns the "mutable" field into "attributes" so it can hold multiple flags.
 *   Current flags are: "mutable" and "template".
 */

global $PgTbl, $RemTbl, $VeTbl;

$rs = mysql_query("SELECT restricted FROM $RemTbl", $db->handle);
if (!$rs)
{
    $db->query("ALTER TABLE $RemTbl
                ADD restricted TINYINT(1) NOT NULL DEFAULT 0
                AFTER site");
}

$rs = mysql_query("SELECT version FROM $VeTbl", $db->handle);
if (!$rs)
{
    $db->query("DROP TABLE IF EXISTS $VeTbl");
    $db->query("CREATE TABLE $VeTbl
                (version TINYINT(3) UNSIGNED DEFAULT 0)");
}

$rs = mysql_query("SELECT attributes FROM $PgTbl", $db->handle);
if (!$rs)
{
    // the default value of attributes is 1: bit 1 = mutable
    $db->query("ALTER TABLE $PgTbl
                ADD attributes TINYINT UNSIGNED NOT NULL DEFAULT 1
                AFTER mutable");

    $db->query("UPDATE $PgTbl
                SET attributes = 0
                WHERE mutable = 'off'");

    $db->query("ALTER TABLE $PgTbl
                DROP mutable");
}

?>
