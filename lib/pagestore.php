<?php

require('lib/db.php');
require('lib/page.php');

// Abstractor for the page database. Note that page.php contains the actual code
// to read/write pages; this serves more general query functions.
class PageStore
{
    var $dbh;

    function PageStore()
    {
        global $DBPersist, $DBServer, $DBUser, $DBPasswd, $DBName;

        $this->dbh = new WikiDB($DBPersist, $DBServer, $DBUser, $DBPasswd, $DBName);
    }

    // Create a page object.
    function page($name = '')
    {
        return new WikiPage($this->dbh, $name);
    }

    // Creates a cache of all existing pages, it speeds up pages having tons of
    // links like RecentChanges.
    function page_exists($page)
    {
        global $PgTbl;

        static $page_exists_cache = array();

        if (!$page_exists_cache) {
            $qid = $this->dbh->query("SELECT title " .
                                     "FROM $PgTbl " .
                                     "WHERE bodylength>1");
            while ($result = $this->dbh->result($qid)) {
                $page_exists_cache[$result[0]] = 1;
            }
        }

        return isset($page_exists_cache[$page]);
    }

    // Return all pages names
    function getAllPageNames()
    {
        global $PgTbl;

        $qid = $this->dbh->query("SELECT title_notbinary " .
                                 "FROM $PgTbl " .
                                 "WHERE bodylength>1 " .
                                 "ORDER BY title_notbinary");

        $list = array();

        while ($result = $this->dbh->result($qid)) {
            $list[] = $result[0];
        }

        return $list;
    }

    // Return hot pages, i.e. pages modified at least 5 times during the last
    // week. Ignores minor edits and multiple subsequent updates by the same user.
    function getHotPages()
    {
        global $CoTbl, $PgTbl;

        $qid = $this->dbh->query("SELECT p.title, c.username, c.version " .
                                 "FROM $PgTbl p, $CoTbl c " .
                                 "WHERE p.id=c.page " .
                                 "AND (unix_timestamp(sysdate()) - " .
                                 "unix_timestamp(c.time)) < (7*24*60*60) " .
                                 "AND c.minoredit = 0 " .
                                 "ORDER BY p.title, c.version");

        $list = array();

        $lastTitle = '';
        $lastUsername = '';
        $count = 0;
        while (($result = $this->dbh->result($qid)))
        {
            if ($result[0] != $lastTitle)
            {
                if ($count > 4) $list[] = $lastTitle;

                $lastTitle = $result[0];
                $lastUsername = '';
                $count = 0;
            }

            if ($result[1] != $lastUsername)
            {
                $lastUsername = $result[1];
                $count++;
            }
        }

        return $list;
    }

    // Return new pages, i.e. pages having their most recent edit less than 24 hours
    // before the first edit. Ignores minor edits.
    function getNewPages()
    {
        global $PgTbl;

        $qid = $this->dbh->query("SELECT title " .
                                 "FROM $PgTbl " .
                                 "WHERE unix_timestamp(updatetime) " .
                                 "-unix_timestamp(createtime)<86400");

        $list = array();
        while ($result = $this->dbh->result($qid)) {
            $list[] = $result[0];
        }

        return $list;
    }

    // Return backlinks
    function getBacklinks($text)
    {
        global $LkTbl;

        $qid = $this->dbh->query("SELECT page FROM $LkTbl " .
                                 "WHERE link='$text' " .
                                 "ORDER BY lower(page)");

        $list = array();

        while(($result = $this->dbh->result($qid)))
            $list[] = $result[0];

        return $list;
    }

    // Return parents
    function getParents($text)
    {
        global $PaTbl, $PgTbl;

        $qid = $this->dbh->query("SELECT parent " .
                                 "FROM $PaTbl a, $PgTbl g " .
                                 "WHERE a.parent=g.title " .
                                 "AND g.bodylength>1 " .
                                 "AND page='$text' " .
                                 "ORDER BY lower(parent)");

        $list = array();

        while(($result = $this->dbh->result($qid)))
            $list[] = $result[0];

        return $list;
    }

