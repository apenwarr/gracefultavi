<?php

function lastedit_check()
{
    global $pagestore;
    global $PgTbl, $LeTbl;

    // Creates the lastedit table if it doesn't exists
    $pagestore->dbh->query("CREATE TABLE IF NOT EXISTS $LeTbl ( " .
                           "page varchar(80) binary DEFAULT '' NOT NULL, " .
                           "version int(10) unsigned DEFAULT '1' NOT NULL, " .
                           "PRIMARY KEY (page, version))");

    // Make sure every page has an entry in the lastedit table.
    // If not, populates the lastedit table.
    $qid = $pagestore->dbh->query("SELECT DISTINCT title FROM $PgTbl, $LeTbl " .
                                  "WHERE $PgTbl.title = $LeTbl.page");
    $lastedit_count = mysql_num_rows($qid);

    $qid = $pagestore->dbh->query("SELECT DISTINCT title FROM $PgTbl");
    $pages_count = mysql_num_rows($qid);

    if ($lastedit_count <> $pages_count)
    {
        $list = array();

        // Make sure we get every page's last version, the next query
        // misses the pages having all their entries with minoredit.
        $qid = $pagestore->dbh->query("SELECT title, MAX(version) " .
                                      "FROM $PgTbl " .
                                      "GROUP BY title");
        while(($result = $pagestore->dbh->result($qid)))
            $list[$result[0]] = $result[1];

        // Gets the latest significant version of each page.
        $qid = $pagestore->dbh->query("SELECT title, MAX(version) " .
                                      "FROM $PgTbl " .
                                      "WHERE minoredit = 0 " .
                                      "OR LENGTH(body) <= 1 " .
                                      "GROUP BY title");
        while(($result = $pagestore->dbh->result($qid)))
            $list[$result[0]] = $result[1];

        $pagestore->dbh->query("LOCK TABLES $LeTbl WRITE");

        $pagestore->dbh->query("DELETE FROM $LeTbl");

        foreach ($list as $key => $value)
            $pagestore->dbh->query("INSERT INTO $LeTbl (page, version) " .
                                   "VALUES ('$key', $value)");

        $pagestore->dbh->query("UNLOCK TABLES");
    }
}

?>
