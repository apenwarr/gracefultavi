<?php

/* Table Of Content generator
 *
 * Creates a table of content based on the page's headers.
 *
 * The param is the maximum hierarchy level to display.
 *
 */

$TocJavascriptFunctions = <<<JAVASCRIPT
<script language="javascript">
<!--
function hideToc()
{
    document.getElementById('showtoc').style.display = 'block';
    document.getElementById('toc').style.display = 'none';
}

function showToc(i)
{
    document.getElementById('toc').style.display = 'block';
    document.getElementById('showtoc').style.display = 'none';
}
//-->
</script>
JAVASCRIPT;


class Macro_Toc
{
    var $i = 0;

    function parse($max_level, $page)
    {
        global $pagestore, $MaxHeading, $ParseEngine;
        global $document, $TocJavascriptFunctions;

        $this->i++;
        if ($this->i > 1)
            return '';

        $toc = array();
        $count = 0;
        foreach(explode("\n", $document) as $line)
        {
            if (preg_match('/^(=+) (.*) (=+)$/', $line, $result) &&
                strlen($result[1]) === strlen($result[3]))
            {
                if (($level = strlen($result[1])) > $MaxHeading)
                    $level = $MaxHeading;

                if ($level <= $max_level)
                {
                    $header = parseText($result[2], $ParseEngine, $page);
                    $header = preg_replace('/<[^>]+>/i', '', $header);
                    //$header = htmlspecialchars(trim($header));

                    $count++;
                    $toc[] = str_repeat(' ', $level-1) . "1.<a href=\"#toc$count\">$header</a>";
                }
            }
        }

        $toc = implode("\n", $toc);

        $toc = parseText($toc, array('parse_indents', 'parse_elements'), $page);

        $return_value = '<table cellspacing="0" cellpadding="3" border=0">' .
                        '<tr id="showtoc" style="display: none;">' .
                        '<td><b>Table of content</b> [ <a href="javascript:showToc();">show</a> ]</td></tr>' .
                        '<tr id="toc"><td><b>Table of content</b> ' .
                        '[ <a href="javascript:hideToc();">hide</a> ]' . $toc . '</td></tr></table>';

        return "$TocJavascriptFunctions\n\n$return_value";
   }
}

return 1;

?>
