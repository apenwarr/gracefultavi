<?php
// $Id: main.php,v 1.4 2002/01/03 16:33:26 smoonen Exp $

// Master parser for 'Tavi.
function parseText($text, $parsers, $object_name)
{
  global $Entity, $ParseObject;

  $old_parse_object = $ParseObject;
  $ParseObject = $object_name;          // So parsers know what they're parsing.

  $count  = count($parsers);
  $result = '';

  // Run each parse element in turn on each line of text.

  foreach(explode("\n", $text) as $line)
  {
    $line = $line . "\n";
    for($i = 0; $i < $count; $i++)
      { $line = $parsers[$i]($line); }

    $result = $result . $line;
  }

  // Some stateful parsers need to perform final processing.

  $line = '';
  for($i = 0; $i < $count; $i++)
    { $line = $parsers[$i]($line); }

  $ParseObject = $old_parse_object;

  return $result . $line;
}
?>
