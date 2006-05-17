<?php

require('template/preview.php');

// Preview what a page will look like when it is saved.
function action_preview()
{
  global $archive, $document, $minoredit, $nextver, $page, $pagefrom;
  global $pagestore, $ParseEngine, $section, $template, $text_after;
  global $text_before;

  $document = str_replace("\r", "", $document);
  $text_before = str_replace("\r", "", $text_before);
  $text_after = str_replace("\r", "", $text_after);

  $pg = $pagestore->page($page);
  $pg->read();

  template_preview(array('page'      => $page,
                         'pagefrom'  => $pagefrom,
                         'text'      => $document,
                         'section'   => $section,
                         'text_before' => $text_before,
                         'text_after'  => $text_after,
                         'html'      => parseText($document,
                                                  $ParseEngine, $page),
                         'timestamp' => $pg->time,
                         'nextver'   => $nextver,
                         'archive'   => $archive,
                         'minoredit' => $minoredit,
                         'template'  => $template,
                         'edituser'  => $pg->username));
}
?>
