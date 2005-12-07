<?php

function do_revert(&$ver1, &$ver2)
{
    global $CoTbl, $page, $pagestore, $PgTbl, $REMOTE_ADDR, $UserName;

    $pg = $pagestore->page($page);
    $pg->read();
    if (!$pg->mutable) { return; }

    $db = $pagestore->dbh;

    $db_title = str_replace('\\', '\\\\', $page);
    $db_title = str_replace('\'', '\\\'', $db_title);

    // page id
    $rs = $db->query("SELECT id " .
                     "FROM $PgTbl ".
                     "WHERE title='$db_title'");
    if (!($row = $db->result($rs))) { return; }
    $page_id = $row[0];

    // version of the spam
    $rs = $db->query("SELECT max(version) " .
                     "FROM $CoTbl ".
                     "WHERE page=$page_id " .
                     "AND minoredit=0");
    if (!($row = $db->result($rs)) || !$row[0]) { return; }
    $spam_version = $row[0];

    // version before the spam
    $rs = $db->query("SELECT max(version) " .
                     "FROM $CoTbl ".
                     "WHERE page=$page_id " .
                     "AND version<$spam_version");
    if (!($row = $db->result($rs)) || !$row[0]) { return; }
    $use_version = $row[0];

    // version, time, and length of the last major edit before the spam
    $rs = $db->query("SELECT max(version) " .
                     "FROM $CoTbl ".
                     "WHERE page=$page_id " .
                     "AND minoredit=0 " .
                     "AND version<$spam_version");
    if (!($row = $db->result($rs))) { return; }
    if (!($last_major_version = $row[0]))
    {
        $rs = $db->query("SELECT min(version) " .
                         "FROM $CoTbl ".
                         "WHERE page=$page_id");
        if (!($row = $db->result($rs)) || !$row[0]) { return; }
        $last_major_version = $row[0];
    }
    $rs = $db->query("SELECT time, length(body) " .
                     "FROM $CoTbl ".
                     "WHERE page=$page_id " .
                     "AND version=$last_major_version");
    if (!($row = $db->result($rs))) { return; }
    $last_major_time = $row[0];
    $last_major_length = $row[1];

    $db->query("UPDATE $CoTbl SET " .
               "time=time, " .
               "supercede=supercede, " .
               "minoredit=1 " .
               "WHERE page=$page_id " .
               "AND version=$spam_version");

    $db->query("UPDATE $PgTbl SET " .
               "lastversion_major=$last_major_version, " .
               "bodylength=$last_major_length, " .
               "createtime=createtime, " .
               "updatetime=$last_major_time " .
               "WHERE id=$page_id");

    $pg->version = $use_version;
    $pg->read();
    $content = $pg->text;
    $content = str_replace("\\", "\\\\", $content);
    $content = str_replace("'", "\\'", $content);
    $content = str_replace("\r", "", $content);

    $pg = $pagestore->page($page);
    $pg->read();
    $pg->text = $content;
    $pg->hostname = gethostbyaddr($REMOTE_ADDR);
    $pg->username = $UserName;
    $pg->comment = 'Spam revert to version ' . $use_version;
    $pg->version++;
    $pg->write(1);

    $ver1 = $spam_version;
    $ver2 = $pg->version;
}

// Revert a page to a selected version and mark subsequent versions as minor
// edits.
function action_revert()
{
    global $HistMax, $page, $pagestore, $ScriptName, $UserName, $UseSpamRevert;

    $ver1 = 0;
    $ver2 = 0;

    if ($UseSpamRevert && $UserName)
    {
        $pagestore->lock();
        do_revert($ver1, $ver2);
        $pagestore->unlock();
    }

    $history_url = $ScriptName . '?action=history&page=' . urlencode($page);
    if ($ver1 > 0 && $ver2 > 0)
    {
        $history_url .= '&ver2=' . $ver2 . '&ver1=' . $ver1;
        if (($ver2 - $ver1 + 1) > $HistMax)
        {
            $history_url .= '&full=1';
        }
    }

    header('Location: ' . $history_url);
}

?>
