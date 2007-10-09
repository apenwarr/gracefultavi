<?php

class Macro_PageLinks
{
   function parse($args, $page)
   {
     global $pagestore, $LkTbl;

     $text = '';
     $first = 1;

     $q1 = $pagestore->dbh->query("SELECT page, SUM(count) AS ct FROM $LkTbl " .
                                  "GROUP BY page ORDER BY ct DESC, page");
     while(($result = $pagestore->dbh->result($q1)))
     {
       if(!$first)                         // Don't prepend newline to first one.
         { $text = $text . "\n"; }
       else
         { $first = 0; }

       $text = $text .
               '(' . $result[1] . ') ' . html_ref($result[0], $result[0]);
     }

     return html_code($text);
   }
}

return 1;
?>
