<?php

class Macro_LinkTable
{
   var $pagestore;

   function parse($args, $page)
   {
     global $pagestore, $LkTbl;
   
     $lastpage = '';
     $text = '';
   
     $q1 = $pagestore->dbh->query("SELECT page, link FROM $LkTbl ORDER BY page");
     while(($result = $pagestore->dbh->result($q1)))
     {
       if($lastpage != $result[0])
       {
         if($lastpage != '')
           { $text = $text . "\n"; }
   
         $text = $text . html_ref($result[0], $result[0]) . ' |';
         $lastpage = $result[0];
       }
   
       $text = $text . ' ' . html_ref($result[1], $result[1]);
     }
   
     return html_code($text);
   }
}

return 1;
?>
