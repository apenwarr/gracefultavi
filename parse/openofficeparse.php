<?php
global $OdtParseEngine, $OdtDisplayEngine;

$OdtParseEngine = array(
    'parse_odt_elem_flag',
    'parse_odt_htmlpre',
    'parse_odt_nowiki',
    'parse_odt_htmlanchor',
    #'parse_htmltags',
    'parse_odt_img',
    'parse_hyperlink_ref',
    'parse_hyperlink_description',
    'parse_odt_macros',
    'parse_hyperlink',
    #'parse_odt_transclude',
    'parse_freelink',
    'parse_interwiki',
    'parse_wikiname',
    'parse_teletype',
    'parse_heading',
    'parse_odt_table',
    'parse_odt_horiz',
    'parse_odt_indents',
    'parse_odt_bold',
    'parse_highlight',
    'parse_odt_italic',
    'parse_odt_underline',
    'parse_odt_strike',
    'parse_singlequote',
    'parse_odt_newline',
    'parse_odt_htmlspecialchars',
    'parse_odt_elements'
    );

$OdtDisplayEngine = array(
    'raw'                   => 'odt_raw',
    'paragraph_start'       => 'odt_paragraph_start',
    'paragraph_end'         => 'odt_paragraph_end',
    'paragraph_empty'       => 'odt_paragraph_empty',
    'pre'                   => 'odt_pre',
    'nowiki'                => 'odt_nowiki',
    'url'                   => 'odt_url',
    'anchor'                => 'odt_anchor',
    'ref'                   => 'odt_ref',
    'interwiki'             => 'odt_url',
    'tt_start'              => 'odt_monospaced_start',
    'tt_end'                => 'odt_monospaced_end',
    'singlequote_start'     => 'odt_monospaced_start',
    'singlequote_end'       => 'odt_monospaced_end',
    'head_start'            => 'odt_head_start',
    'head_end'              => 'odt_head_end',
    'hr'                    => 'odt_hr',
    'indent_list_start'     => 'odt_list_start',
    'indent_list_end'       => 'odt_list_end',
    'indent_item_start'     => 'odt_listitem_start',
    'indent_item_end'       => 'odt_listitem_end',
    'bold_start'            => 'odt_bold_start',
    'bold_end'              => 'odt_bold_end',
    'highlight_start'       => 'odt_highlight_start',
    'highlight_end'         => 'odt_highlight_end',
    'highlightpass_start'   => 'odt_highlightpass_start',
    'highlightpass_end'     => 'odt_highlightpass_end',
    'highlightfail_start'   => 'odt_highlightfail_start',
    'highlightfail_end'     => 'odt_highlightfail_end',
    'italic_start'          => 'odt_italic_start',
    'italic_end'            => 'odt_italic_end',
    'underline_start'       => 'odt_underline_start',
    'underline_end'         => 'odt_underline_end',
    'strike_start'          => 'odt_strike_start',
    'strike_end'            => 'odt_strike_end',
    );

/**
 * Transforms Functions
 */

function parse_odt_elem_flag($text)
{
    global $FlgChr;

    // Hide element flags (0xFF)
    return preg_replace('/' . $FlgChr . '/e', "new_entity(array('raw', '$FlgChr'))", $text, -1);
}

function parse_odt_htmlpre($text)
{
    global $Entity, $FlgChr;
    static $in_pre = 0;

    if ($in_pre)
    {
        // Find first single </pre> and stops in-pre.
        if (!(($pos = strpos(strtolower($text), '</pre>')) === false))
        {
            $in_pre = 0;
            $preEnding = substr($text, 0, $pos); // $pos + strlen('</pre>')
            $afterPre = substr($text, $pos+6);
            $text = new_entity(array('pre', $preEnding)) .
                    new_entity(array('paragraph_empty')) .
                    new_entity(array('paragraph_start')) .
                    $afterPre;

            // Find all <pre></pre> couples on the same line.
            return preg_replace('/(.*)(<pre>)(.*)(<\\/pre>)(.*)/Uie',
                                "q1('\\1') .
                                 new_entity(array('paragraph_end')) .
                                 new_entity(array('paragraph_empty')) .
                                 new_entity(array('pre', q1('\\3'))) .
                                 new_entity(array('paragraph_empty')) .
                                 new_entity(array('paragraph_start')) .
                                 q1('\\5')",
                                $text, -1);
        }

        return new_entity(array('pre', $text));
    }
    else
    {
        // Find all <pre></pre> couples on the same line.
        $text = preg_replace('/(.*)(<pre>)(.*)(<\\/pre>)(.*)/Uie',
                             "q1('\\1') .
                              new_entity(array('paragraph_end')) .
                              new_entity(array('paragraph_empty')) .
                              new_entity(array('pre', q1('\\3'))) .
                              new_entity(array('paragraph_empty')) .
                              new_entity(array('paragraph_start')) .
                              q1('\\5')",
                             $text, -1);

        // Find first remaining single <pre> and starts the in-pre
        if (!(($pos = strpos(strtolower($text), '<pre>')) === false))
        {
            $in_pre = 1;
            $beforePre = substr($text, 0, $pos);
            $preBeginning = substr($text, $pos+5);
            return $beforePre .
                   new_entity(array('paragraph_end')) .
                   new_entity(array('paragraph_empty')) .
                   new_entity(array('pre', $preBeginning));
        }

        return $text;
    }
}

