<?php

class Macro_CategoryCmds
{
    var $trigger = "!";
    var $pagestore;

    function parse($args, $page)
    {
        global $pagestore, $MinEntries, $DayLimit, $full, $page, $Entity;
        global $FlgChr, $UserName, $UseHotPages;

        $text = '';
        if (strstr($args, '*'))                // Category containing all pages.
        {
            $list = $pagestore->allpages();
        }
        else if (strstr($args, '?'))           // New pages.
        {
            $list = $pagestore->newpages();
        }
        else if (strstr($args, '~'))           // Zero-length (deleted) pages.
        {
            $list = $pagestore->emptypages();
        }
        else                                   // Ordinary list of pages.
        {
            $parsed = parseText($args, array('parse_wikiname', 'parse_freelink'), '');
            $pagenames = array();
            preg_replace('/' . $FlgChr . '(\\d+)' . $FlgChr . '/e', '$pagenames[]=$Entity[\\1][1]', $parsed);
            $list = $pagestore->givenpages($pagenames);
        }

        if (count($list) == 0) return '';

        usort($list, 'catSort');

        if ($UseHotPages)
            $hotPagesList = $pagestore->getHotPages();
        else
            $hotPagesList = array();

        $newPages = $pagestore->getNewPages();

        if ($UserName == '')
            $modifiedWatchedPages = array();
        else
            $modifiedWatchedPages = $pagestore->getModifiedWatchedPages($UserName);

        $now = time();
        for ($i = 0; $i < count($list); $i++)
        {
            $editTime = mktime(substr($list[$i][0], 8, 2), substr($list[$i][0], 10, 2),
                               substr($list[$i][0], 12, 2), substr($list[$i][0], 4, 2),
                               substr($list[$i][0], 6, 2), substr($list[$i][0], 0, 4));
            if ($DayLimit && $i >= $MinEntries &&
                !$full && ($now - $editTime) > $DayLimit * 24 * 60 * 60)
            {
                break;
            }

            $text = $text . html_category($list[$i][0], $list[$i][1], $list[$i][2],
                                          $list[$i][3], $list[$i][5], $list[$i][7],
                                          $hotPagesList, $newPages, $modifiedWatchedPages);

            // Do not put a newline on the last one.
            if ($i < count($list) - 1)
                $text = $text . html_newline();
        }

        if ($i < count($list))
            $text = $text . html_fulllist($page, count($list));

        return $text;
    }
}

function catSort($p1, $p2)
{
    return strcmp($p2[0], $p1[0]);
}

return 1;

?>
