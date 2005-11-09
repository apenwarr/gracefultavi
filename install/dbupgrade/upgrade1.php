<?php

/**
 * GracefulTavi Database Upgrade
 *
 * Version: 1
 *
 * Occured in release: 1.04
 *
 * Author:
 *   Michel Emond
 *   Net Integration Technologies
 *   www.net-itech.com
 *
 * Description:
 *   This is a major database upgrade. Splits the "pages" table into "pages" and
 *   "content" in order to improve performances. At the same time, merges
 *   content from "metaphone" and "lastedit" into "pages".
 *
 *   This file is basically the php script included in the migration kit
 *   provided with the 1.04 release.
 */

set_time_limit(0);

global $DBTablePrefix, $PgTbl;

$MpTbl = $DBTablePrefix . 'metaphone';
$LeTbl = $DBTablePrefix . 'lastedit';

$PgTblOld = $DBTablePrefix . 'pages_old';
$CoTbl = $DBTablePrefix . 'content';


$rs = mysql_query("describe $CoTbl", $db->handle);
if (!$rs)
{
    /* Create, drop and rename tables */

    $qid = $db->query("alter table $PgTbl rename $PgTblOld");

    $qid = $db->query("drop table if exists $LeTbl");
    $qid = $db->query("drop table if exists $MpTbl");

    $qid = $db->query("drop table if exists $PgTbl");
    $qid = $db->query("CREATE TABLE $PgTbl (
        id                MEDIUMINT UNSIGNED  NOT NULL AUTO_INCREMENT,
        title             VARCHAR(80) BINARY  NOT NULL DEFAULT '',
        title_notbinary   VARCHAR(80)         NOT NULL DEFAULT '',
        lastversion       MEDIUMINT UNSIGNED  NOT NULL DEFAULT '1',
        lastversion_major MEDIUMINT UNSIGNED  NOT NULL DEFAULT '1',
        metaphone         VARCHAR(80) BINARY  NOT NULL DEFAULT '',
        bodylength        SMALLINT UNSIGNED   NOT NULL default '0',
        mutable           SET('off', 'on')    NOT NULL DEFAULT 'on',
        createtime        TIMESTAMP(14)       NOT NULL,
        updatetime        TIMESTAMP(14)       NOT NULL,
        PRIMARY KEY (id),
        UNIQUE title_u (title),
        INDEX title_idx (title),
        INDEX title_notbinary_idx (title_notbinary),
        INDEX lastversion_major_idx (lastversion_major),
        INDEX bodylength_idx (bodylength)
    )");

    $qid = $db->query("drop table if exists $CoTbl");
    $qid = $db->query("CREATE TABLE $CoTbl (
        page        MEDIUMINT UNSIGNED  NOT NULL,
        version     MEDIUMINT UNSIGNED  NOT NULL DEFAULT '1',
        time        TIMESTAMP(14)       NOT NULL,
        supercede   TIMESTAMP(14)       NOT NULL,
        minoredit   TINYINT             NOT NULL DEFAULT 0,
        username    VARCHAR(80)         NOT NULL,
        author      VARCHAR(80)         NOT NULL DEFAULT '',
        comment     VARCHAR(80)         NOT NULL DEFAULT '',
        body        TEXT                NOT NULL,
        PRIMARY KEY (page, version)
    )");


    /* Pages */

    $qid = $db->query("SELECT distinct title FROM $PgTblOld " .
                      "ORDER BY lower(title)");
    $pages_titles = array();
    while (($result = $db->result($qid))) {
        $pages_titles[] = $result[0];
    }
    $page_ids = array();
    foreach ($pages_titles as $page_name) {
        // last version
        $qid = $db->query("select max(version) " .
                          "from $PgTblOld " .
                          "where title='$page_name'");
        $result = $db->result($qid);
        $last_version = $result[0];

        // metaphone
        $metaphone = substr(metaphone($page_name), 0, 80);

        // body length
        $qid = $db->query("select length(body) " .
                          "from $PgTblOld " .
                          "where title='$page_name' " .
                          "and version=$last_version");
        $result = $db->result($qid);
        $body_length = $result[0];

        // create time
        $qid = $db->query("select min(time) " .
                          "from $PgTblOld " .
                          "where title='$page_name'");
        $result = $db->result($qid);
        $create_time = $result[0];

        // update time
        // last version major
        $qid = $db->query("select max(version) " .
                          "from $PgTblOld " .
                          "where title='$page_name' " .
                          "and minoredit=0");
        $result = $db->result($qid);
        if ($version = $result[0]) {
            $qid = $db->query("select time " .
                              "from $PgTblOld " .
                              "where title='$page_name' " .
                              "and version=$version");
            $result = $db->result($qid);
            $update_time = $result[0];
            $last_version_major = $version;
        } else {
            $update_time = $create_time;
            $qid = $db->query("select min(version) " .
                              "from $PgTblOld " .
                              "where title='$page_name'");
            $result = $db->result($qid);
            $last_version_major = $result[0];
        }

        // mutable
        $qid = $db->query("select count(*) " .
                          "from $PgTblOld " .
                          "where title='$page_name' " .
                          "and mutable='off'");
        $result = $db->result($qid);
        $mutable = ($result[0] == 0) ? 'on' : 'off';

        $dbname = str_replace('\\', '\\\\', $page_name);
        $dbname = str_replace('\'', '\\\'', $dbname);

        $qid = $db->query("INSERT INTO $PgTbl (title, title_notbinary, " .
                          "lastversion, lastversion_major, metaphone, " .
                          "bodylength, mutable, createtime, updatetime) " .
                          "VALUES ('$dbname', '$dbname', $last_version, " .
                          "$last_version_major, '$metaphone', $body_length, " .
                          "'$mutable', $create_time, $update_time)");

        $page_ids[$page_name] = mysql_insert_id($db->handle);
    }


    /* Content */

    foreach ($page_ids as $page_name => $page_id) {
        $qid = $db->query("select version, time, supercede, username, " .
                          "author, comment, body, minoredit " .
                          "from $PgTblOld " .
                          "where title='$page_name'");

        while (($result = $db->result($qid))) {
            $comment = str_replace("\\", "\\\\", $result[5]);
            $comment = str_replace("'", "\\'", $comment);

            $document = str_replace("\\", "\\\\", $result[6]);
            $document = str_replace("'", "\\'", $document);
            $document = str_replace("\r", "", $document);

            $qid2 = $db->query("insert into $CoTbl (page, version, time, supercede, " .
                               "username, author, comment, body, minoredit) " .
                               "values ($page_id, $result[0], $result[1], " .
                               "$result[2], '$result[3]', " .
                               "'$result[4]', '$comment', '$document', $result[7])");
        }
    }

    $qid = $db->query("drop table if exists $PgTblOld");


    /* Pages Watch / Subscribe */

    $db->query("ALTER TABLE ${DBTablePrefix}pageswatch
                RENAME ${DBTablePrefix}subscribe");

    $db->query("ALTER TABLE ${DBTablePrefix}subscribe DROP time");
}

?>