function parse_odt_nowiki($text)
{
    return preg_replace("/```(.*)```/Ue",
                        "new_entity(array('nowiki', parse_elements(q1('\\1'))))",
                        $text, -1);
}

// Ensures that links won't be created inside html <a> tags, i.e. between <a>
// and </a>. It is used only by parse_htmlanchor. The 2 regular expression
// pattern were taken/inspired from 'parse_hyperlink' and 'parse_wikiname'.
function odt_avoid_links($text)
{
  global $LinkPtn, $UrlPtn;

  // disable urls
  $text = preg_replace("/(^|[^A-Za-z])($UrlPtn)(\$|[^\\/?=&~A-Za-z0-9])/e",
                       "q1('\\1') . new_entity(array('raw', q1('\\2'))) . q1('\\5')",
                       $text, -1);

  // disable wikinames
  $text = preg_replace("/(^|[^A-Za-z!])($LinkPtn)((\#[-A-Za-z0-9]+)?)(\"\")?/e",
                       "q1('\\1') . '!' . q1('\\2') . '\\7'",
                       $text, -1);

  return $text;
}

function parse_odt_htmlanchor($text)
{
    $text = preg_replace("/<a[^>]+?href=.*?([^\"\s>]+)[^>]*>([^<>]+)<\/a>/ei",
                         "new_entity(array('url', q1('\\1'), avoid_links(q1('\\2'))))", $text, -1);

    $text = preg_replace("/<a[^>]+?name=.*?([^\"\s>]+)[^>]*>.+?<\/a>/ei",
                         "new_entity(array('anchor', q1('\\1')))", $text, -1);
    $text = preg_replace("/<a[^>]+?name=.*?([^\"\s>]+)[^>]*\/>/ei",
                         "new_entity(array('anchor', q1('\\1')))", $text, -1);

    return $text;
}

function parse_odt_img($text) {
    return preg_replace('/<img [^>]*src=([^> ]+)[^>]*>/ei',
        "new_entity(array('raw', odt_image(odt_img_trim_quotes(q1('\\1')))))",
        $text, -1);
}

function parse_odt_macros($text)
{
    global $DiffScript, $page, $ViewBase, $ViewMacroEngine;

    // support for images with Attach macro
    $text = preg_replace('/\\[\\[Attach image ([^]]+)]]/e',
        "new_entity(array('raw', odt_image('attachments/'.trim(q1('\\1')))))",
        $text, -1);

    // support for RevisionHistory
    if (preg_match('/\\[\\[RevisionHistory( [^]]*)?]]/', $text, $matches)) {
        $text = $ViewMacroEngine['RevisionHistory']->parse($matches[1], $page);

        if ($text) {
            // convert table html to wiki markup
            $text = str_replace("<table>\n", '', $text);
            $text = str_replace('</table>', '', $text);
            $text = str_replace('<td></td>', '<td> </td>', $text);
            $text = str_replace('<tr><td>', '||', $text);
            $text = str_replace('</td><td>', '||', $text);
            $text = str_replace('</td></tr>', '||', $text);

            // misc cleanup
            $text = str_replace('<div align="right">', '', $text);
            $text = str_replace('</div>', '', $text);
            $text = str_replace('&nbsp;', '', $text);

            // clean history links
            $re = '/<a href="'.preg_quote($ViewBase).'[^"]+?&amp;version='.
                  '[^"]+?">(.+?)<\/a>/';
            $text = preg_replace($re, "\\1", $text);
            $re = '/<a href="'.preg_quote($DiffScript).'[^"]+?">(.+?)<\/a>/';
            $text = preg_replace($re, "\\1", $text);

            // convert author links
            $re = '/<a href="'.preg_quote($ViewBase).'[^"]+?">(.+?)<\/a>/e';
            $text = preg_replace($re, "wikiname_token(q1('\\1'), '')", $text);

            $odt = '';
            foreach (explode("\n", $text) as $line) {
                $line = parse_wikiname($line);
                $line = parse_odt_table($line);
                $line = parse_odt_bold($line);
                $odt .= $line;
            }

            $text = $odt;
        }
    }

    // ignores any other macro
    return preg_replace('/(\\[\\[([^] ]+( [^]]+)?)]])/e',
                        "new_entity(array('raw', q1('\\1')))", $text, -1);
}

