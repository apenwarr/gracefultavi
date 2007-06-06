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
    document.getElementById('showtoc' + i).style.display = '';
    document.getElementById('thetoc' + i).style.display = 'none';
}

function showToc(i)
{
    document.getElementById('thetoc' + i).style.display = '';
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
            if (($result = parse_heading_line_match(strip_tags($line))) !== false)
            {
                $found = 1;
                $level = min(strlen($result[2]), $max_level);
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

        // variables used for numbered headers support
        $num_headers_count = array();
        $last_level = 0;
        $numbering_level_diff = -1;

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
                $toc[] = '<ul>';
                $toc[] = "<li type=disc><a href=\"#$anchor_name\">$matches[1]</a>";
                if ($level+2 <= $max_level)
                {
                    $toc[] = '<ul>';
                    foreach ($categories as $id => $name)
                        $toc[] = "<li type=disc><a href=\"#$anchor_name$id\">$name</a>";
                    $toc[] = '</ul>';
                }
            }

            // actual toc based on the headers
            if (($result = parse_heading_line_match(strip_tags($line))) !== false)
            {
                $level = strlen($result[2]);
                $count++;

                if ($level <= $max_level)
                {
                    $indentIncrease = $level - $prev_level;
                    for ($i = 0; $i < $indentIncrease; $i++)
                    {
                        $toc[] = '<ul>';
                        if ($i != ($indentIncrease - 1))
                            $toc[] = '<li type=disc>';
                    }
                    for ($i = $indentIncrease; $i < 0; $i++)
                        $toc[] = '</ul>';

                    $prev_level = $level;

                    $header = $result[3];
                    // remove tags, brackets around wiki words, and
                    // exclamations marks disabling wiki words
                    $header = preg_replace('/<[^>]+>/', '', $header);
                    $header = preg_replace('/[\\[\\]]/', '', $header);
                    $header = preg_replace('/!(\S)/', '\\1', $header);

                    // support for numbered headers
                    $header_num = '';
                    if ($result[1] == '@')
                    {
                        if ($numbering_level_diff < 0)
                        {
                            $numbering_level_diff = $level - 1;
                        }
                        if ($level > $last_level)
                        {
                            for ($i = $last_level+1; $i < $level; $i++)
                                { $num_headers_count[$i] = 1; }
                            $num_headers_count[$level] = 0;
                        }
                        $last_level = $level;
                        $num_headers_count[$level]++;
                        for ($i = 1+$numbering_level_diff; $i <= $level; $i++)
                        {
                            if ($header_num != '') { $header_num .= '.'; }
                            $header_num .= $num_headers_count[$i];
                        }
                    }

                    $anchor = $header_num ? "section$header_num" : "toc$count";

                    $toc[] = "<li type=disc><a href=\"#$anchor\">$header_num $header</a>";
                }
            }
        }

        for ($i = 0; $i < $prev_level; $i++)
            $toc[] = '</ul>';

        $toc = implode("\n", $toc);

        $return_value = '<table cellspacing="0" cellpadding="3" border=0">' .
                        '<tr id="showtoc' . $this->i . '" style="display: none;">' .
                        '<td><b>Table of contents</b> [ <a href="javascript:showToc(' . $this->i . ');">show</a> ]</td></tr>' .
                        '<tr id="thetoc' . $this->i . '"><td><b>Table of contents</b> ' .
                        '[ <a href="javascript:hideToc(' . $this->i . ');">hide</a> ]' . $toc . '</td></tr></table>';

        return "$TocJavascriptFunctions\n\n$return_value";
    }
}

return 1;

?>
