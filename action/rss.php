<?php

require('template/rss.php');
require('parse/main.php');
require('parse/macros.php');
require('parse/html.php');
require('lib/diff.php');

function timestamp_mysql_to_unix($timestamp)
{
    return mktime(substr($timestamp, 8, 2),  substr($timestamp, 10, 2),
                  substr($timestamp, 12, 2), substr($timestamp, 4, 2),
                  substr($timestamp, 6, 2),  substr($timestamp, 0, 4));
}

function action_rss()
{
    global $days, $min, $pagestore;

    $itemdesc = '';

    if ($min == 0){ $min = 10; }
    if ($days == 0) { $days = 2; }

    $pages = $pagestore->allpages();

    usort($pages, 'catSort');
    $now = time();

    for ($i = 0; $i < count($pages); $i++) {
        $editTime = mktime(substr($pages[$i][0], 8, 2),
                           substr($pages[$i][0], 10, 2),
                           substr($pages[$i][0], 12, 2),
                           substr($pages[$i][0], 4, 2),
                           substr($pages[$i][0], 6, 2),
                           substr($pages[$i][0], 0, 4));
        if ($days >= 0 && ($now - $editTime) > $days * 24 * 60 * 60 && $i >= $min) {
            break;
        }

        // Gets the diff as it shows by default on History page.
        // See diff_get_history_versions in lib/diff.php.
        $history = $pagestore->history($pages[$i][1]);
        $versions = diff_get_history_versions($history);
        $latest_ver = $versions['latest_ver'];
        $previous_ver = $versions['previous_ver'];

        $p1 = $pagestore->page($pages[$i][1]);
        $p1->version = $previous_ver;
        $p2 = $pagestore->page($pages[$i][1]);
        $p2->version = $latest_ver;

        if ($previous_ver == $latest_ver) {
            $diff = $p1->read();
        } else {
            $diff = diff_compute($p1->read(), $p2->read());
        }

        $diff = diff_parse($diff);

        #$diff = preg_replace('/\n/', chr(13) . chr(10), $diff);
        #$diff = preg_replace('/\n/', "<br>\n", $diff);

        $itemdesc = $itemdesc .
                    '<item>' . "\n" .
                    '<title>' . $pages[$i][1] . '</title>' . "\n" .
                    '<pubDate>' .  date('r', timestamp_mysql_to_unix($pages[$i][0])) . '</pubDate>' . "\n" .
                    '<link>' . viewFullURL($pages[$i][1]) . '&amp;' . $pages[$i][7] . '</link>' . "\n" .
                    '<description>' . htmlspecialchars(($pages[$i][5] ? $pages[$i][5] . "<br>\n<br>\n" : '') . $diff) . '</description>' . "\n" .
                    '</item>' . "\n\n";
    }

    template_rss(array('itemdesc' => $itemdesc));
}

?>
