<?php

function check_db_version($ver)
{
    $cur_ver = get_db_version();
    if ($cur_ver < $ver)
        db_upgrade($cur_ver, $ver);
}

function get_db_version()
{
    global $CoTbl, $db, $DBTablePrefix, $VeTbl;

    // try using the version table, new since version 3
    if ($VeTbl) {
        $rs = mysql_query("select version from $VeTbl", $db->handle);
        if ($rs) {
            $row = mysql_fetch_assoc($rs);
            if ($row && is_numeric($row['version'])) {
                return $row['version'];
            }
        }
    }

    // check for the content table, new since version 1
    if (!$CoTbl) { return 0; }
    $rs = mysql_query("describe $CoTbl", $db->handle);
    if (!$rs) { return 0; }

    // check for the table of the WikiPoll macro, new since version 2
    $rs = mysql_query("describe ${DBTablePrefix}poll", $db->handle);
    if (!$rs) { return 1; }

    // the db is in the state right before the versioning system was implemented
    return 2;
}

function db_upgrade($cur_ver, $ver)
{
    global $db, $VeTbl, $WorkingDirectory;

    if (!$WorkingDirectory) { $WorkingDirectory = "."; }

    for ($i = $cur_ver+1; $i <= $ver; $i++)
    {
        $file = "$WorkingDirectory/install/dbupgrade/upgrade$i.php";
        if (file_exists($file))
            require($file);
    }

    mysql_query("delete from $VeTbl", $db->handle);
    mysql_query("insert into $VeTbl values ($ver)", $db->handle);

    #TODO
    # delete from vetbl
    # insert into vetbl
}

?>