function parse_odt_table($text)
{
    global $page, $parseOdtTableStyle;
    static $inTable = false;
    static $tableCount = 0;
    static $table = array();

    if (!isset($parseOdtTableStyle)) {
        $parseOdtTableStyle = '';
    }

    $tableOdt = '';

    if (preg_match('/^\*?(\|\|)+.*(\|\|)\s*$/', $text)) {
        if (!$inTable) {
            $inTable = true;
            $tableCount++;
            $table = array();
        }

        $cols = explode('||', $text);
        array_pop($cols);
        array_shift($cols);
        $row = array();
        $colspan = 1;
        foreach ($cols as $col) {
            if ($col == '') {
                $colspan++;
            } else {
                $row[] = array($colspan, $col);
                $colspan = 1;
            }
        }
        if ($colspan > 1) {
            $row[] = array($colspan, '');
        }

        $table[] = $row;
        $text = '';
    } elseif ($inTable) {
        $inTable = false;

        $maxColCount = 0;
        foreach ($table as $row) {
            $curColCount = 0;
            foreach ($row as $cell) {
                $curColCount += $cell[0];
            }
            if ($curColCount > $maxColCount) {
                $maxColCount = $curColCount;
            }
        }

        $parseOdtTableStyle .=
            odt_table_style($tableCount, $maxColCount);

        $tableOdt = new_entity(array('raw', odt_paragraph_end())).
            new_entity(array('raw',
                       odt_table_start($tableCount, $maxColCount)));

        $rowCount = 1;
        foreach ($table as $row) {
            $tableOdt .= new_entity(array('raw', odt_table_row_start()));
            $firstRow = ($rowCount == 1);
            $cellCount = 1;
            foreach ($row as $cell) {
                $colSpan = $cell[0];
                $lastCell = (($cellCount+$colSpan-1) == $maxColCount);
                $tableOdt .= new_entity(array('raw',
                    odt_table_cell_start($tableCount, $firstRow, $lastCell,
                                         $colSpan))).
                    $cell[1].
                    new_entity(array('raw', odt_table_cell_end($colSpan)));
                $cellCount += $colSpan;
            }
            // fills row with empty cells when missing
            for ($i = $cellCount; $i <= $maxColCount; $i++) {
                $lastCell = ($i == $maxColCount);
                $tableOdt .= new_entity(array('raw',
                    odt_table_cell_start($tableCount, $firstRow, $lastCell,
                                         1))).
                    new_entity(array('raw', odt_table_cell_end(1)));
            }
            $tableOdt .= new_entity(array('raw', odt_table_row_end()));
            $rowCount++;
        }
        $tableOdt .= new_entity(array('raw', odt_table_end())).
                     new_entity(array('raw', odt_paragraph_start()));
    }

    if ($tableOdt != '') {
        $text = $tableOdt . parse_odt_newline($text);
    }

    return $text;
}

function parse_odt_horiz($text)
{
    $text = preg_replace("/^-{4,}\s*/e", "new_entity(array('hr'))",
                         $text, -1);

    return preg_replace("/(&lt;|<)hr>(\\n(\\r)?)?/e", "new_entity(array('hr'))",
                        $text, -1);
}

