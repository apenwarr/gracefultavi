<?php
// $Id: history.php,v 1.6 2002/01/07 16:28:32 smoonen Exp $

require('parse/main.php');
require('parse/macros.php');
require('parse/html.php');
require('lib/diff.php');
require('template/history.php');  #require(TemplateDir . '/history.php');
require('lib/headers.php');

function timestampToSeconds($timestamp)
{
    return mktime(substr($timestamp, 8, 2),  substr($timestamp, 10, 2),
                  substr($timestamp, 12, 2), substr($timestamp, 4, 2),
                  substr($timestamp, 6, 2),  substr($timestamp, 0, 4));
}

// Display the known history of a page's edits.
function action_history()
{
  global $pagestore, $page, $full, $HistMax;
  global $ver1, $ver2;

  $history = $pagestore->history($page);

  gen_headers($history[0][0]);


  // If no Newer version is requested, the latest one will be used.
  // If no Older version is requested, will find the next version that is
  // either from a different author, or by the same author but more than
  // 24 hours before the Newer version.
  if (count($history) > 0)
  {
    $latest_index = 0;
    if ($ver2)
      for ($i = 0; $i < count($history); $i++)
        if ($history[$i][2] == $ver2)
        {
          $latest_index = $i;
          break;
        }
    $latest_ver = $history[$latest_index][2];
    $latest_author = ($history[$latest_index][3] == '' ? $history[$latest_index][1] : $history[$latest_index][3]);
    $latest_timestamp = timestampToSeconds($history[$latest_index][0]);

    if (!$previous_ver = $ver1)
    {
      $previous_ver = $history[count($history)-1][2];
      for ($i = $latest_index; $i < count($history)-1; $i++)
        if ( $latest_author != ($history[$i][3] == '' ? $history[$i][1] : $history[$i][3]) ||
             ($latest_timestamp - timestampToSeconds($history[$i][0])) > 86400
           )
        {
          $previous_ver = $history[$i][2];
          break;
        }
    }
  }


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
