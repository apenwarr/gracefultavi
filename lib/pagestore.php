<?php
// $Id: pagestore.php,v 1.3 2003/04/01 18:32:36 mich Exp $

require('lib/db.php');
require('lib/page.php');

// Abstractor for the page database.  Note that page.php contains the actual
//   code to read/write pages; this serves more general query functions.
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

    // Return all pages names
    function getAllPageNames()
    {
        global $PgTbl;

        $qid = $this->dbh->query("SELECT distinct title FROM $PgTbl " .
                                 "ORDER BY lower(title)");

        $list = array();

        while(($result = $this->dbh->result($qid)))
            $list[] = $result[0];

        return $list;
    }

    // Return hot pages, i.e. pages modified at least 5 times during the last
    // week. Ignores minor edits and multiple subsequent updates by the same user.
    function getHotPages()
    {
        global $PgTbl;

        $qid = $this->dbh->query("SELECT title, username, version " .
                                 "FROM $PgTbl " .
                                 "WHERE (unix_timestamp(sysdate()) - unix_timestamp(time)) < (7*24*60*60) " .
                                 "AND minoredit = 0 " .
                                 "ORDER BY title, version");

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
        global $PgTbl, $LeTbl;

# new icon temporarily removed
return array();

/*
        $qid = $this->dbh->query("select p1.title " .
                                 "from $PgTbl p1, $PgTbl p2 " .
                                 "where p1.title = p2.title " .
                                 "and p1.version = 1 " .
                                 "and p1.minoredit = 0 " .
                                 "and p2.minoredit = 0 " .
                                 "group by p1.title, p2.title " .
                                 "having max(unix_timestamp(p2.time)) - min(unix_timestamp(p1.time)) < 60*60*24");

        $list = array();

        while (($result = $this->dbh->result($qid)))
            $list[] = $result[0];
*/

        $qid = $this->dbh->query("select title, unix_timestamp(time) time " .
                                 "from $PgTbl " .
                                 "where version = 1");
        $mintime = array();
        while (($result = $this->dbh->result($qid)))
            $mintime[$result[0]] = $result[1];

        $qid = $this->dbh->query("SELECT p.title, unix_timestamp(p.time) time " .
                                 "FROM $PgTbl p, $LeTbl l " .
                                 "WHERE p.title = l.page " .
                                 "AND p.version = l.version");
        $maxtime = array();
        while (($result = $this->dbh->result($qid)))
            $maxtime[$result[0]] = $result[1];

        $list = array();
        foreach ($mintime as $title => $time)
            if (!isset($maxtime[$title]) || ($maxtime[$title] - $time < 86400)) // 86400 seconds = 24 hours
                $list[] = $title;

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
        global $PaTbl;

        $qid = $this->dbh->query("SELECT parent FROM $PaTbl " .
                                 "WHERE page='$text' " .
                                 "ORDER BY lower(parent)");

        $list = array();

        while(($result = $this->dbh->result($qid)))
            $list[] = $result[0];

        return $list;
    }

    // Return children
    function getChildren($text)
    {
        global $PaTbl;

        $qid = $this->dbh->query("SELECT page FROM $PaTbl " .
                                 "WHERE parent='$text' " .
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
        global $PaTbl;

        if ($parents && !is_array($parents))
            $parents = array($parents);

        $this->dbh->query("DELETE FROM $PaTbl " .
                          "WHERE page = '$page'");

        if ($parents)
            foreach ($parents as $parent)
                $this->dbh->query("INSERT INTO $PaTbl (page, parent) " .
                                  "VALUES ('$page', '$parent')");
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
        while (($result = $this->dbh->result($qid))) {
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
                $qid = $this->dbh->query("SELECT distinct title FROM $PgTbl order by lower(title)");
                $pages = array();
                while(($result = $this->dbh->result($qid)))
                    $pages[] = $result[0];

                // get all pages with children
                $qid = $this->dbh->query("SELECT distinct parent FROM $PaTbl order by lower(page)");
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

        if ($caseSensitive)
        {
            $qid = $this->dbh->query("SELECT distinct title FROM $PgTbl");

            while(($result = $this->dbh->result($qid)))
                if (levenshtein($result[0], $pageLower) <= $toleratedDistance)
                    $list[] = $result[0];
        }
        else
        {
            $qid = $this->dbh->query("SELECT distinct lower(title), title FROM $PgTbl");

            $page = strtolower($page);
            while(($result = $this->dbh->result($qid)))
                if (levenshtein($result[0], $page) <= $toleratedDistance)
                    $list[] = $result[1];
        }

        return $list;
    }

    // Find truly sounding-like pages using the metaphone() php function.
    // The metaphone values are actually stored in a table.
    function findSoundLike($page)
    {
        global $PgTbl, $MpTbl;

        $metaphone = metaphone($page);

        $qid = $this->dbh->query("SELECT distinct title FROM $PgTbl as p, $MpTbl as m " .
                                 "WHERE p.title=m.page AND metaphone='$metaphone'");
        $list = array();
        while (($result = $this->dbh->result($qid)))
            $list[] = $result[0];

        return $list;
    }

    // Find one page in the database.
    function findOne($page)
    {
        global $LeTbl;

        // case sensitive search
        $qid = $this->dbh->query("SELECT distinct page FROM $LeTbl " .
                                 "WHERE page='$page'");
        if (mysql_num_rows($qid) == 1)
            return $page;

        // case insensitive search
        $pageLower = strtolower($page);
        $qid = $this->dbh->query("SELECT distinct page FROM $LeTbl " .
                                 "WHERE lower(page)='$pageLower'");
        if (mysql_num_rows($qid) == 1)
        {
            $result = $this->dbh->result($qid);
            return $result[0];
        }

        // beginning with search
        $qid = $this->dbh->query("SELECT distinct page FROM $LeTbl " .
                                 "WHERE lower(page) like '$pageLower%' " .
                                 "ORDER BY lower(page)");
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
    global $PgTbl;

    $qid = $this->dbh->query("SELECT title, max(version) FROM $PgTbl " .
                             "GROUP BY title ORDER BY lower(title)");

    $list = array();
    $text = strtolower($text);
    while(($result = $this->dbh->result($qid)))
    {
        $q2 = $this->dbh->query("SELECT title FROM $PgTbl " .
                                "WHERE title='$result[0]' " .
                                "AND version='$result[1]' " .
                                "AND (lower(body) LIKE '%$text%' " .
                                "OR lower(title) LIKE '%$text%')");
        if($this->dbh->result($q2))
            { $list[] = $result[0]; }
    }

    return $list;
  }

  // Retrieve a page's edit history.
  function history($page)
  {
    global $PgTbl;

    $qid = $this->dbh->query("SELECT time, author, version, username, " .
                             "comment " .
                             "FROM $PgTbl WHERE title='$page' " .
                             "ORDER BY version DESC");

    $list = array();
    while(($result = $this->dbh->result($qid)))
    {
      $list[] = array($result[0], $result[1], $result[2], $result[3],
                      $result[4]);
    }

    return $list;
  }

  // Look up an interwiki prefix.
  function interwiki($name)
  {
    global $IwTbl;

    $qid = $this->dbh->query("SELECT url FROM $IwTbl WHERE prefix='$name'");
    if(($result = $this->dbh->result($qid)))
      { return $result[0]; }
    return '';
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
    //   script invocation.  If this assumption should change, $links should
    //   be made a 2-dimensional array.

    global $LkTbl;
    static $links = array();

    if(empty($links[$link]))
    {
      $this->dbh->query("INSERT INTO $LkTbl VALUES ('$page', '$link', 1)");
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
      $this->dbh->query("UPDATE $IwTbl SET where_defined='$where_defined', " .
                        "url='$url' WHERE prefix='$prefix'");
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
      $this->dbh->query("UPDATE $SwTbl SET where_defined='$where_defined', " .
                        "url='$url' WHERE prefix='$prefix'");
    }
    else
    {
      $this->dbh->query("INSERT INTO $SwTbl (prefix, where_defined, url) " .
                        "VALUES ('$prefix', '$where_defined', '$url')");
    }
  }

  // Find all twins of a page at sisterwiki sites.
  function twinpages($page)
  {
    global $RemTbl, $IwTbl;

    $list = array();
    $q2 = $this->dbh->query("SELECT site, page FROM $RemTbl " .
                            "WHERE page LIKE '$page'");
    while(($twin = $this->dbh->result($q2)))
      { $list[] = array($twin[0], $twin[1]); }

    return $list;
  }

  // Lock the database tables.
  function lock()
  {
    global $PgTbl, $IwTbl, $SwTbl, $LkTbl, $RtTbl, $RemTbl,
           $PaTbl, $MpTbl, $SuTbl, $LeTbl;

    $this->dbh->query("LOCK TABLES $PgTbl WRITE, $IwTbl WRITE, $SwTbl WRITE, " .
                      "$LkTbl WRITE, $RtTbl WRITE, $RemTbl WRITE, " .
                      "$PaTbl WRITE, $MpTbl WRITE, $SuTbl WRITE, $LeTbl WRITE");
  }

  // Unlock the database tables.
  function unlock()
  {
    $this->dbh->query("UNLOCK TABLES");
  }

  // Retrieve a list of all of the pages in the wiki except the ones with an
  // empty body. This ignores the minor edits.
  function allpages($with_page_size = false)
  {
        global $PgTbl, $LeTbl;

/*
        $qid = $this->dbh->query("SELECT title, MAX(version) " .
                                 "FROM $PgTbl " .
                                 "WHERE minoredit = 0 " .
                                 "OR LENGTH(body) <= 1 " .
                                 "GROUP BY title " .
                                 "ORDER BY lower(title)");

        $list = array();
        while(($result = $this->dbh->result($qid)))
        {
            $q2 = $this->dbh->query("SELECT author, time, username, LENGTH(body), " .
                                    "comment, mutable " .
                                    "FROM $PgTbl " .
                                    "WHERE title='$result[0]' " .
                                    "AND version='$result[1]' " .
                                    "AND LENGTH(body) > 1");

            if ($auth_res = $this->dbh->result($q2))
            {
                $list[] = array($auth_res[1], $result[0], $auth_res[0], $auth_res[2],
                                $auth_res[3], $auth_res[4], $auth_res[5] == 'on', $result[1]);
            }
        }
*/

        $page_size_column = $with_page_size ? 'LENGTH(p.body)' : '2';

        $qid = $this->dbh->query("SELECT p.title, p.version, p.author, p.time, p.username, " .
                                 "$page_size_column, p.comment, p.mutable, p.minoredit " .
                                 "FROM $PgTbl p, $LeTbl l " .
                                 "WHERE p.title = l.page " .
                                 "AND p.version = l.version "
                                 #. "AND length(substring(p.body, 2, 1)) > 0 "
                                 #. "AND p.minoredit = 0"
                                 );

        while ($result = $this->dbh->result($qid)) {
            if ($result[8] == 0) {
                $list[] = array($result[3], $result[0], $result[2], $result[4], $result[5],
                                $result[6], $result[7] == 'on', $result[1]);
            }
        }

        return $list;
  }

  // Retrieve a list of all new pages in the wiki.
  function newpages()
  {
    global $PgTbl;

    $qid = $this->dbh->query("SELECT title, author, time, username, " .
                             "LENGTH(body), comment " .
                             "FROM $PgTbl WHERE version=1");

    $list = array();
    while(($result = $this->dbh->result($qid)))
    {
      $list[] = array($result[2], $result[0], $result[1], $result[3],
                      $result[4], $result[5]);
    }

    return $list;
  }

  // Return a list of all empty (deleted) pages in the wiki.
  function emptypages()
  {
    global $PgTbl;

    $qid = $this->dbh->query("SELECT title, MAX(version) FROM $PgTbl " .
                             "GROUP BY title order by lower(title)");

    $list = array();
    while(($result = $this->dbh->result($qid)))
    {
      $q2 = $this->dbh->query("SELECT author, time, username, comment " .
                              "FROM $PgTbl " .
                              "WHERE title='$result[0]' " .
                              "AND version='$result[1]' " .
                              "AND body=''");
      if(($auth_res = $this->dbh->result($q2)))
      {
        $list[] = array($auth_res[1], $result[0], $auth_res[0],
                        $auth_res[2], 0, $auth_res[3]);
      }
    }

    return $list;
  }

  // Return a list of information about a particular set of pages.
  function givenpages($names)
  {
    global $PgTbl;

    $list = array();
    foreach($names as $page)
    {
      $qid = $this->dbh->query("SELECT time, author, username, LENGTH(body), " .
                               "comment FROM $PgTbl WHERE title='$page' " .
                               "ORDER BY version DESC");

      if(!($result = $this->dbh->result($qid)))
        { continue; }

      $list[] = array($result[0], $page, $result[1], $result[2],
                      $result[3], $result[4]);
    }

    return $list;
  }

  // Expire old versions of pages.
  function maintain()
  {
    global $PgTbl, $RtTbl, $ExpireLen, $RatePeriod;

    if ($ExpireLen)
    {
        $qid = $this->dbh->query("SELECT title, MAX(version) FROM $PgTbl " .
                                 "GROUP BY title");

        while(($result = $this->dbh->result($qid)))
        {
            $this->dbh->query("DELETE FROM $PgTbl WHERE title='$result[0]' " .
                              "AND (version < $result[1] OR LENGTH(body)<=1) " .
                              "AND TO_DAYS(NOW()) - TO_DAYS(supercede) > $ExpireLen");
        }
    }

    if($RatePeriod)
    {
        $this->dbh->query("DELETE FROM $RtTbl " .
                          "WHERE ip NOT LIKE '%.*' " .
                          "AND TO_DAYS(NOW()) > TO_DAYS(time)");
    }
  }
}
?>
