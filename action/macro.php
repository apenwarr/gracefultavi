<?php
// $Id: macro.php,v 1.3 2001/11/30 07:49:32 toph Exp $

require('parse/macros.php');
require('parse/html.php');

// Execute a macro directly from the URL.
function action_macro()
{
  global $ViewMacroEngine, $macro, $parms;

  if(!empty($ViewMacroEngine[$macro]))
  {
    print $ViewMacroEngine[$macro]($parms);
  }
}
?>
