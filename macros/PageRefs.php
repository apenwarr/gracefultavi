<?php

class Macro_PageRefs
{
   var $pagestore;

   function parse($args, $page)
   {
     global $pagestore, $LkTbl, $PgTbl;

     $text = '';
     $first = 1;
     $q1 = $pagestore->dbh->query("SELECT link, SUM(count) AS ct FROM $LkTbl " .
                                  "GROUP BY link ORDER BY ct DESC, link");
     while(($result = $pagestore->dbh->result($q1)))
     {
       $q2 = $pagestore->dbh->query("SELECT lastversion FROM $PgTbl " .
                                    "WHERE title='$result[0]'");
       if(($r2 = $pagestore->dbh->result($q2)) && !empty($r2[0]))
       {
         if(!$first)                       // Don't prepend newline to first one.
           { $text = $text . "\n"; }
         else
           { $first = 0; }

         $text = $text . '(' .
                 html_url(findURL($result[0]), $result[1]) . ') ' .
                 html_ref($result[0], $result[0]);
       }
     }

     return html_code($text);
   }
}

return 1;

?>
