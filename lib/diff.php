<?php
require_once("lib/difflib.php");

function diff_compute($text1, $text2)
{
    $text1 = explode("\n", $text1);
    $text2 = explode("\n", $text2);
    $diff = new Diff($text1, $text2);
    $formatter = new UnifiedDiffFormatter;

    return $formatter->format($diff);
}

function diff_parse($text)
{
  global $DiffEngine;

  return parseText($text, $DiffEngine, '');
}

function timestampToSeconds($timestamp)
{
    return mktime(substr($timestamp, 8, 2),  substr($timestamp, 10, 2),
                  substr($timestamp, 12, 2), substr($timestamp, 4, 2),
                  substr($timestamp, 6, 2),  substr($timestamp, 0, 4));
}

function diff_get_history_versions($history, $ver1 = '', $ver2 = '')
{
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
                     ($latest_timestamp - timestampToSeconds($history[$i][0])) > 86400 )
                {
                    $previous_ver = $history[$i][2];
                    break;
                }
        }
    }

    return array('latest_ver' => $latest_ver, 'previous_ver' => $previous_ver);
}
?>
