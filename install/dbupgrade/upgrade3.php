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
 *   Adds a new field in the "remote_pages" table and creates the "version"
 *   table for the new versioning system.
 */

global $RemTbl, $VeTbl;

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
    $db->query("CREATE TABLE $VeTbl (
                version TINYINT(3) UNSIGNED DEFAULT 0
                )");
}

?>
