<?php
// $Id: edit.php,v 1.6 2002/01/07 16:28:32 smoonen Exp $

require('parse/html.php');
require('template/edit.php');  #require(TemplateDir . '/edit.php');

// Edit a page (possibly an archive version).
function action_edit()
{
  global $page, $pagefrom, $pagestore, $ParseEngine, $version, $ErrorPageLocked;
  global $UserName;

  $pg = $pagestore->page($page);
  $pg->read();

  if(!$UserName || !$pg->mutable)
    { die($ErrorPageLocked); }

  $archive = 0;
  if($version != '')
  {
    $pg->version = $version;
    $pg->read();
    $archive = 1;
  }

  template_edit(array('page'      => $page,
                      'pagefrom'  => $pagefrom,
                      'text'      => trim($pg->text) != '' ? $pg->text : "Describe $page here...\n\nPlease provide content before saving.\n\n--[$UserName]",
                      'timestamp' => $pg->time,
                      'nextver'   => $pg->version + 1,
                      'archive'   => $archive,
                      'edituser'  => $pg->username));
}
?>