function parse_odt_indents($text)
{
    global $MaxNesting;
    static $indentPrevLevel = -1;
    static $indentPrefixString = '';
    static $indentPrevLineIsBlank = 0;
    static $indentStealLine = 0;
    static $pending_p = '';

    // Indentation increase of more than on level will be corrected to only one.
    $auto_fix_indent_leap = 1; // this value is boolean

    // Fix notation for ordered list, changes:
    //  - '[0-9].' to '#'
    //  - '[a-z].' to INDENTS_TYPE_A, i.e. ascii 182 (266 in octal)
    //  - 'ii.' to INDENTS_TYPE_I, i.e. ascii 187 (273 in octal)
    $text = preg_replace('/^(\s*)[0-9]{1,2}\.(.+\\n?$)/', '\\1#\\2', $text);
    $text = preg_replace('/^(\s*)[a-z]\.(.+\\n?$)/', '\\1'.INDENTS_TYPE_A.'\\2', $text);
    $text = preg_replace('/^(\s*)ii\.(.+\\n?$)/', '\\1'.INDENTS_TYPE_I.'\\2', $text);

    // Fix notation for citation
    $cite_pattern = '^(\s*>)+';
    if (preg_match("/$cite_pattern/", $text, $matches))
    {
        $auto_fix_indent_leap = 0;
        $cite_blank_line = preg_match("/$cite_pattern\s*$/", $text);
        $cite_level = preg_match_all('/>/', $matches[0], $dummy);
        $text = preg_replace("/$cite_pattern/", str_repeat(' ', $cite_level-1).'>', $text);
        if ($cite_blank_line)
            $text = preg_replace('/^(.*)$/', '\\1<p>', $text);
    }

    // Locate the indent prefix characters.
    $matched = preg_match('/^(\s*)([:\\-\\*#>\266\273])([^:\\-\\*#>].*\\n?)$/', $text, $result);
    if (!$matched) {
        preg_match('/^(\s*)(:)(-.*\\n?)$/', $text, $result);
    }

    if(array_key_exists(1, $result) && isset($result[1]))
        $indentSpaces = $result[1];
    if(array_key_exists(2, $result) && isset($result[2]))
        $indentChar = $result[2];
    if(array_key_exists(3, $result) && isset($result[3]))
        $indentText = $result[3];

    // No list on last line, no list on this line. Bail out:
    if ($indentPrevLevel == -1 && !isset($indentChar) && !$indentStealLine)
        return $text; // Common case fast.

    $isBlankLine = ((trim($text) == '') ? 1 : 0);

    if (!$indentStealLine)
    {
        if (isset($indentChar))
        {
            if ($auto_fix_indent_leap)
                $indentCurLevel = min($indentPrevLevel + 1, strlen($indentSpaces), $MaxNesting);
            else
                $indentCurLevel = min(strlen($indentSpaces), $MaxNesting);

            $fixup = '';

            if ($indentCurLevel > $indentPrevLevel)
            {
                // add any pending <p>
                $fixup .= $pending_p;
                $pending_p = '';

                if ($auto_fix_indent_leap)
                    $fixup .= odt_entity_list($indentChar, 'start');
                else
                    $fixup .= str_repeat(odt_entity_list($indentChar, 'start'),
                                         $indentCurLevel - $indentPrevLevel);
            }
            else
            {
                // close previously openend levels, until current level
                for ($i = $indentPrevLevel; $i > $indentCurLevel; $i--)
                    $fixup .= odt_entity_listitem($indentPrefixString[$i], 'end') .
                              odt_entity_list($indentPrefixString[$i], 'end');

                // close previous list item
                $fixup .= odt_entity_listitem($indentPrefixString[$indentCurLevel], 'end');

                // if the indent type ([:#-*]) is different from previous at same level
                if ($indentPrefixString[$indentCurLevel] != $indentChar)
                    $fixup .= odt_entity_list($indentPrefixString[$indentCurLevel], 'end');

                // add any pending <p>
                $fixup .= $pending_p;
                $pending_p = '';

                // if the indent type ([:#-*]) is different from previous at same level
                if ($indentPrefixString[$indentCurLevel] != $indentChar)
                    $fixup .= odt_entity_list($indentChar, 'start');
            }

            // open new list item
            $fixup .= odt_entity_listitem($indentChar, 'start');

            $text = $fixup . $indentText;

            if ($auto_fix_indent_leap || $indentCurLevel <= $indentPrevLevel)
                $indentPrefixString = substr($indentPrefixString, 0, $indentCurLevel) .
                                      $indentChar;
            else
                $indentPrefixString = substr($indentPrefixString, 0, $indentPrevLevel+1) .
                                      str_repeat($indentChar, $indentCurLevel-$indentPrevLevel);

            $indentPrevLevel = $indentCurLevel;
        }
        else
        {
            // Note: Every parsing functions is called at the end of the page with
            // an empty string, i.e. without a carriage return, since some stateful
            // parsers need to perform final processing. This is the case for
            // indents and this is why $text is tested against ''.
            if ($isBlankLine && $text != '')
            {
                $text = $indentPrevLineIsBlank ? '' : '<p>';
            }
            else if ($indentPrevLineIsBlank || $text == '')
            {
                // Check if there's leading spaces, telling to stay in the same
                // list item but to start a new paragraph.
                // If no leading spaces, the indents are completely closed.
                $i = 0; // just in case...
                if (preg_match('/^\s*/', $text, $leadingSpaces))
                    $i = strlen($leadingSpaces[0]);

                if ($i < strlen($indentPrefixString))
                {
                    // We're at a lower nesting level, end dangling lists up
                    // to the nesting level specified by the leading spaces
                    // and add any pending <p> after the last list end.
                    for ($j = $i; $j < strlen($indentPrefixString); $j++)
                    {
                        $text = odt_entity_listitem($indentPrefixString[$j], 'end') .
                                odt_entity_list($indentPrefixString[$j], 'end') .
                                $pending_p .
                                $text;
                        $pending_p = '';
                    }
                    $indentPrefixString = substr($indentPrefixString, 0, $i);
                    $indentPrevLevel = $i - 1;
                }
                else
                {
                    // $indentChar is set here to keep the line stealing working
                    $indentChar = ' ';
                    $text = $pending_p . $text;
                    $pending_p = '';
                }
            }
            else
            {
                $text = ' ' . trim($text);
                $indentChar = ' '; // same as above
            }
        }
    }

    $indentPrevLineIsBlank = $isBlankLine;

    // Check if *we* have a trailing '\' to "steal" the next line.
    if (isset($indentChar) || isset($indentStealLine))
    {
        if (preg_match('/(^|[^\\\\])(\\\\\\\\)*\\\\$/', $text))
        {
            $text = preg_replace('/\\\\$/', "\n", $text);
            $indentStealLine = 1;
        }
        else
            $indentStealLine = 0;
    }

    // holds any single <p> and prints it later, see above
    if ($text == '<p>') {
        $pending_p = new_entity(array('raw', odt_paragraph_end())) .
                     new_entity(array('raw', odt_paragraph_start()));
        $text = '';
    }

    return $text;
}

