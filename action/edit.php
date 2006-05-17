<?php

require('parse/html.php');
require('template/edit.php');

// Edit a page (possibly an archive version).
function action_edit()
{
  global $ErrorPageLocked, $page, $pagefrom, $pagestore, $ParseEngine;
  global $section, $use_template, $UserName, $version;

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
  if ($use_template)
  {
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

  // section editing
  $text_before = '';
  $text_after = '';
  if ($section)
  {
    $lines = explode("\n", $page_text);

    $lines_before = array();
    $lines_after = array();
    $lines_section = array();
    $section_count = 0;
    $found_level = 0;

    foreach ($lines as $line)
    {
      if (preg_match(parse_heading_regexp(), $line, $result) &&
          (strlen($result[2]) == strlen($result[4])) &&
          (!$found_level || strlen($result[2]) <= $found_level))
      {
        $section_count++;
      }

      if ($section_count < $section)
      {
        $lines_before[] = $line;
      }
      else if ($section_count > $section)
      {
        $lines_after[] = $line;
      }
      else
      {
        if (!$found_level) { $found_level = strlen($result[2]); }
        $lines_section[] = $line;
      }
    }

    $text_before = implode("\n", $lines_before);
    $text_after = implode("\n", $lines_after);
    $page_text = implode("\n", $lines_section);
  }

  if (trim($page_text) == '')
  {
    $page_text = "Describe $page here...\n\nPlease provide content before ".
                 "saving.\n\n-- [$UserName]";
  }

  template_edit(array('page'      => $page,
                      'pagefrom'  => $pagefrom,
                      'text'      => $page_text,
                      'section'   => $section,
                      'text_before' => $text_before,
                      'text_after'  => $text_after,
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