    // Return children
    function getChildren($text)
    {
        global $PaTbl, $PgTbl;

        $qid = $this->dbh->query("SELECT page " .
                                 "FROM $PaTbl a, $PgTbl g " .
                                 "WHERE a.page=g.title " .
                                 "AND g.bodylength>1 " .
                                 "AND parent='$text' " .
                                 "ORDER BY lower(page)");

        $list = array();

        while(($result = $this->dbh->result($qid)))
            $list[] = $result[0];

        return $list;
    }

    // Find the most probable parent of a page.
    function findFosterParent($page)
    {
        global $LkTbl;

        $backlinks = $this->getBacklinks($page);

        $parent = '';
        $backlinksCount = 0;
        foreach($backlinks as $backlink)
        {
            $qid = $this->dbh->query("SELECT count(*) " .
                                     "FROM $LkTbl " .
                                     "WHERE link='$backlink'");

            if ($result = $this->dbh->result($qid))
                if(!$parent || $result[0] > $backlinksCount)
                {
                    $parent = $backlink;
                    $backlinksCount = $result[0];
                }
        }

        return $parent;
    }

    // Reparent a page
    // It is assumed that the pages specified by $parents and $page all exists.
    function reparent($page, $parents)
    {
        global $PaTbl, $PgTbl;

        if ($parents && !is_array($parents))
            $parents = array($parents);

        $this->dbh->query("DELETE FROM $PaTbl " .
                          "WHERE page='$page'");

        // disallow parenting of empty pages
        $qid = $this->dbh->query("SELECT id " .
                                 "FROM $PgTbl " .
                                 "WHERE title='$page' " .
                                 "AND bodylength>1");
        if (!mysql_num_rows($qid)) { return; }

        if ($parents)
            foreach ($parents as $parent)
                $this->dbh->query("INSERT INTO $PaTbl (page, parent) " .
                                  "VALUES ('$page', '$parent')");
    }

    // Remove any parenting when deleting a page.
    function reparent_emptypage($page)
    {
        global $PaTbl;

        $this->dbh->query("DELETE FROM $PaTbl " .
                          "WHERE page='$page' " .
                          "OR parent='$page'");
    }

    // Return subscribed pages for a given user
    function getSubscribedPages($username)
    {
        global $SuTbl;

        $query = "SELECT page " .
                 "FROM $SuTbl " .
                 "WHERE username='$username' " .
                 "ORDER BY lower(page)";
        $qid = $this->dbh->query($query);

        $list = array();
        while ($result = $this->dbh->result($qid)) {
            $list[] = $result[0];
        }

        return $list;
    }

    // Unsubscribe pages for a giver user
    function unsubscribePages($username, $pages)
    {
        global $SuTbl;

        foreach ($pages as $page) {
            $query = "DELETE FROM $SuTbl " .
                     "WHERE username='$username' " .
                     "AND page='$page'";
            $qid = $this->dbh->query($query);
        }

        return;
    }

    function getTemplatePages()
    {
        global $PgTbl;

        $query = "SELECT title " .
                 "FROM $PgTbl " .
                 "WHERE (attributes & ".TEMPLATE_ATTR.") = ".TEMPLATE_ATTR." " .
                 "ORDER BY lower(title)";
        $qid = $this->dbh->query($query);

        $list = array();
        while ($result = $this->dbh->result($qid)) {
            $list[] = $result[0];
        }

        return $list;
    }

    // Build a tree from an array of branches
    function getTreeFromBranches($branches)
    {
        $tree = array();

        foreach ($branches as $branch)
        {
            $nodes = split('%', $branch);

            $current = &$tree;
            foreach ($nodes as $node)
            {
                if (!isset($current[$node]))
                    $current[$node] = array();
                $current = &$current[$node];
            }
        }

        return $tree;
    }

    // Extracts all the nodes from an array of branches
    function getNodesFromBranches($branches)
    {
        $nodesList = array();

        foreach ($branches as $branch)
        {
            $nodes = split('%', $branch);

            foreach ($nodes as $node)
                if (!array_key_exists($node, $nodesList))
                    $nodesList[] = $node;
        }

        $nodesList = array_unique($nodesList);

        return $nodesList;
    }

