<?php

require('parse/html.php');
require('template/edit.php');

// Edit a page (possibly an archive version).
function action_edit()
{
  global $ErrorPageLocked, $page, $pagefrom, $pagestore, $ParseEngine;
  global $use_template, $UserName, $version;

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

  $page_text = $pg->text;

  // page template
  if ($use_template) {
    $tmpl_pg = $pagestore->page($use_template);
    if ($tmpl_pg->exists())
    {
      $tmpl_pg->read();
      $page_text = $tmpl_pg->text;
      $archive = 0;
    }
    else
    {
      $use_template = '';
    }
  }

  template_edit(array('page'      => $page,
                      'pagefrom'  => $pagefrom,
                      'text'      => trim($page_text) != '' ? $page_text : "Describe $page here...\n\nPlease provide content before saving.\n\n--[$UserName]",
                      'timestamp' => $pg->time,
                      'nextver'   => $pg->version + 1,
                      'archive'   => $archive,
                      'template'  => $pg->template,
                      'templates' => $pagestore->getTemplatePages(),
                      'use_template' => $use_template,
                      'edituser'  => $pg->username,
                      'editver'   => ($UserName && $pg->mutable) ?
                                     (($version == '') ? 0 : $version) : -1));
}
?>
