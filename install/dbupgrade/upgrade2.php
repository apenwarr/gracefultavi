<?php

/**
 * GracefulTavi Database Upgrade
 *
 * Version 2
 *
 * Occured in release: 1.05
 *
 * Author:
 *   Michel Emond
 *   Net Integration Technologies
 *   www.net-itech.com
 *
 * Description:
 *   Adds a table for the new WikiPoll macro.
 */

global $DBTablePrefix;

$poll_table = $DBTablePrefix.'poll';

$rs = mysql_query("DESCRIBE $poll_table", $db->handle);
if (!$rs)
{
    $db->query("DROP TABLE IF EXISTS $poll_table");
    $db->query("CREATE TABLE $poll_table (
                id INT(10) unsigned NOT NULL AUTO_INCREMENT,
                title VARCHAR(200) NOT NULL DEFAULT '',
                author VARCHAR(80) NOT NULL DEFAULT '',
                choice VARCHAR(200) NOT NULL DEFAULT '',
                PRIMARY KEY (id)
                )");
}

?>