function odt_entity_list($type, $fn)
{
    static $level = 0;

    $level += (($fn == 'start') ? 1 : -1);

    if ($type == '-' || $type == '*') {
        return new_entity(array('indent_list_' . $fn, 'bullet', $level));
    } else if($type == ':' || $type == ';') {
        return new_entity(array('indent_list_' . $fn, 'indent', $level));
    } else if($type == '#') {
        return new_entity(array('indent_list_' . $fn, 'number', $level));
    } else if($type == INDENTS_TYPE_A) {
        return new_entity(array('indent_list_' . $fn, 'letter', $level));
    } else if($type == INDENTS_TYPE_I) {
        return new_entity(array('indent_list_' . $fn, 'roman', $level));
    } else if($type == '>') {
        return new_entity(array('indent_list_' . $fn, 'cite', $level));
    }
}

function odt_entity_listitem($type, $fn)
{
    return new_entity(array('indent_item_' . $fn));
}

function parse_odt_bold($text)
{
  $text = preg_replace("/<b>(()|[^'].*)<\/b>/Uei", "pair_tokens('bold', q1('\\1'))",
                       $text, -1);

  return preg_replace("/'''(.*)'''/Ue", "pair_tokens('bold', q1('\\1'))",
                      $text, -1);
}

function parse_odt_italic($text)
{
  $text = preg_replace("/<i>(()|[^'].*)<\/i>/Uei", "pair_tokens('italic', q1('\\1'))",
                       $text, -1);

  return preg_replace("/\*(()|[^'].*)\*/Ue", "pair_tokens('italic', q1('\\1'))",
                      $text, -1);
}

function parse_odt_underline($text)
{
  $text = preg_replace("/<u>(()|[^'].*)<\/u>/Uei", "pair_tokens('underline', q1('\\1'))",
                       $text, -1);

  return preg_replace("/\b_(.+)_\b/Ue", "pair_tokens('underline', q1('\\1'))",
                      $text, -1);
}

function parse_odt_strike($text)
{
  return preg_replace("/<strike>(()|[^'].*)<\/strike>/Uei", "pair_tokens('strike', q1('\\1'))",
                      $text, -1);
}

