<?php
// $Id: history.php,v 1.6 2002/01/07 16:28:32 smoonen Exp $

require('parse/main.php');
require('parse/macros.php');
require('parse/html.php');
require('lib/diff.php');
require('template/history.php');  #require(TemplateDir . '/history.php');
require('lib/headers.php');

// Display the known history of a page's edits.
function action_history()
{
  global $pagestore, $page, $full, $HistMax;
  global $ver1, $ver2;

  $history = $pagestore->history($page);

  gen_headers($history[0][0]);

  $versions = diff_get_history_versions($history, $ver1, $ver2);
  $latest_ver = $versions['latest_ver'];
  $previous_ver = $versions['previous_ver'];

  $text = '';

  for($i = 0; $i < count($history); $i++)
  {
    if($i < $HistMax || $full)
        $text = $text . html_history_entry($page, $history[$i][2],
                                           $history[$i][0], $history[$i][1],
                                           $history[$i][3],
                                           $previous_ver == $history[$i][2],
                                           $latest_ver == $history[$i][2],
                                           $history[$i][4]);
  }

  if($i >= $HistMax && !$full)
    $text = $text . html_fullhistory($page, count($history));

  $p1 = $pagestore->page($page);
  $p1->version = $previous_ver;
  $p2 = $pagestore->page($page);
  $p2->version = $latest_ver;

  $diff = diff_compute($p1->read(), $p2->read());

  template_history(array('page'    => $page,
                         'history' => $text,
                         'diff'    => diff_parse($diff)));
}
?>
