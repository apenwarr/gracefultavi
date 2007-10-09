<?php

class Macro_Transclude
{
   function parse($args, $page)
   {
     global $pagestore, $ParseEngine, $ParseObject;
     static $visited_array = array();
     static $visited_count = 0;

     if(!validate_page($args))
       { return '[[Transclude ' . $args . ']]'; }

     $visited_array[$visited_count++] = $ParseObject;
     for($i = 0; $i < $visited_count; $i++)
     {
       if($visited_array[$i] == $args)
       {
         $visited_count--;
         return '[[Transclude ' . $args . ']]';
       }
     }

     $pg = $pagestore->page($args);
     $pg->read();
     if(!$pg->exists)
     {
       $visited_count--;
       return '[[Transclude ' . $args . ']]';
     }

     $result = parseText($pg->text, $ParseEngine, $args);
     $visited_count--;
     return $result;
   }
}

return 1;

?>