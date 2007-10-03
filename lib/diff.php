<?php

require_once("lib/difflib.php");

function diff_compute($text1, $text2)
{
    $text1 = explode("\n", "\n".$text1);
    $text2 = explode("\n", "\n".$text2);
    $diff = new Diff($text1, $text2);
    $formatter = new UnifiedDiffFormatter;

    return $formatter->format($diff);
}

function wdiff_compute($text1, $text2)
{
    global $ErrorCreatingTemp, $ErrorWritingTemp, $TempDir, $WdiffCmd;
    global $WdiffLibrary;

    $num = posix_getpid();  // Comment if running on Windows.
    // $num = rand();       // Uncomment if running on Windows.

    $temp1 = $TempDir . '/wiki_' . $num . '_1.txt';
    $temp2 = $TempDir . '/wiki_' . $num . '_2.txt';

    if(!($h1 = fopen($temp1, 'w')) || !($h2 = fopen($temp2, 'w')))
        { die($ErrorCreatingTemp); }

    $fw1 = fwrite($h1, $text1);
    $fw2 = fwrite($h2, $text2);
    if (($fw1 === false) || ((strlen($text1) > 0) && ($fw1 == 0)) ||
        ($fw2 === false) || ((strlen($text2) > 0) && ($fw2 == 0)))
        { die($ErrorWritingTemp); }

    fclose($h1);
    fclose($h2);

    if ($WdiffLibrary) putenv('LD_LIBRARY_PATH=/disk');
    exec($WdiffCmd . ' -n --start-delete="<DEL>" --end-delete="</DEL>" --start-insert="<INS>" --end-insert="</INS>" ' . $temp1 . ' ' . $temp2, $output);

    unlink($temp1);
    unlink($temp2);

    $output = implode("\n", $output);

    return $output;
}

function diff_parse($text)
{
  global $DiffEngine;

  return parseText($text, $DiffEngine, '');
}

function wdiff_parse($text)
{
  global $WdiffEngine;

  return parseText($text, $WdiffEngine, '');
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
