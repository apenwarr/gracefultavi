<?php
require_once("lib/difflib.php");

function diff_compute($text1, $text2)
{
    $text1 = explode("\n", $text1);
    $text2 = explode("\n", $text2);
    $diff = new Diff($text1, $text2);
    $formatter = new UnifiedDiffFormatter;

    return $formatter->format($diff);
}

function diff_parse($text)
{
  global $DiffEngine;

  return parseText($text, $DiffEngine, '');
}
?>
