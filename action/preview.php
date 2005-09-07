<?php

require('template/preview.php');

// Preview what a page will look like when it is saved.
function action_preview()
{
  global $ParseEngine, $archive;
  global $page, $pagefrom, $document, $nextver, $pagestore;

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
                         'edituser'  => $pg->username));
}
?>