    // Return a tree structure according to parent/child relationships
    //
    // Initial value of the parameters:
    //   breadcrumb: root page of the tree
    //   findPage  : page to find
    //   returnType: TREE or FLAT, return a tree or a flat array of the nodes
    //   i         : internal use for recursivity control, do not use when calling getTree
    function getTree($breadcrumb, $findPage = '', $returnType = 'TREE', $i = 0)
    {
        static $branches;

        // initialize branches at first entry
        if ($i == 0) $branches = array();

        preg_match("/^(.+%)*(.+)$/", $breadcrumb, $results);

        $path = $results[1];
        $page = preg_quote($results[2], '/');

        if (!preg_match("/(^|%)$page($|%)/", $path))
            if ($page == $findPage)
                $branches[] = $breadcrumb;
            else
            {
                $links = $this->getChildren($page);

                if (!$findPage && !$links)
                    $branches[] = $breadcrumb;
                else
                    foreach ($links as $link)
                        if ($link != $page)
                            $this->getTree("$breadcrumb%$link", $findPage, $returnType, $i + 1);
            }

        // Returns the tree at the end of the first entry in getTree
        if ($i == 0)
        {
            if ($returnType == 'TREE')
                return $this->getTreeFromBranches($branches);
            else
                return $this->getNodesFromBranches($branches);
        }
    }

    // Return a tree starting by the leaves, going up until rootPage, no parents
    // or a cycle. If no leaves are provided, starts from all leaves,
    // i.e. all pages without children. initialLeaves can be a single value or
    // and array.
    function getTreeFromLeaves($rootPage = '', $initialLeaves = '', $breadcrumb = '', $i = 0)
    {
        global $PgTbl, $PaTbl;
        static $branches;

        if ($i == 0)
        {
            // first time in, initialize the search
            $branches = array();

            if (!$initialLeaves)
            {
                // get all pages
                $qid = $this->dbh->query("SELECT title_notbinary " .
                                         "FROM $PgTbl " .
                                         "WHERE bodylength>1 " .
                                         "ORDER BY title_notbinary");
                $pages = array();
                while(($result = $this->dbh->result($qid)))
                    $pages[] = $result[0];

                // get all pages with children
                $qid = $this->dbh->query("SELECT DISTINCT a.parent " .
                                         "FROM $PaTbl a, $PgTbl g " .
                                         "WHERE a.parent=g.title " .
                                         "AND g.bodylength>1 " .
                                         "ORDER BY lower(a.page)");
                $parents = array();
                while(($result = $this->dbh->result($qid)))
                    $parents[] = $result[0];

                // get all pages without children
                $initialLeaves = array_diff($pages, $parents);
            }

            if (!is_array($initialLeaves))
                $initialLeaves = array($initialLeaves);

            foreach ($initialLeaves as $leaf)
                $this->getTreeFromLeaves($rootPage, '', $leaf, $i+1);

            natcasesort($branches);

            return $this->getTreeFromBranches($branches);
        }
        else
        {
            preg_match("/^([^%]+)((%[^%]+)*)$/", $breadcrumb, $results);

            $page = preg_quote($results[1], '/');
            $remaining = $results[2];

            if (preg_match("/(^|%)$page($|%)/", $remaining))
                $branches[] = $breadcrumb;
            else
                if ($page == $rootPage)
                    $branches[] = $breadcrumb;
                else
                {
                    $parents = $this->getParents($page);

                    if (!$parents)
                        $branches[] = $breadcrumb;
                    else
                        foreach ($parents as $parent)
                            $this->getTreeFromLeaves($rootPage, '', "$parent%$breadcrumb", $i + 1);
                }
        }
    }

