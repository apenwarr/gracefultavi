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
        global $pagestore, $MaxHeading;
        global $document, $TocJavascriptFunctions;

        $document = parseText($document, array('parse_htmlpre', 'parse_nowiki'), $page);

        if (!$max_level) $max_level = $MaxHeading; // maximum for the wiki
        if (!$max_level) $max_level = 9;           // maximum in html

        $this->i++;
        if ($this->i > 1)
            $TocJavascriptFunctions = '';

        // find highest header level
        $prevLevel = $max_level;
        foreach (explode("\n", $document) as $line)
        {
            if (preg_match('/^\s*(=+)([^=]*)(=+)\s*$/', $line, $result) &&
                strlen($result[1]) === strlen($result[3]))
            {
                $level = min(strlen($result[1]), $max_level);

                if ($level < $prevLevel)
                    $prevLevel = $level;

            }
        }
        $prevLevel--;

        $toc = array();
        $count = 0;
        foreach (explode("\n", $document) as $line)
        {
            if (preg_match('/^\s*(=+)([^=]*)(=+)\s*$/', $line, $result) &&
                strlen($result[1]) === strlen($result[3]))
            {
                $level = strlen($result[1]);
                $count++;

                if ($level <= $max_level)
                {
                    $indentIncrease = $level - $prevLevel;
                    for ($i = 0; $i < $indentIncrease; $i++)
                    {
                        $toc[] = '<ol>';
                        if ($i != ($indentIncrease - 1))
                            $toc[] = '<li>';
                    }
                    for ($i = $indentIncrease; $i < 0; $i++)
                        $toc[] = '</ol>';

                    $prevLevel = $level;

                    $header = $result[2];
                    $header = preg_replace('/<[^>]+>/', '', $header);
                    $header = preg_replace('/[!\\[\\]]/', '', $header);

                    $toc[] = "<li><a href=\"#toc$count\">$header</a>";
                }
            }
        }

        for ($i = 0; $i < $prevLevel; $i++)
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
