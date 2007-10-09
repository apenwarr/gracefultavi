<?php

class Macro_GetTopLevel
{
   // This macro gives the top level page.
   function parse($args, $page)
   {
      global $HomePage;

      return html_ref($HomePage, $HomePage);
   }
}

return 1;
?>