    // Find looking-like pages in the database. Returns a list of pages which
    // names looks like the given page parameter. The parameter
    // toleratedDistance refers to the return value of evenshtein(), see php
    // manual for more informations.
    function findLookLike($page, $toleratedDistance, $caseSensitive = 0)
    {
        // Note: The code is doubled to avoid a useless if inside a loop over
        // all the pages. Since we're in a 'find' function here, speed is
        // crucial.

        global $PgTbl;

        $list = array();

        if ($caseSensitive) {
            $qid = $this->dbh->query("SELECT title FROM $PgTbl");

            while ($result = $this->dbh->result($qid)) {
                if (levenshtein($result[0], $page) <= $toleratedDistance) {
                    $list[] = $result[0];
                }
            }
        } else {
            $qid = $this->dbh->query("SELECT lower(title), title FROM $PgTbl");

            $page = strtolower($page);
            while ($result = $this->dbh->result($qid)) {
                if (levenshtein($result[0], $page) <= $toleratedDistance) {
                    $list[] = $result[1];
                }
            }
        }

        return $list;
    }

    // Find truly sounding-like pages using the metaphone() php function.
    // The metaphone values are actually stored in a table.
    function findSoundLike($page)
    {
        global $PgTbl;

        $metaphone = metaphone($page);

        $qid = $this->dbh->query("SELECT title " .
                                 "FROM $PgTbl " .
                                 "WHERE metaphone='$metaphone'");
        $list = array();
        while ($result = $this->dbh->result($qid)) {
            $list[] = $result[0];
        }

        return $list;
    }

    // Find one page in the database.
    function findOne($page)
    {
        global $PgTbl;

        $dbname = str_replace('\\', '\\\\', $page);
        $dbname = str_replace('\'', '\\\'', $dbname);

        // case sensitive search
        $qid = $this->dbh->query("SELECT title " .
                                 "FROM $PgTbl " .
                                 "WHERE title='$dbname' " .
                                 "AND bodylength>1");
        if (mysql_num_rows($qid) == 1)
            return $page;

        // case insensitive search
        $qid = $this->dbh->query("SELECT title_notbinary " .
                                 "FROM $PgTbl " .
                                 "WHERE title_notbinary='$dbname' " .
                                 "AND bodylength>1");
        if (mysql_num_rows($qid) == 1)
        {
            $result = $this->dbh->result($qid);
            return $result[0];
        }

        // beginning with search
        $qid = $this->dbh->query("SELECT title_notbinary " .
                                 "FROM $PgTbl " .
                                 "WHERE title_notbinary like '$dbname%' " .
                                 "AND bodylength>1 " .
                                 "ORDER BY title_notbinary");
        if (mysql_num_rows($qid) > 0)
        {
            $result = $this->dbh->result($qid);
            return $result[0];
        }

        // sound-like search
        // $lookLike = $this->findLookLike($page, 2);
        $soundLike = $this->findSoundLike($page);
        if (count($soundLike) == 1)
            return $soundLike[0];
        else
            return '';
    }

