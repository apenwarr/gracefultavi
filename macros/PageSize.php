<?php

class Macro_PageSize
{
   var $pagestore;

   function parse($args, $page)
   {
     global $pagestore;

     $first = 1;
     $list = $pagestore->allpages();

     usort($list, 'sizeSort');

     $text = '';

     foreach($list as $page)
     {
       if(!$first)                         // Don't prepend newline to first one.
         { $text = $text . "\n"; }
       else
         { $first = 0; }

       $text = $text .
               $page[4] . ' ' . html_ref($page[1], $page[1]);
     }

     return html_code($text);
   }
}

function sizeSort($p1, $p2)
{ return $p2[4] - $p1[4]; }

return 1;
?>