<?php

require('template/preview.php');
require('action/history.php');

// Preview what a page will look like when it is saved.
function action_preview()
{
  global $archive, $diff_mode, $document, $minoredit, $nextver, $page;
  global $pagefrom, $pagestore, $ParseEngine, $section, $template, $text_after;
  global $text_before;

  $document = str_replace("\r", "", $document);
  $text_before = str_replace("\r", "", $text_before);
  $text_after = str_replace("\r", "", $text_after);

  $pg = $pagestore->page($page);
  $pg->read();

  // computes the diff of the current changes
  $body1_pg = $pagestore->page($page);
  $body1_pg->version = $nextver - 1;
  $body1 = $body1_pg->read();
  $body2 = $document;
  if ($section)
  {
      $body2 = $text_before."\n\n".trim($document)."\n\n".$text_after;
  }
  $diff = do_diff($body1, $body2);

  template_preview(array(
      'page'        => $page,
      'pagefrom'    => $pagefrom,
      'text'        => $document,
      'section'     => $section,
      'text_before' => $text_before,
      'text_after'  => $text_after,
      'html'        => parseText($document, $ParseEngine, $page),
      'diff'        => $diff,
      'diff_mode'   => $diff_mode,
      'timestamp'   => $pg->time,
      'nextver'     => $nextver,
      'archive'     => $archive,
      'minoredit'   => $minoredit,
      'template'    => $template,
      'edituser'    => $pg->username
  ));
}
?>
