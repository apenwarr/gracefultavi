<?php

class Macro_CsvTable
{
    var $pagestore;

    function parse($args, $page)
    {
        global $EditCols, $EditRows, $FlgChr, $HTTP_POST_VARS, $page;

        $o = '<h2>CSV File to Wiki Table Converter</h2>';

        if ($HTTP_POST_VARS['csvtable'])
        {
            $csv = $HTTP_POST_VARS['csvtable'];
            if (get_magic_quotes_gpc()) {
                $csv = stripslashes($csv);
            }

            $wiki_table = '*';
            foreach (split("\n", $csv) as $line)
            {
                $line = trim($line);
                if (!$line) { continue; }
                $line = preg_replace('/^"(.*)"$/', '\\1', $line);
                $line = str_replace('""', $FlgChr, $line);
                $cols = explode('","', $line);
                foreach ($cols as $i => $value)
                {
                    if ($value)
                    {
                        $cols[$i] = str_replace($FlgChr, '"', $value);
                    }
                    else
                    {
                        unset($cols[$i]);
                    }
                }
                $wiki_table .= '||'.implode('||', $cols).'||'."\n";
            }

            $o .=
                '<p>Here is the Wiki Table text.
                <form name="csvtowikitableform"
                    action="?page='.htmlspecialchars($page).'" method="POST">
                <p><textarea name="csvtable" rows="'.$EditRows.'"
                    cols="'.$EditCols.'" wrap="virtual">'.
                    htmlspecialchars($wiki_table).'</textarea>
                </form>';

        }
        else
        {
            $o .= '
                <p>Paste in your csv file and hit submit.
                <p>Each table cell must be enclosed with double quotes, and each
                   actual double quote char must be doubled.
                <form name="csvtowikitableform"
                    action="?page='.htmlspecialchars($page).'" method="POST">
                <p><input type="submit" value="Submit">
                <p><textarea name="csvtable" rows="'.$EditRows.'"
                    cols="'.$EditCols.'" wrap="virtual"></textarea>
                </form>';
        }

        return $o;
    }
}

return 1;

?>
