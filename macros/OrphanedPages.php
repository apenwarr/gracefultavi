<?php

class Macro_OrphanedPages
{
   var $pagestore;

   function parse($args, $page)
   {
     global $pagestore, $LkTbl;

     $text = '';
     $first = 1;

     $pages = $pagestore->allpages();
     usort($pages, 'nameSort');

     foreach($pages as $page)
     {
       $q2 = $pagestore->dbh->query("SELECT page FROM $LkTbl " .
                                    "WHERE link='$page[1]' AND page!='$page[1]'");
       if(!($r2 = $pagestore->dbh->result($q2)) || empty($r2[0]))
       {
         if(!$first)                       // Don't prepend newline to first one.
           { $text = $text . "\n"; }
         else
           { $first = 0; }

         $text = $text . html_ref($page[1], $page[1]);
       }
     }

     return html_code($text);
   }
}

function nameSort($p1, $p2)
{ return strcmp($p1[1], $p2[1]); }

return 1;
?>