function parse_odt_newline($text)
{
    static $last = array('', '');

    // treat lines with only spaces as empty
    $thisline = preg_replace('/^ */', '', $text);

    // More than two consecutive newlines fold into only two newlines.
    if ($last[0] == "\n" && $last[1] == "\n" && $thisline == "\n")
        return '';
    $last[0] = $last[1];
    $last[1] = $thisline;

    if ($thisline == "\n" || $thisline == "\n\r") {
        $text =
        new_entity(array('raw', odt_paragraph_end())).
                new_entity(array('raw', odt_paragraph_start())).
        new_entity(array('raw', odt_paragraph_end())).
                new_entity(array('raw', odt_paragraph_start())).
                $text;
    }

    // deal with <br>
    $text = preg_replace('/<br>/i',
        new_entity(array('raw', odt_paragraph_end())) .
        new_entity(array('raw', odt_paragraph_start())),
        $text);

    return $text;
}

function parse_odt_htmlspecialchars($text) {
    return htmlspecialchars($text);
}

function parse_odt_elements($text)
{
    global $FlgChr;
    return preg_replace("/$FlgChr(\\d+)$FlgChr/e",
                        "generate_odt_element(q1('\\1'))", $text, -1);
}

function generate_odt_element($text)
{
  global $Entity, $OdtDisplayEngine;

  for ($i = 1; $i < 6; $i++)
    if (!isset($Entity[$text][$i]))
        $Entity[$text][$i] = '';

if (!$OdtDisplayEngine[$Entity[$text][0]]) {
    print_r($text);
    print '!';
    print_r($Entity);
    print '!';
    print_r($OdtDisplayEngine);
    exit;
}

  return $OdtDisplayEngine[$Entity[$text][0]]($Entity[$text][1],
                                              $Entity[$text][2],
                                              $Entity[$text][3],
                                              $Entity[$text][4],
                                              $Entity[$text][5]);
}


/**
 * ODT Functions
 */

function odt_raw($text)
  { return $text; }

function odt_paragraph_start()
  { return '<text:p text:style-name="Standard">'; }
function odt_paragraph_end()
  { return '</text:p>'; }
function odt_paragraph_empty()
  { return '<text:p text:style-name="Standard"/>'; }

function odt_pre($text)
{
    $text = htmlspecialchars($text);
    if ($text) {
        $text = preg_replace('/^(\s+)/e', "odt_pre_spaces('\\1')", $text);
        return '<text:p text:style-name="TPre">'.$text.'</text:p>';
    } else {
        return '';
    }
}
function odt_pre_spaces($s)
{
    $n = strlen($s);
    if ($n == 1) {
        return '<text:s/>';
    } elseif ($n > 1) {
        return '<text:s text:c="'.$n.'"/>';
    }
}

function odt_nowiki($text)
  { return $text; }

function odt_img_trim_quotes($src)
{
    $src = preg_replace('/^[\'"]/', '', $src);
    $src = preg_replace('/[\'"]$/', '', $src);
    return $src;
}

function odt_image($url)
{
    global $odtUrlImages, $TempDir;
    static $imageCount = 0;

    // fix silly jpeg extension
    preg_match('/(.jpe?g|.png|.gif)$/i', $url, $matches);
    $ext = strtolower($matches[0]);
    if ($ext == '.jpeg') {
        $ext = '.jpg';
    }

    $imageCount++;

    // create temporary file
    $randomPrefix = md5(uniqid(rand(), true));
    $filename = "$TempDir/${randomPrefix}Image$imageCount$ext";
    if (!$fh = fopen($filename, 'w')) {
        return htmlspecialchars($url);
    }
    if (!($imageContent = @file_get_contents($url))) {
        fclose($fh);
        @unlink($filename);
        return htmlspecialchars($url);
    }
    if (fwrite($fh, $imageContent) === FALSE) {
        fclose($fh);
        @unlink($filename);
        return htmlspecialchars($url);
    }
    fclose($fh);

    // keep filename, the image file is moved later in the process
    $odtUrlImages[] = $filename;

    // width and height default to 5 cm
    $width = 5;
    $height = 5;

    // checks if gd is loaded, and image format supported
    // if yes, uses gd to figure out image dimension
    $formats = array('.gif' => IMG_GIF, '.jpg' => IMG_JPG,
                     '.png' => IMG_PNG);
    if (extension_loaded('gd') && (imagetypes() & $formats[$ext])) {
        // create gd image
        $gdFcnSuffix = substr($ext, 1);
        if ($gdFcnSuffix == 'jpg') {
            $gdFcnSuffix = 'jpeg';
        }
        $gdCreateFcn = 'imagecreatefrom'.$gdFcnSuffix;
        $img = $gdCreateFcn($url);

        // figure out width and height in cm
        $pixelsPerCm = 28.319;
        $maxCmWidth = 17.59;
        $width = imagesx($img) / $pixelsPerCm;
        $height = imagesy($img) / $pixelsPerCm;
        if ($width > $maxCmWidth) {
            $height = $height * $maxCmWidth / $width;
            $width = $maxCmWidth;
        }
        $width = round($width * 1000) / 1000;
        $height = round($height * 1000) / 1000;

        imagedestroy($img);
    }

    return '<draw:frame draw:style-name="TImage" draw:name="graphics'.
           $imageCount.'" text:anchor-type="as-char" svg:width="'.$width.
           'cm" svg:height="'.$height.'cm" draw:z-index="0"><draw:image xl'.
           'ink:href="Pictures/Image'.$imageCount.$ext.'" xlink:type="simp'.
           'le" xlink:show="embed" xlink:actuate="onLoad"/></draw:frame>';
}

