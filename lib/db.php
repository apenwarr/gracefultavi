<?php

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
    {
      if (0)
      {
        die("$ErrorDatabaseQuery<br><br>" .
            "Query: $text<br>" .
            'MySQL error: ' . mysql_error($this->handle) .
            '<pre>'.print_r(debug_backtrace(), true).'</pre>');
      }
      else
      {
        die($ErrorDatabaseQuery);
      }
    }

    return $qid;
  }

  function result($qid)
  {
    return mysql_fetch_row($qid);
  }
}
?>
