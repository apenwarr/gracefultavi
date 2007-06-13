<?php

require('template/conflict.php');

// Conflict editor. Someone accidentally almost overwrote something someone else
// just saved.
function action_conflict()
{
  global $document, $merge, $page, $pagestore, $ParseEngine;

  $pg = $pagestore->page($page);
  $pg->read();

  template_conflict(array(
      'page'      => $page,
      'text'      => $pg->text,
      'html'      => parseText($pg->text, $ParseEngine, $page),
      'usertext'  => $document,
      'merge'     => $merge,
      'timestamp' => $pg->time,
      'nextver'   => $pg->version + 1
  ));
}
?>
