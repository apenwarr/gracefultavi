<?php

require('template/preview.php');

// Preview what a page will look like when it is saved.
function action_preview()
{
  global $archive, $document, $minoredit, $nextver, $page, $pagefrom;
  global $pagestore, $ParseEngine, $template;

  $document = str_replace("\r", "", $document);
  $pg = $pagestore->page($page);
  $pg->read();

  template_preview(array('page'      => $page,
                         'pagefrom'  => $pagefrom,
                         'text'      => $document,
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
