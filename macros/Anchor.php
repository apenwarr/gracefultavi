<?php

class Macro_Anchor
{
   // This macro inserts an HTML anchor into the text.
   var $pagestore;

   function parse($args, $page)
   {
      preg_match('/([-A-Za-z0-9]*)/', $args, $result);

      if($result[1] != '')
      { return html_anchor($result[1]); }
      else
      { return ''; }
   }
}

return 1;
?>
