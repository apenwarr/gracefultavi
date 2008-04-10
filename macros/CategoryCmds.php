<?php

class Macro_CategoryCmds
{
    var $trigger = "!";

    function parse($args, $page)
    {
        global $pagestore, $MinEntries, $DayLimit, $full, $page, $Entity;
        global $FlgChr, $UserName, $UseHotPages;

        list($args, $ignoreRegExp) = explode(' ', $args, 2);

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

        $now = time();
        for ($i = 0; $i < count($list); $i++)
        {
            if ($ignoreRegExp) {
                if (@preg_match($ignoreRegExp, $list[$i][1])) {
                    continue;
                }
            }

            if ($DayLimit && $i >= $MinEntries &&
                !$full && ($now - $list[$i][0]) > ($DayLimit * 24 * 60 * 60))
            {
                break;
            }

            $text = $text . html_category($list[$i][0], $list[$i][1], $list[$i][2],
                                          $list[$i][3], $list[$i][5], $list[$i][7],
                                          $hotPagesList, $newPages);

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
