<?php

/* Table Of Contents generator
 *
 * Creates a table of contents based on the page's headers.
 *
 * The param is the maximum hierarchy level to display.
 * Defaults to 9.
 *
 */

$TocJavascriptFunctions = <<<JAVASCRIPT
<script language="javascript">
<!--
function hideToc(i)
{
    document.getElementById('showtoc' + i).style.display = 'block';
    document.getElementById('toc' + i).style.display = 'none';
}

function showToc(i)
{
    document.getElementById('toc' + i).style.display = 'block';
    document.getElementById('showtoc' + i).style.display = 'none';
}
//-->
</script>
JAVASCRIPT;


class Macro_Toc
{
    var $i = 0;

    function parse($max_level, $page)
    {
        global $pagestore, $MaxHeading, $document, $TocJavascriptFunctions;
        global $ViewMacroEngine;

        $document = parseText($document, array('parse_htmlpre', 'parse_nowiki'), $page);

        if (!$max_level) $max_level = $MaxHeading; // maximum for the wiki
        if (!$max_level) $max_level = 9;           // maximum in html

        $this->i++;
        if ($this->i > 1)
            $TocJavascriptFunctions = '';

        // find highest header level
        $prev_level = $max_level;
        $found = 0;
        foreach (explode("\n", $document) as $line)
        {
            if (preg_match('/^\s*_?(=+)([^=]*)(=+)\s*$/', strip_tags($line), $result) &&
                strlen($result[1]) === strlen($result[3]))
            {
                $found = 1;
                $level = min(strlen($result[1]), $max_level);
                if ($level < $prev_level)
                    $prev_level = $level;
            }
        }
        if ($found)
            $prev_level--;
        else
            $prev_level = 0;

        $toc = array();
        $count = 0;
        $level = $prev_level;

        foreach (explode("\n", $document) as $line)
        {
            // includes content of checklists in the toc
            if ($level+1 <= $max_level
                && isset($ViewMacroEngine['ChecklistMaster'])
                && preg_match('/\\[\\[ChecklistMaster ([^]]+)]]/',
                              $line, $matches))
            {
                $prev_level = $level+1;
                $categories = $ViewMacroEngine['ChecklistMaster']->getCategories($matches[1]);
                $anchor_name = str_replace(' ', '_', $matches[1]);
                $toc[] = '<ol>';
                $toc[] = "<li><a href=\"#$anchor_name\">$matches[1]</a>";
                if ($level+2 <= $max_level)
                {
                    #$prev_level = $level+2;
                    $toc[] = '<ol>';
                    foreach ($categories as $id => $name)
                        $toc[] = "<li><a href=\"#$anchor_name$id\">$name</a>";
                    $toc[] = '</ol>';
                }
                #$toc[] = '</ol>';
            }

            // actual toc based on the headers
            if (preg_match('/^\s*_?(=+)([^=]*)(=+)\s*$/', strip_tags($line), $result) &&
                strlen($result[1]) === strlen($result[3]))
            {
                $level = strlen($result[1]);
                $count++;

                if ($level <= $max_level)
                {
                    $indentIncrease = $level - $prev_level;
                    for ($i = 0; $i < $indentIncrease; $i++)
                    {
                        $toc[] = '<ol>';
                        if ($i != ($indentIncrease - 1))
                            $toc[] = '<li>';
                    }
                    for ($i = $indentIncrease; $i < 0; $i++)
                        $toc[] = '</ol>';

                    $prev_level = $level;

                    $header = $result[2];
                    $header = preg_replace('/<[^>]+>/', '', $header);
                    $header = preg_replace('/[!\\[\\]]/', '', $header);

                    $toc[] = "<li><a href=\"#toc$count\">$header</a>";
                }
            }
        }

        for ($i = 0; $i < $prev_level; $i++)
            $toc[] = '</ol>';

        $toc = implode("\n", $toc);

        $return_value = '<table cellspacing="0" cellpadding="3" border=0">' .
                        '<tr id="showtoc' . $this->i . '" style="display: none;">' .
                        '<td><b>Table of contents</b> [ <a href="javascript:showToc(' . $this->i . ');">show</a> ]</td></tr>' .
                        '<tr id="toc' . $this->i . '"><td><b>Table of contents</b> ' .
                        '[ <a href="javascript:hideToc(' . $this->i . ');">hide</a> ]' . $toc . '</td></tr></table>';

        return "$TocJavascriptFunctions\n\n$return_value";
   }
}

return 1;

?>
