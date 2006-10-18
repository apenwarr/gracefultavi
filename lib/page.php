<?php

// Abstractor to read and write wiki pages.
class WikiPage
{
    var $page_id;       // Primary Key ID of page.
    var $name = '';     // Name of page.
    var $dbname = '';   // Name used in DB queries.
    var $text = '';     // Page's text in wiki markup form.
    var $time = '';     // Page's modification time.
    var $hostname = ''; // Hostname of last editor.
    var $username = ''; // Username of last editor.
    var $comment  = ''; // Description of last edit.
    var $version = -1;  // Version number of page.
    var $mutable = 1;   // Whether page may be edited.
    var $template = 0;  // Whether page is a template.
    var $exists = 0;    // Whether page has a record in the db.
    var $db;            // Database object.
    var $createtime;    // Creation time of page.
    var $updatetime;    // Update time of page.

    function WikiPage($db_, $name_ = '')
    {
        $this->db = $db_;
        $this->name = $name_;
        $this->dbname = str_replace('\\', '\\\\', $name_);
        $this->dbname = str_replace('\'', '\\\'', $this->dbname);
    }

    // Checks whether the page exists. Returns true if the page exists in the
    // database and has content. Otherwise, returns false. Not to confuse with
    // the "exists" property of this class, which is true when the page exists
    // in the database only, no matter if it has content or not.
    function exists()
    {
        global $PgTbl, $page;

        $qid = $this->db->query("SELECT id " .
                                "FROM $PgTbl " .
                                "WHERE title='$this->dbname' " .
                                "AND bodylength>1");

        return !!($result = $this->db->result($qid));
    }

    // Read in a page's contents.
    // Returns: contents of the page.
    function read()
    {
        global $CoTbl, $PgTbl;

        if ($this->version == -1) {
            $qry_version = 'lastversion';
        } else {
            $qry_version = $this->version;
        }

        $qry = "SELECT id, time, author, body, attributes, version, " .
               "username, comment, createtime, updatetime " .
               "FROM $PgTbl, $CoTbl " .
               "WHERE title='$this->dbname' " .
               "AND id=page " .
               "AND version=$qry_version";
        $qid = $this->db->query($qry);

        if (!($result = $this->db->result($qid))) {
            return '';
        }

        $this->page_id  = $result[0];
        $this->time     = $result[1];
        $this->hostname = $result[2];
        $this->exists   = 1;
        $this->version  = $result[5];
        $this->mutable  = (($result[4] & MUTABLE_ATTR) == MUTABLE_ATTR ? 1 : 0);
        $this->template = (($result[4] & TEMPLATE_ATTR) == TEMPLATE_ATTR ? 1 : 0);
        $this->username = $result[6];
        $this->text     = $result[3];
        $this->comment  = $result[7];
        $this->createtime = $result[8];
        $this->updatetime = $result[9];

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
        global $CoTbl, $PgTbl;

        // Ensure a leading and trailing spaces free text but force a new line
        // at the end.
        $this->text = trim($this->text) . "\n";

        $page_id = $this->page_id;
        $body_length = strlen($this->text);

        // minor edit is always disabled if body is empty
        $insertMinorEdit = ($body_length <= 1) ? 0 : ($minoredit ? 1 : 0);

        // template is disabled if body is empty
        if ($body_length <= 1) { $this->template = 0; }

        if ($insertMinorEdit && !trim($this->comment)) {
            $this->comment = 'Minor edit';
        }

        // page table
        $attributes = $this->mutable * MUTABLE_ATTR +
                      $this->template * TEMPLATE_ATTR;
        if ($this->exists) {
            // get roolback information
            $qry = "SELECT lastversion, lastversion_major, bodylength, " .
                   "attributes, createtime, updatetime " .
                   "FROM $PgTbl " .
                   "WHERE id=$page_id";
            $qid = $this->db->query($qry);
            $rollback = $this->db->result($qid);

            $qry = "UPDATE $PgTbl SET lastversion=$this->version, " .
                   "bodylength=$body_length, " .
                   "attributes=$attributes, " .
                   "createtime='$this->createtime'";
            if ($insertMinorEdit) {
                $qry .= ", updatetime='$this->updatetime'";
            } else {
                $qry .= ", updatetime=null" .
                        ", lastversion_major=$this->version";
            }
            $qry .= " WHERE id=$page_id";
            $this->db->query($qry);
        } else {
            $metaphone = substr(metaphone($this->name), 0, 80);
            $qry = "INSERT INTO $PgTbl (title, title_notbinary, " .
                   "lastversion, lastversion_major, metaphone, bodylength, " .
                   "attributes, createtime, updatetime) " .
                   "VALUES ('$this->dbname', '$this->dbname', " .
                   "$this->version, $this->version, '$metaphone', " .
                   "$body_length, $attributes, null, null)";
            $this->db->query($qry);
            $page_id = mysql_insert_id($this->db->handle);
            if (!$page_id) { return false; }
        }

        // content table
        $qry = "INSERT INTO $CoTbl (page, version, time, " .
               "supercede, username, author, comment, " .
               "body, minoredit) " .
               "VALUES ($page_id, $this->version, NULL, NULL, " .
               "'$this->username', '$this->hostname', " .
               "'$this->comment', '$this->text', $insertMinorEdit)";
        if (!($qid = mysql_query($qry, $this->db->handle))) {
            // Roolback previous insert/update to restore data and preserve
            // referential integrity.
            if ($this->exists) {
                $qry = "UPDATE $PgTbl SET lastversion = $rollback[0], " .
                       "lastversion_major = $rollback[1], " .
                       "bodylength = $rollback[2], " .
                       "attributes = $rollback[3], " .
                       "createtime = '$rollback[4]', " .
                       "updatetime = '$rollback[5]' " .
                       "WHERE id=$page_id";
            } else {
                $qry = "DELETE FROM $PgTbl " .
                       "WHERE id=$page_id";
            }
            $this->db->query($qry);
            return false;
        }

        if ($this->version > 1) {
            $this->db->query("UPDATE $CoTbl SET time='$this->time', " .
                             "supercede=NULL WHERE page=$page_id " .
                             "AND version=" . ($this->version - 1));
        }

        return true;
    }

    // Check if a user is subscribed to a page.
    function isSubscribed($username)
    {
        global $SuTbl;

        $query = "SELECT count(*) " .
                 "FROM $SuTbl " .
                 "WHERE page='$this->dbname' " .
                 "AND username='$username'";

        $qid = $this->db->query($query);

        return (($result = $this->db->result($qid)) && $result[0] > 0) ? 1 : 0;

    }

    // Toggle page subscription for a user
    function toggleSubscribe($username)
    {
        global $SuTbl;

        if ($username != '')
        {
            if ($this->isSubscribed($username))
                $this->db->query("DELETE FROM $SuTbl " .
                                 "WHERE page = '$this->dbname' " .
                                 "AND username = '$username'");
            else
                $this->db->query("INSERT INTO $SuTbl (page, username) " .
                                 "VALUES ('$this->dbname', '$username')");
        }

        return;
    }

    function getSubscribedUsers($skip_username = '')
    {
        global $SuTbl;

        $query = "SELECT username " .
                 "FROM $SuTbl " .
                 "WHERE page='$this->dbname'";
        if ($skip_username) {
            $query .= " AND username<>'$skip_username'";
        }
        $qid = $this->db->query($query);

        $usernames = array();
        while ($result = $this->db->result($qid)) {
            $usernames[] = $result[0];
        }

        return $usernames;
    }
}
?>