    // Find text in the database.
    function find($text)
    {
        global $CoTbl, $PgTbl;

        if (trim($text) == '')
        {
            return $this->getAllPageNames();
        }

        $text = strtolower($text);
        $text0 = $text;

        // split in words
        $quoted_words = array();
        if (preg_match_all('/[-\+]?"[^"]+"/', $text, $matches))
        {
            $quoted_words = $matches[0];
            foreach ($quoted_words as $word)
            {
                $text = str_replace($word, '', $text);
            }
        }
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = array_merge($words, $quoted_words);

        // filter out words required to match or not
        // remove quotes from quoted words
        // cleanup for sql strings
        $text = addslashes($text);
        $text0 = addslashes($text0);
        foreach ($words as $word)
        {
            preg_match('/^([-\+]?)(.+)$/', $word, $matches);
            $word = preg_replace('/^"(.+)"$/', '\\1', $matches[2]);
            $word = addslashes($word);
            switch ($matches[1])
            {
                case '+':
                    $score_words[] = $word;
                    $with_words[] = $word;
                    break;
                case '-':
                    $without_words[] = $word;
                    break;
                default:
                    $score_words[] = $word;
                    break;
            }
        }

        // build qry
        $qry = "SELECT title,";
        if (count($words) > 1)
        {
            $qry .= "
                (100 * IF (body LIKE '%$text0%', 1, 0)) +";
        }
        $qry .= "
            ((1 ";
        foreach ($score_words as $word)
        {
            $qry .= "* IF (title_notbinary LIKE '%$word%', 2, 1) ";
        }
        $qry .= ")*10) + (1 ";
        foreach ($score_words as $word)
        {
            $qry .= "* IF (body LIKE '%$word%', 2, 1) ";
        }
        $qry .= ") score
            FROM $PgTbl
            WHERE bodylength>1
            AND (0";
        foreach ($score_words as $word)
        {
            $qry .= "
                OR title_notbinary LIKE '%$word%'
                OR body LIKE '%$word%'";
        }
        $qry .= "
            )";
        foreach ($with_words as $word)
        {
            $qry .= "
                AND body LIKE '%$word%'";
        }
        foreach ($without_words as $word)
        {
            $qry .= "
                AND body NOT LIKE '%$word%'";
        }
        $qry .= "
            ORDER BY score DESC";

        $qid = $this->dbh->query($qry);
        $list = array();
        while ($row = $this->dbh->result($qid))
        {
            $list[] = $row[0];
        }

        return $list;
    }

    // Retrieve a page's edit history.
    function history($page)
    {
        global $CoTbl, $PgTbl;

        $qid = $this->dbh->query("SELECT c.time, c.author, c.version, " .
                                 "c.username, c.comment " .
                                 "FROM $PgTbl p, $CoTbl c " .
                                 "WHERE p.title='$page' " .
                                 "AND p.id=c.page " .
                                 "ORDER BY version DESC");

        $list = array();
        while ($result = $this->dbh->result($qid)) {
            $list[] = array($result[0], $result[1], $result[2], $result[3],
                            $result[4]);
        }

        return $list;
    }

    // Look up an interwiki prefix.
    function interwiki($name, $viewing_page = '')
    {
        global $IwTbl;

        if ($viewing_page == 'RecentChanges') {
            static $interwiki_cache = array();

            if (!$interwiki_cache) {
                $qid = $this->dbh->query("SELECT url, prefix " .
                                         "FROM $IwTbl");
                while ($result = $this->dbh->result($qid)) {
                    $interwiki_cache[$result[1]] = $result[0];
                }
            }

            return (isset($interwiki_cache[$name])) ?
                $interwiki_cache[$name] : '';
        } else {
            $qid = $this->dbh->query("SELECT url " .
                                     "FROM $IwTbl " .
                                     "WHERE prefix='$name'");
            if ($result = $this->dbh->result($qid)) {
                return $result[0];
            }
            return '';
        }
    }

    // Clear all the links cached for a particular page.
    function clear_link($page)
    {
        global $LkTbl;

        $this->dbh->query("DELETE FROM $LkTbl WHERE page='$page'");
    }

    // Clear all the interwiki definitions for a particular page.
    function clear_interwiki($page)
    {
        global $IwTbl;

        $this->dbh->query("DELETE FROM $IwTbl WHERE where_defined='$page'");
    }

    // Clear all the sisterwiki definitions for a particular page.
    function clear_sisterwiki($page)
    {
        global $SwTbl;

        $this->dbh->query("DELETE FROM $SwTbl WHERE where_defined='$page'");
    }

    // Add a link for a given page to the link table.
    function new_link($page, $link)
    {
        // Assumption: this will only ever be called with one page per
        //   script invocation.  If this assumption should change, $links
        //   should be made a 2-dimensional array.

        global $LkTbl;
        static $links = array();

        if(empty($links[$link]))
        {
            $this->dbh->query("INSERT INTO $LkTbl VALUES " .
                              "('$page', '$link', 1)");
            $links[$link] = 1;
        }
        else
        {
            $links[$link]++;
            $this->dbh->query("UPDATE $LkTbl SET count=" . $links[$link] .
                              " WHERE page='$page' AND link='$link'");
        }
    }

    // Add an interwiki definition for a particular page.
    function new_interwiki($where_defined, $prefix, $url)
    {
        global $IwTbl;

        $url = str_replace("'", "\\'", $url);
        $url = str_replace("&amp;", "&", $url);

        $qid = $this->dbh->query("SELECT where_defined FROM $IwTbl " .
                                 "WHERE prefix='$prefix'");
        if($this->dbh->result($qid))
        {
            $this->dbh->query("UPDATE $IwTbl SET " .
                              "where_defined='$where_defined', " .
                              "url='$url' ".
                              "WHERE prefix='$prefix'");
        }
        else
        {
          $this->dbh->query("INSERT INTO $IwTbl (prefix, where_defined, url) " .
                            "VALUES('$prefix', '$where_defined', '$url')");
        }
    }

    // Add a sisterwiki definition for a particular page.
    function new_sisterwiki($where_defined, $prefix, $url)
    {
        global $SwTbl;

        $url = str_replace("'", "\\'", $url);
        $url = str_replace("&amp;", "&", $url);

        $qid = $this->dbh->query("SELECT where_defined FROM $SwTbl " .
                                 "WHERE prefix='$prefix'");
        if($this->dbh->result($qid))
        {
            $this->dbh->query("UPDATE $SwTbl SET " .
                              "where_defined='$where_defined', " .
                              "url='$url' " .
                              "WHERE prefix='$prefix'");
        }
        else
        {
            $this->dbh->query("INSERT INTO $SwTbl " .
                              "(prefix, where_defined, url) " .
                              "VALUES ('$prefix', '$where_defined', '$url')");
        }
    }

    // Find all twins of a page at sisterwiki sites.
    function twinpages($page, $viewing_page = '')
    {
        global $RemTbl, $UserName;

        $restriction_level = $UserName ? 2 : 1;

        $list = array();

        if ($viewing_page == 'RecentChanges') {
            static $twinpages_cache = array();

            if (!$twinpages_cache) {
                $qid = $this->dbh->query("SELECT site, page " .
                                         "FROM $RemTbl " .
                                         "WHERE restricted < $restriction_level");
                while ($result = $this->dbh->result($qid)) {
                    if (!isset($twinpages_cache[$result[1]])) {
                        $twinpages_cache[$result[1]] = array();
                    }
                    $twinpages_cache[$result[1]][] = $result[0];
                }
            }

            if (isset($twinpages_cache[$page])) {
                foreach ($twinpages_cache[$page] as $site) {
                    $list[] = array($site, $page);
                }
            }
        } else {
            $dbname = str_replace('\\', '\\\\', $page);
            $dbname = str_replace('\'', '\\\'', $dbname);
            $q2 = $this->dbh->query("SELECT site " .
                                    "FROM $RemTbl " .
                                    "WHERE page='$dbname' " .
                                    "AND restricted < $restriction_level");
            while ($twin = $this->dbh->result($q2)) {
                $list[] = array($twin[0], $page);
            }
        }

        return $list;
    }

    // Lock the database tables.
    function lock()
    {
        global $CoTbl, $IwTbl, $LkTbl, $PaTbl, $PgTbl, $RemTbl, $RtTbl, $SuTbl;
        global $SwTbl, $VeTbl;

        $this->dbh->query("LOCK TABLES $CoTbl WRITE, $IwTbl WRITE, " .
                          "$LkTbl WRITE, $PaTbl WRITE, $PgTbl WRITE, " .
                          "$RemTbl WRITE, $RtTbl WRITE, $SuTbl WRITE, " .
                          "$SwTbl WRITE, $VeTbl WRITE");
    }

    // Unlock the database tables.
    function unlock()
    {
        $this->dbh->query("UNLOCK TABLES");
    }

    // Retrieve a list of all of the pages in the wiki except the ones with an
    // empty body. This ignores the minor edits.
    function allpages()
    {
        global $CoTbl, $PgTbl, $DayLimit, $MinEntries;

        if ($DayLimit) {
            $from_time = date('YmdHis', time() - ($DayLimit * 24 * 60 * 60));
        } else {
            $from_time = 0;
        }

        $base_qry = "SELECT p.title, c.version, c.author, " .
                    "c.time, c.username, p.bodylength, " .
                    "c.comment, p.attributes, c.minoredit " .
                    "FROM $PgTbl p, $CoTbl c " .
                    "WHERE p.id=c.page " .
                    "AND p.bodylength>1 " .
                    "AND p.lastversion_major=c.version ";

        $qid = $this->dbh->query($base_qry . "AND p.updatetime>$from_time");
        if (mysql_num_rows($qid) < $MinEntries) {
            $qid = $this->dbh->query($base_qry . "ORDER BY c.time DESC LIMIT $MinEntries");
        }

        $list = array();
        while ($result = $this->dbh->result($qid)) {
            $is_mutable = (($result[7] & MUTABLE_ATTR) == MUTABLE_ATTR ? 1 : 0);
            $list[] = array($result[3], $result[0], $result[2], $result[4], $result[5],
                            $result[6], $is_mutable, $result[1]);
        }

        return $list;
    }

    // Retrieve a list of all new pages in the wiki.
    function newpages()
    {
        global $CoTbl, $PgTbl;

        $qid = $this->dbh->query("SELECT p.title, c.author, c.time, " .
                                 "c.username, p.bodylength, c.comment " .
                                 "FROM $PgTbl p, $CoTbl c " .
                                 "WHERE p.id=c.page " .
                                 "AND p.lastversion_major=1 " .
                                 "AND c.version=1");

        $list = array();
        while ($result = $this->dbh->result($qid)) {
            $list[] = array($result[2], $result[0], $result[1], $result[3],
                            $result[4], $result[5]);
        }

        return $list;
    }

    // Return a list of all empty (deleted) pages in the wiki.
    function emptypages()
    {
        global $CoTbl, $PgTbl;

        $qid = $this->dbh->query("SELECT p.title, c.author, c.time, " .
                                 "c.username, c.comment " .
                                 "FROM $PgTbl p, $CoTbl c " .
                                 "WHERE p.id=c.page " .
                                 "AND p.bodylength<2 " .
                                 "AND p.lastversion_major=c.version");
        $list = array();
        while ($result = $this->dbh->result($qid)) {
            $list[] = array($result[2], $result[0], $result[1],
                            $result[3], 0, $result[4]);
        }

        return $list;
    }

    // Return a list of information about a particular set of pages.
    function givenpages($names)
    {
        global $CoTbl, $PgTbl;

        $list = array();
        foreach ($names as $page) {
            $dbname = str_replace('\\', '\\\\', $page);
            $dbname = str_replace('\'', '\\\'', $dbname);

            $qid = $this->dbh->query("SELECT c.time, c.author, c.username, " .
                                     "p.bodylength, c.comment " .
                                     "FROM $PgTbl p, $CoTbl c " .
                                     "WHERE p.title='$dbname' " .
                                     "AND p.id=c.page " .
                                     "AND p.lastversion_major=c.version");

            if (!($result = $this->dbh->result($qid))) {
                continue;
            }

            $list[] = array($result[0], $page, $result[1], $result[2],
                            $result[3], $result[4]);
        }

        return $list;
    }

    // Expire old versions of pages.
    function maintain()
    {
        global $PgTbl, $RtTbl, $ExpireLen, $RatePeriod;

        /*
        TODO
        This functionality is not yet supported with the new database schema.

        if ($ExpireLen) {
            $qid = $this->dbh->query("SELECT title, MAX(version) FROM $PgTbl " .
                                     "GROUP BY title");

            while ($result = $this->dbh->result($qid)) {
                $this->dbh->query("DELETE FROM $PgTbl WHERE title='$result[0]' " .
                                  "AND (version < $result[1] OR LENGTH(body)<=1) " .
                                  "AND TO_DAYS(NOW()) - TO_DAYS(supercede) > $ExpireLen");
            }
        }
        */

        if ($RatePeriod) {
            $this->dbh->query("DELETE FROM $RtTbl " .
                              "WHERE ip NOT LIKE '%.*' " .
                              "AND TO_DAYS(NOW()) > TO_DAYS(time)");
        }
    }
}
?>
