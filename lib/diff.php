<?php
// $Id: diff.php,v 1.5 2002/01/09 21:48:46 smoonen Exp $

// Compute the difference between two sets of text.
function diff_compute($text1, $text2)
{
  global $TempDir, $DiffCmd, $ErrorCreatingTemp, $ErrorWritingTemp;

  $num = posix_getpid();                // Comment if running on Windows.
  // $num = rand();                     // Uncomment if running on Windows.

  $temp1 = $TempDir . '/wiki_' . $num . '_1.txt';
  $temp2 = $TempDir . '/wiki_' . $num . '_2.txt';

  if(!($h1 = fopen($temp1, 'w')) || !($h2 = fopen($temp2, 'w')))
    { die($ErrorCreatingTemp); }

  if(fwrite($h1, $text1) < 0 || fwrite($h2, $text2) < 0)
    { die($ErrorWritingTemp); }

  fclose($h1);
  fclose($h2);

  $diff = `$DiffCmd -U 4 $temp1 $temp2`;

  // Clean diff result by removing the first 2 lines.
  $diff = preg_replace('/^[^\n]*\n[^\n]*\n(.*)$/s', '\\1', $diff);

  unlink($temp1);
  unlink($temp2);

  return $diff;
}

// Parse diff output into nice HTML.
function diff_parse($text)
{
  global $DiffEngine;

  return parseText($text, $DiffEngine, '');
}

?>