function odt_url($url, $text)
{
    if ($url == $text
        && preg_match('/(.jpe?g|.png|.gif)$/i', $text))
    {
        return odt_image($text);
    }

    $url = htmlspecialchars($url);
    $text = htmlspecialchars($text);

    return '<text:a xlink:type="simple" xlink:href="'.$url.'">'.$text.
           '</text:a>';
}

function odt_anchor($anchor)
{
    $anchor = htmlspecialchars($anchor);
    return '<text:bookmark text:name="'.$anchor.'"/>';
}

function odt_ref($refPage, $appearance, $hover = '', $anchor = '',
                 $anchor_appearance = '')
{
    global $db, $SeparateLinkWords, $page, $pagestore, $ScriptBase;

    if ($page == 'RecentChanges') {
        $p_exists = $pagestore->page_exists($refPage);
    } else {
        $p = new WikiPage($db, $refPage);
        $p_exists = $p->exists();

        // automatically handle plurals
        if (!$p_exists) {
            foreach (array('s', 'es') as $plural) {
                if (substr($refPage, -strlen($plural)) == $plural) {
                    $temp_refPage =
                        substr($refPage, 0, strlen($refPage)-strlen($plural));
                    $p = new WikiPage($db, $temp_refPage);
                    if ($p_exists = $p->exists()) {
                        $refPage = $temp_refPage;
                        break;
                    }
                }
            }
        }
    }

    if ($p_exists) {
        if ($SeparateLinkWords && $refPage == $appearance) {
            $appearance = html_split_name($refPage);
        }
        $url = $ScriptBase . '?page=' . urlencode($refPage) . $anchor;
        $result = odt_url($url, $appearance.$anchor_appearance);
    } else {
        $result = "";
        if (validate_page($refPage) == 1       // Normal WikiName
            && $appearance == $refPage)        // ... and is what it appears
        {
            $result = $refPage;
        } else {                               // Free link.
            // Puts the appearance between parenthesis if there's a space in it.
            if (strpos($appearance, ' ') === false) {
                $tempAppearance = $appearance;
            } else {
                $tempAppearance = "($appearance)";
            }
            $result = $tempAppearance;
        }
    }

    return $result;
}

function odt_monospaced_start()
{
    return '<text:span text:style-name="TMonospaced">';
}
function odt_monospaced_end()
{
    return '</text:span>';
}

function odt_head_start($level, $underline, $numbering, $style_inline,
                        $show_edit_link)
{
    static $count = 0; $count++;
    $anchor = $numbering ? "section$numbering" : "toc$count";
    $styleName = ($underline ? 'HU' : 'Heading_20_') . $level;

    return odt_paragraph_end().
           '<text:h text:style-name="'.$styleName.'" '.
           'text:outline-level="'.$level.'">'.
           odt_anchor($anchor);
}
function odt_head_end($level)
{
    return '</text:h>'.odt_paragraph_start();
}

