<?php
// $Id: page.php,v 1.3 2001/11/30 22:10:15 toph Exp $

// Abstractor to read and write wiki pages.
class WikiPage
{
    var $name = '';                       // Name of page.
    var $dbname = '';                     // Name used in DB queries.
    var $text = '';                       // Page's text in wiki markup form.
    var $time = '';                       // Page's modification time.
    var $hostname = '';                   // Hostname of last editor.
    var $username = '';                   // Username of last editor.
    var $comment  = '';                   // Description of last edit.
    var $version = -1;                    // Version number of page.
    var $mutable = 1;                     // Whether page may be edited.
    var $exists = 0;                      // Whether page already exists.
    var $db;                              // Database object.

    function WikiPage($db_, $name_ = '')
    {
        $this->db = $db_;
        $this->name = $name_;
        $this->dbname = str_replace('\\', '\\\\', $name_);
        $this->dbname = str_replace('\'', '\\\'', $this->dbname);
    }

    // Check whether a page exists.
    // Returns: nonzero if page exists in database.
    function exists()
    {
        global $PgTbl, $LeTbl;

        $qid = $this->db->query("SELECT LENGTH(body) " .
                                "FROM $PgTbl, $LeTbl " .
                                "WHERE $PgTbl.title = '$this->dbname' " .
                                "AND $PgTbl.title = $LeTbl.page ".
                                "AND $PgTbl.version = $LeTbl.version");

/* old version
        $qid = $this->db->query("SELECT LENGTH(body) " .
                                "FROM $PgTbl " .
                                "WHERE title = '$this->dbname' " .
                                "ORDER BY version DESC " .
                                "LIMIT 1");
*/

        return !!(($result = $this->db->result($qid)) && $result[0]>1);

/* older version
        $qid = $this->db->query("SELECT MAX(version) FROM $PgTbl " .
                                "WHERE title='$this->dbname'");

        return !!(($result = $this->db->result($qid)) && $result[0]);
*/
    }

    // Read in a page's contents.
    // Returns: contents of the page.
    function read()
    {
        global $PgTbl;

        $query = "SELECT title, time, author, body, mutable, version, " .
                 "username, comment " .
                 "FROM $PgTbl WHERE title='$this->dbname' ";

        if($this->version != -1)
            $query = $query . "AND version = '$this->version'";
        else
            $query = $query . "ORDER BY version DESC";

        $qid = $this->db->query($query);

        if(!($result = $this->db->result($qid)))
            return "";

        $this->time     = $result[1];
        $this->hostname = $result[2];
        $this->exists   = 1;
        $this->version  = $result[5];
        $this->mutable  = ($result[4] == 'on');
        $this->username = $result[6];
        $this->text     = $result[3];
        $this->comment  = $result[7];

        return $this->text;
    }

    // Write out a page's contents.
    // Note: caller is responsible for performing locking.
    // Note: it is assumed that the 'time' member actually contains the
    //       modification-time for the *previous* version.  It is expected that
    //       the previous version will have been read into the same object.
    //       Yes, this is a tiny kludge. :-)
    function write($minoredit = 0)
    {
        global $PgTbl, $MpTbl, $LeTbl;

        // Ensure a leading and trailing spaces free text but force a new line
        // at the end.
        $this->text = trim($this->text) . "\n";

        if (strlen($this->text) <= 1)
            $insertMinorEdit = 0;         // minor edit is always disabled if body is empty
        else
            $insertMinorEdit = $minoredit ? 1 : 0;

        if ($insertMinorEdit && !trim($this->comment))
            $this->comment = 'Minor edit';

        $this->db->query("INSERT INTO $PgTbl (title, version, time, supercede, " .
                         "mutable, username, author, comment, body, minoredit) " .
                         "VALUES('$this->dbname', $this->version, NULL, NULL, '" .
                         ($this->mutable ? 'on' : 'off') . "', " .
                         "'$this->username', '$this->hostname', " .
                         "'$this->comment', '$this->text', $insertMinorEdit)");

        if($this->version > 1)
        {
            $this->db->query("UPDATE $PgTbl SET time=$this->time, " .
                             "supercede=NULL WHERE title='$this->dbname' " .
                             "AND version=" . ($this->version - 1));
            if (!$insertMinorEdit)
                $this->db->query("UPDATE $LeTbl SET version=$this->version " .
                                 "WHERE page='$this->dbname'");
        }
        else
        {
            $metaphone = substr(metaphone($this->dbname), 0, 80);
            $this->db->query("DELETE FROM $MpTbl " .
                             "WHERE page = '$this->dbname'");
            $this->db->query("INSERT INTO $MpTbl (page, metaphone) " .
                             "VALUES ('$this->dbname', '$metaphone')");
            $this->db->query("INSERT INTO $LeTbl (page, version) " .
                             "VALUES ('$this->dbname', 1)");
        }
    }

    // Check if the page is set to be watched by a user.
    function isWatched($userName)
    {
        global $PwTbl;
        
        $query = "SELECT count(*) " .
                 "FROM $PwTbl " .
                 "WHERE page='{$this->name}' " .
                 "AND username='$userName'";

        $qid = $this->db->query($query);

        if (!($result = $this->db->result($qid)))
            return 0;
        else if ($result[0] == 0)
            return 0;
        else
            return 1;
    }

    // Toggle page watch for a user
    function toggleWatch($userName)
    {
        global $PwTbl;
        
        if ($userName != '')
        {
            if ($this->isWatched($userName))
                $this->db->query("DELETE FROM $PwTbl " .
                                 "WHERE page = '{$this->name}' " .
                                 "AND username = '$userName'");
            else
                $this->db->query("INSERT INTO $PwTbl (page, username) " .
                                 "VALUES ('{$this->name}', '$userName')");
        }

        return;
    }

    // Saves access time. Assume that isWatched is used before to check if the
    // record exists in the table PwTbl.
    function updateAccessTime($userName)
    {
        global $PwTbl;

        $this->db->query("UPDATE $PwTbl " .
                         "SET time = NULL " .
                         "WHERE page = '{$this->name}' " .
                         "AND username = '$userName'");

        return;
    }
}
?>
