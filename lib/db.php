<?php
// $Id: db.php,v 1.3 2001/11/30 22:10:15 toph Exp $

// MySQL database abstractor.  It should be easy to port this to other
//   databases, such as PostgreSQL.
class WikiDB
{
  var $handle;

  function WikiDB($persistent, $server, $user, $pass, $database)
  {
    global $ErrorDatabaseConnect, $ErrorDatabaseSelect;

    if($persistent)
      { $this->handle = mysql_pconnect($server, $user, $pass); }
    else
      { $this->handle = mysql_connect($server, $user, $pass); }

    if($this->handle <= 0)
      { die($ErrorDatabaseConnect); }

    if(mysql_select_db($database, $this->handle) == false)
      { die($ErrorDatabaseSelect); }
  }

  function query($text)
  {
    global $ErrorDatabaseQuery;

    if(!($qid = mysql_query($text, $this->handle)))
      die("Query: $text<br>" . $ErrorDatabaseQuery);

    return $qid;
  }

  function result($qid)
  {
    return mysql_fetch_row($qid);
  }
}
?>