function odt_table_start($tableNumber, $colCount)
{
    return '<table:table table:name="Table'.$tableNumber.'" '.
           'table:style-name="Table'.$tableNumber.'">'.
           '<table:table-column table:style-name="Table'.$tableNumber.
           '.Columns" table:number-columns-repeated="'.$colCount.'"/>';
}
function odt_table_end()
{
    return '</table:table>';
}
function odt_table_row_start()
{
    return '<table:table-row>';
}
function odt_table_row_end()
{
    return '</table:table-row>';
}
function odt_table_cell_start($tableNumber, $firstRow, $lastCell, $colSpan = 1)
{
    $cellStyle = ($lastCell ? 'B' : 'A') . ($firstRow ? '1' : '2');
    $spanAttr = '';
    if ($colSpan > 1) {
        $spanAttr = ' table:number-columns-spanned="'.$colSpan.'"';
    }
    return '<table:table-cell table:style-name="Table'.$tableNumber.'.'.
           $cellStyle.'"'.$spanAttr.' office:value-type="string">'.
           '<text:p text:style-name="Table_20_Contents">';
}
function odt_table_cell_end($colSpan = 1)
{
    $return = '</text:p></table:table-cell>';
    if ($colSpan > 1) {
        $return .= str_repeat('<table:covered-table-cell/>', $colSpan-1);
    }
    return $return;
}

function odt_table_style($tableNumber, $colCount)
{
    $tableWidth = 17.59;
    $colWidth = round($tableWidth / $colCount * 1000) / 1000;
    $relColWidth = floor(65535 / $colCount);

    $style = <<<EOT
<style:style style:name="Table$tableNumber" style:family="table">
<style:table-properties style:width="${tableWidth}cm" table:align="margins"/>
</style:style>
<style:style style:name="Table$tableNumber.Columns" style:family="table-column">
<style:table-column-properties style:column-width="${colWidth}cm" style:rel-column-width="$relColWidth*"/>
</style:style>
<style:style style:name="Table$tableNumber.A1" style:family="table-cell">
<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.002cm solid #000000" fo:border-right="none" fo:border-top="0.002cm solid #000000" fo:border-bottom="0.002cm solid #000000"/>
</style:style>
<style:style style:name="Table$tableNumber.B1" style:family="table-cell">
<style:table-cell-properties fo:padding="0.097cm" fo:border="0.002cm solid #000000"/>
</style:style>
<style:style style:name="Table$tableNumber.A2" style:family="table-cell">
<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.002cm solid #000000" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.002cm solid #000000"/>
</style:style>
<style:style style:name="Table$tableNumber.B2" style:family="table-cell">
<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.002cm solid #000000" fo:border-right="0.002cm solid #000000" fo:border-top="none" fo:border-bottom="0.002cm solid #000000"/>
</style:style>
EOT;

    return str_replace("\n", '', $style);
}

function odt_hr()
{
    return odt_paragraph_end().
           '<text:p text:style-name="HR"/>'.
           odt_paragraph_empty().
           odt_paragraph_start();
}

function odt_list_start($type, $level)
{
    $style = '';

    if ($level == 1) {
        switch ($type) {
            case 'indent':
            case 'cite':
                $style = 'Plain';
                break;
            case 'number':
                $style = 'Numbering';
                break;
            case 'letter':
                $style = 'Letter';
                break;
            case 'roman':
                $style = 'Roman';
                break;
            default:
                $style = 'Bullet';
        }
        $style = ' text:style-name="Indent'.$style.'"';
    }

    return odt_paragraph_end() . '<text:list'.$style.'>';
}
function odt_list_end($type, $level)
{
    return '</text:list>' . odt_paragraph_start();
}
function odt_listitem_start()
{
    return '<text:list-item>'.odt_paragraph_start();
}
function odt_listitem_end()
{
    return odt_paragraph_end().'</text:list-item>';
}

function odt_bold_start()
{
    return '<text:span text:style-name="TBold">';
}
function odt_bold_end()
{
    return '</text:span>';
}

function odt_highlight_start()
{
    return '<text:span text:style-name="THighlight">';
}
function odt_highlight_end()
{
    return '</text:span>';
}
function odt_highlightpass_start()
{
    return '<text:span text:style-name="THighlightPass">';
}
function odt_highlightpass_end()
{
    return '</text:span>';
}
function odt_highlightfail_start()
{
    return '<text:span text:style-name="THighlightFail">';
}
function odt_highlightfail_end()
{
    return '</text:span>';
}

function odt_italic_start()
{
    return '<text:span text:style-name="TItalic">';
}
function odt_italic_end()
{
    return '</text:span>';
}

function odt_underline_start()
{
    return '<text:span text:style-name="TUnderline">';
}
function odt_underline_end()
{
    return '</text:span>';
}

function odt_strike_start()
{
    return '<text:span text:style-name="TStrike">';
}
function odt_strike_end()
{
    return '</text:span>';
}

?>
