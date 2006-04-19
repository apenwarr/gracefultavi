<?php

// The main parser components. Each of these takes a line of text and scans it
// for particular wiki markup. It converts markup elements to
// $FlgChr . x . $FlgChr, where x is an index into the global array $Entity,
// which contains descriptions of each markup entity. Later, these will be
// converted back into HTML (or, in the future, perhaps some other
// representation such as XML).

define('INDENTS_TYPE_A', chr(182));
define('INDENTS_TYPE_I', chr(187));

function parse_noop($text)
{
    return $text;
}

// The following function "corrects" for PHP's odd preg_replace behavior.
// Back-references have backslashes inserted before certain quotes
// (specifically, whichever quote was used around the backreference); this
// function removes remove those backslashes.

function q1($text)
  { return str_replace('\\"', '"', $text); }

function validate_page($page)
{
  global $FlgChr;

  $p = parse_wikiname($page, 1);
  if(preg_match('/^' . $FlgChr . '\\d+' . $FlgChr . '$/', $p))
    { return 1; }
  $p = parse_freelink('[' . $page . ']', 1);
  if(preg_match('/^' . $FlgChr . '\\d+' . $FlgChr . '$/', $p))
    { return 2; }
  return 0;
}

function parse_elem_flag($text)
{
  global $FlgChr;

  // Hide element flags (0xFF) from view.
  return preg_replace('/' . $FlgChr . '/e', "new_entity(array('raw', '$FlgChr'))", $text, -1);
}

function new_entity($array)
{
  global $Entity, $FlgChr;

  $Entity[count($Entity)] = $array;

  return $FlgChr . (count($Entity) - 1) . $FlgChr;
}

function parse_wikiname($text, $validate = 0)
{
  global $LinkPtn, $EnableWikiLinks;

  if(!$EnableWikiLinks) { return $text; }

  if($validate)
    { $ptn = "/(^|[^A-Za-z])($LinkPtn)(())(\"\")?/e"; }
  else
    { $ptn = "/(^|[^A-Za-z])(!?$LinkPtn)((\#[-A-Za-z0-9]+)?)(\"\")?/e"; }

  return preg_replace($ptn,
                      "q1('\\1') . wikiname_token(q1('\\2'), '\\4')",
                      $text, -1);
}

function wikiname_token($name, $anchor)
{
  if($name[0] == '!')                   // No-link escape sequence.
    { return substr($name, 1); }        // Trim leading '!'.
  return new_entity(array('ref', $name, $name, '', $anchor, $anchor));
}

function parse_freelink($text, $validate = 0)
{
  global $EnableFreeLinks;

  if(!$EnableFreeLinks) { return $text; }

  if($validate)
  {
    // Space removed from links between brackets
    // $ptn = "/\\[([-A-Za-z0-9 _+\\/.,']+)(())()\\]/e";
    $ptn = "/\\[([-A-Za-z0-9_+\\/.,']+)(())()\\]/e";

    return preg_replace($ptn,
                        "freelink_token(q1('\\1'), q1('\\3'), '\\5', '')",
                        $text, -1);
  }
  else
  {
    // Space removed from links between brackets
    // $basePtn = "\\[([-A-Za-z0-9 _+\\/.,']+)((\|[-A-Za-z0-9 _+\\/.,']+)?)((\#[-A-Za-z0-9]+)?)\\]";
    $basePtn = "\\[([-A-Za-z0-9_+\\/.,']+)((\|[-A-Za-z0-9 _+\\/.,']+)?)((\#[-A-Za-z0-9]+)?)\\]";

    // tranform freelinks with the "!" prefix into raw text
    $ptn = "/!$basePtn/e";
    $text = preg_replace($ptn,
                         "new_entity(array('raw', '[' . q1('\\1') . q1('\\3') . '\\5' . ']'))",
                         $text, -1);

    // parse freelinks without the "!" prefix
    $ptn = "/$basePtn/e";
    $text = preg_replace($ptn,
                         "freelink_token(q1('\\1'), q1('\\3'), '\\5', '')",
                         $text, -1);

    return $text;
  }
}

function freelink_token($link, $appearance, $anchor, $anchor_appearance)
{
  if($appearance == '')
    { $appearance = $link; }
  else
    { $appearance = substr($appearance, 1); }   // Trim leading '|'.
  return new_entity(array('ref', $link, $appearance, '',
                          $anchor, $anchor_appearance));
}

function parse_interwiki($text)
{
  global $InterwikiPtn;

  return preg_replace("/(^|[^A-Za-z])($InterwikiPtn)(\$|[^\\/=&~A-Za-z0-9])/e",
                      "q1('\\1') . interwiki_token(q1('\\3'), q1('\\4')) . q1('\\6')",
                      $text, -1);
}

function interwiki_token($prefix, $ref)
{
    global $pagestore;

    if (($url = $pagestore->interwiki($prefix)) != '') {
        if (preg_match_all('/\$(\d)/', $url, $matches)) {
            $ref_parts = explode(':', $ref);
            if (isset($matches[1]) && is_array($matches[1])) {
                foreach ($matches[1] as $num) {
                    $ref_part = isset($ref_parts[$num-1]) ? $ref_parts[$num-1] : '';
                    $url = preg_replace("/\\\$$num/", $ref_part, $url);
                }
            }
        } else {
            $url .= $ref;
        }

        return new_entity(array('interwiki', $url, $prefix . ':' . $ref));
  }

  return $prefix . ':' . $ref;
}

// Ensures that links won't be created inside html <a> tags, i.e. between <a>
// and </a>. It is used only by parse_htmlanchor. The 2 regular expression
// pattern were taken/inspired from 'parse_hyperlink' and 'parse_wikiname'.
function avoid_links($text)
{
  global $LinkPtn, $UrlPtn;

  // disable urls
  $text = preg_replace("/(^|[^A-Za-z])($UrlPtn)(\$|[^\\/?=&~A-Za-z0-9])/e",
                       "q1('\\1') . new_entity(array('raw', q1('\\2'))) . q1('\\5')",
                       $text, -1);

  // disable wikinames
  $text = preg_replace("/(^|[^A-Za-z!])($LinkPtn)((\#[-A-Za-z0-9]+)?)(\"\")?/e",
                       "q1('\\1') . '!' . q1('\\2') . '\\4'",
                       $text, -1);

  return $text;
}

function parse_htmlanchor($text)
{
  return preg_replace("/(<a[^>]+href[^>]+>)([^<>]+)(<\/a>)/Uei",
                      "new_entity(array('raw', q1('\\1'))) . avoid_links(q1('\\2')) . '\\3'", $text, -1);
}

function parse_htmltags($text)
{
    $tags = 'A|ABBR|ACRONYM|ADDRESS|APPLET|AREA|B|BASE|BASEFONT|BDO|BIG|BLOCKQUOTE|BODY|BR|BUTTON|CAPTION|CENTER|CITE|CODE|COL|COLGROUP|DD|DEL|DFN|DIR|DIV|DL|DT|EM|FIELDSET|FONT|FORM|FRAME|FRAMESET|H1|H2|H3|H4|H5|H6|HEAD|HR|HTML|I|IFRAME|IMG|INPUT|INS|ISINDEX|KBD|LABEL|LEGEND|LI|LINK|MAP|MENU|META|NOFRAMES|NOSCRIPT|OBJECT|OL|OPTGROUP|OPTION|P|PARAM|PRE|Q|S|SAMP|SCRIPT|SELECT|SMALL|SPAN|STRIKE|STRONG|STYLE|SUB|SUP|TABLE|TBODY|TD|TEXTAREA|TFOOT|TH|THEAD|TITLE|TR|TT|U|UL|VAR';

    $text = preg_replace('/(<(' . $tags . ')[^>]*?>)/ei', "new_entity(array('raw', q1('\\1')))", $text);

    return $text;
}

function parse_hyperlink_ref($text)
{
  global $UrlPtn;

  return preg_replace("/\\[($UrlPtn)]/Ue",
                      "url_token(q1('\\1'), '')", $text, -1);
}

function parse_hyperlink_description($text)
{
  global $UrlPtn;

  return preg_replace("/\\[($UrlPtn) ([^]]+)]/e",
                      "url_token(q1('\\1'), q1('\\4'))", $text, -1);
}

function parse_hyperlink($text)
{
  global $UrlPtn, $FlgChr;

  $text = preg_replace("/(^|[^A-Za-z=\"])($UrlPtn)(\$|[^\\/?=&~A-Za-z0-9])/e",
                       "q1('\\1') . url_token(q1('\\2'), q1('\\2')) . q1('\\5')", $text, -1); //"

  return $text;
}

function url_token($value, $display)
{
  static $count = 1;

  if($display == '')
    { $display = '[' . $count++ . ']'; }

  return new_entity(array('url', $value, $display));
}

function parse_macros($text)
{
  return preg_replace('/\\[\\[([^] ]+( [^]]+)?)]]/e',
                      "macro_token(q1('\\1'), q1('\\3'))", $text, -1);
}

function macro_token($macro, $trail)
{
    global $ViewMacroEngine, $page;

    $fragments = explode(" ", $macro, 2);
    $cmd = $fragments[0];
    $args = isset($fragments[1]) ? $fragments[1] : '';
    if (array_key_exists($cmd, $ViewMacroEngine))
    {
        eval('$result = $ViewMacroEngine[$cmd]->parse($args, $page);');
        return new_entity(array('raw', $result));
    }
    else
        return '[[' . $macro . ']]' . ($trail == "\n" ? $trail : '');
}

function parse_transclude($text)
{
  $text2 = preg_replace('/%%([^%]+)%%/e',
                        "transclude_token(q1('\\1'))", $text, -1);
  if($text2 != $text)
    { $text2 = str_replace("\n", '', $text2); }
  return $text2;
}

function transclude_token($text)
{
  global $pagestore, $ParseEngine, $ParseObject;
  static $visited_array = array();
  static $visited_count = 0;

  if(!validate_page($text))
    { return '%%' . $text . '%%'; }

  $visited_array[$visited_count++] = $ParseObject;
  for($i = 0; $i < $visited_count; $i++)
  {
    if($visited_array[$i] == $text)
    {
      $visited_count--;
      return '%%' . $text . '%%';
    }
  }

  $pg = $pagestore->page($text);
  $pg->read();
  if(!$pg->exists)
  {
    $visited_count--;
    return '%%' . $text . '%%';
  }

  $result = new_entity(array('raw', parseText($pg->text, $ParseEngine, $text)));
  $visited_count--;
  return $result;
}

function parse_bold($text)
{
  $text = preg_replace("/&lt;b>(()|[^'].*)&lt;\/b>/Ue", "pair_tokens('bold', q1('\\1'))",
                       $text, -1);

  return preg_replace("/'''([^']*)'''/Ue", "pair_tokens('bold', q1('\\1'))",
                      $text, -1);
}

function highlight_pair_tokens($text)
{
    if (trim(strtolower($text)) == 'pass')
        return pair_tokens('highlightpass', $text);
    else if (trim(strtolower($text)) == 'fail')
        return pair_tokens('highlightfail', $text);
    else
        return pair_tokens('highlight', $text);
}
function parse_highlight($text)
{
  return preg_replace("/\*{2}(()|[^*].*)\*{2}/Ue", "highlight_pair_tokens(q1('\\1'))",
                      $text, -1);
}

function parse_italic($text)
{
  return preg_replace("/\*(()|[^'].*)\*/Ue", "pair_tokens('italic', q1('\\1'))",
                      $text, -1);
}

function parse_underline($text)
{
  return preg_replace("/\b_(.+)_\b/Ue", "pair_tokens('underline', q1('\\1'))",
                      $text, -1);
}

function parse_singlequote($text)
{
  return preg_replace("/(^|\W)\'(.+)\'($|\W)/Ue",
                      "q1('\\1') . pair_tokens('singlequote', q1('\\2')) . q1('\\3')",
                      $text, -1);
}

function parse_teletype($text)
{
  return preg_replace("/{{({*?.*}*?)}}/Ue",
                      "pair_tokens('tt', q1('\\1'))", $text, -1);
}

function pair_tokens($type, $text)
{
  global $Entity, $FlgChr;

  $Entity[count($Entity)] = array($type . '_start');
  $Entity[count($Entity)] = array($type . '_end');

  return $FlgChr . (count($Entity) - 2) . $FlgChr . $text .
         $FlgChr . (count($Entity) - 1) . $FlgChr;
}

function parse_newline($text)
{
    static $last = array('', '');

    // More than two consecutive newlines fold into only two newlines.
    if ($last[0] == "\n" && $last[1] == "\n" && $text == "\n")
        return '';
    $last[0] = $last[1];
    $last[1] = $text;

    if ($text == "\n" || $text == "\n\r")
        return "<p>$text";
    else
        return $text;
}

function parse_horiz($text)
{
    $text = preg_replace("/^-{4,}\s*/e", "new_entity(array('hr'))",
                         $text, -1);

    return preg_replace("/&lt;hr>(\\n(\\r)?)?/e", "new_entity(array('hr'))",
                        $text, -1);
}

function parse_nowiki($text)
{
  return preg_replace("/```(.*)```/Ue",
                      "new_entity(array('nowiki', parse_elements(q1('\\1'))))",
                      $text, -1);
}

function parse_code($text)
{
  global $Entity, $FlgChr;
  static $in_code = 0;
  static $buffer  = '';

  if($in_code)
  {
    if($text == "&lt;/code>\n")
    {
      $Entity[count($Entity)] = array('code', $buffer);
      $buffer  = '';
      $in_code = 0;
      return $FlgChr . (count($Entity) - 1) . $FlgChr;
    }

    $buffer = $buffer . parse_elements($text);
    return '';
  }
  else
  {
    if($text == "&lt;code>\n")
    {
      $in_code = 1;
      return '';
    }

    return $text;
  }
}

function parse_htmlpre($text)
{
    global $Entity, $FlgChr;
    static $in_pre = 0;

    if($in_pre)
    {
        // Find first single </pre> and stops in-pre.
        if (!(($pos = strpos(strtolower($text), '</pre>')) === false))
        {
            $in_pre = 0;
            $preEnding = substr($text, 0, $pos+6); // $pos + strlen('</pre>')
            $afterPre = substr($text, $pos+6);
            $text = new_entity(array('raw', $preEnding)) . $afterPre;

            // Find all <pre></pre> couples on the same line.
            return preg_replace('/(.*)(<pre>.*<\\/pre>)(.*)/Uie',
                                "q1('\\1') . new_entity(array('raw', q1('\\2'))) . q1('\\3')",
                                $text, -1);
        }

        return new_entity(array('raw', $text));
    }
    else
    {
        // Find all <pre></pre> couples on the same line.
        $text = preg_replace('/(.*)(<pre>.*<\\/pre>)(.*)/Uie',
                             "q1('\\1') . new_entity(array('raw', q1('\\2'))) . q1('\\3')",
                             $text, -1);

        // Find first remaining single <pre> and starts the in-pre
        if (!(($pos = strpos(strtolower($text), '<pre>')) === false))
        {
            $in_pre = 1;
            $beforePre = substr($text, 0, $pos);
            $preBeginning = substr($text, $pos);
            return $beforePre . new_entity(array('raw', $preBeginning));
        }

        return $text;
    }
}

function parse_raw_html($text)
{
  global $Entity, $FlgChr;
  static $in_html = 0;
  static $buffer  = '';

  if($in_html)
  {
    if(strtolower($text) == "</html>\n")
    {
      $Entity[count($Entity)] = array('raw', $buffer);
      $buffer  = '';
      $in_html = 0;
      return $FlgChr . (count($Entity) - 1) . $FlgChr;
    }

    $buffer = $buffer . parse_elements($text);
    return '';
  }
  else
  {
    if(strtolower($text) == "<html>\n")
    {
      $in_html = 1;
      return '';
    }

    return $text;
  }
}

function parse_indents($text)
{
    global $MaxNesting;
    static $indentPrevLevel = -1;
    static $indentPrefixString = '';
    static $indentPrevLineIsBlank = 0;
    static $indentStealLine = 0;
    static $pending_p = '';

    // Indentation increase of more than on level will be corrected to only one.
    $auto_fix_indent_leap = 1;

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
                    $fixup .= entity_list($indentChar, 'start');
                else
                    $fixup .= str_repeat(entity_list($indentChar, 'start'),
                                         $indentCurLevel - $indentPrevLevel);
            }
            else
            {
                // close previously openend levels, until current level
                for ($i = $indentPrevLevel; $i > $indentCurLevel; $i--)
                    $fixup .= entity_listitem($indentPrefixString[$i], 'end') .
                              entity_list($indentPrefixString[$i], 'end');

                // close previous list item
                $fixup .= entity_listitem($indentPrefixString[$indentCurLevel], 'end');

                // if the indent type ([:#-*]) is different from previous at same level
                if ($indentPrefixString[$indentCurLevel] != $indentChar)
                    $fixup .= entity_list($indentPrefixString[$indentCurLevel], 'end');

                // add any pending <p>
                $fixup .= $pending_p;
                $pending_p = '';

                // if the indent type ([:#-*]) is different from previous at same level
                if ($indentPrefixString[$indentCurLevel] != $indentChar)
                    $fixup .= entity_list($indentChar, 'start');
            }

            // open new list item
            $fixup .= entity_listitem($indentChar, 'start');

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
                        $text = entity_listitem($indentPrefixString[$j], 'end') .
                                entity_list($indentPrefixString[$j], 'end') .
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
        $pending_p = $text;
        $text = '';
    }

    return $text;
}

function entity_list($type, $fn)
{
  if($type == '-' || $type == '*')
    { return new_entity(array('bullet_list_' . $fn)); }
  else if($type == ':' || $type == ';')
    { return new_entity(array('indent_list_' . $fn)); }
  else if($type == '#')
    { return new_entity(array('numbered_list_' . $fn)); }
  else if($type == INDENTS_TYPE_A)
    { return new_entity(array('numbered_list_a_' . $fn)); }
  else if($type == INDENTS_TYPE_I)
    { return new_entity(array('numbered_list_i_' . $fn)); }
  else if($type == '>')
    { return new_entity(array('cite_list_' . $fn)); }
}

function entity_listitem($type, $fn)
{
  if($type == '-' || $type == '*')
    { return new_entity(array('bullet_item_' . $fn)); }
  else if($type == ':' || $type == ';')
    { return new_entity(array('indent_item_' . $fn)); }
  else if($type == '#' || $type == INDENTS_TYPE_A  || $type == INDENTS_TYPE_I)
    { return new_entity(array('numbered_item_' . $fn)); }
  else if($type == '>')
    { return new_entity(array('cite_item_' . $fn)); }
}

function parse_heading($text)
{
  global $MaxHeading;

  static $c = array();
  static $last_level = 0;

  if(!preg_match('/^\s*([_@]?)(=+)([^=]*)(=+)(\\\\?)(.*)$/', $text, $result))
    { return $text; }

  if(strlen($result[2]) != strlen($result[4]))
    { return $text; }

  if(($level = strlen($result[2])) > $MaxHeading)
    { $level = $MaxHeading; }

  $style_underline = $result[1] == '_';
  $headers_numbering = $result[1] == '@';

  $header_num = '';
  if($headers_numbering)
  {
    if($level > $last_level) {
      for($i = $last_level+1; $i < $level; $i++)
        { $c[$i] = 1; }
      $c[$level] = 0;
    }
    $last_level = $level;
    $c[$level]++;
    for ($i = 1; $i <= $level; $i++) {
      if ($header_num != '') { $header_num .= '.'; }
      $header_num .= $c[$i];
    }
    $header_num .= ' ';
  }

  return new_entity(array('head_start', $level, $style_underline,
                          trim($header_num), strlen($result[5]))) .
         $header_num.trim($result[3]) . new_entity(array('head_end', $level)) .
         (strlen($result[5]) ? '&nbsp;' : '') . $result[6];
}

function parse_htmlisms($text)
{
  $text = str_replace('&', '&amp;', $text);
  $text = str_replace('<', '&lt;', $text);
  return $text;
}

function parse_elements($text)
{
  global $FlgChr;
  return preg_replace("/$FlgChr(\\d+)$FlgChr/e", "generate_element(q1('\\1'))", $text, -1);
}

function generate_element($text)
{
  global $Entity, $DisplayEngine;

  for ($i = 1; $i < 6; $i++)
    if (!isset($Entity[$text][$i]))
        $Entity[$text][$i] = '';

  return $DisplayEngine[$Entity[$text][0]]($Entity[$text][1],
                                           $Entity[$text][2],
                                           $Entity[$text][3],
                                           $Entity[$text][4],
                                           $Entity[$text][5]);
}

function parse_diff_skip($text)
{
  static $skipFirstHr = 1;

  if(preg_match('/^@@[\s-\+\d,]+@@/', $text))
  {
    if ($skipFirstHr)
    {
        $skipFirstHr = 0;
        return '';
    }
    else
        return ' ' . new_entity(array('hr'));
  }

  if(preg_match('/^\\\\ No newline/', $text))
    return '';

  return $text;
}

function parse_diff_color($text)
{
  static $in_old = 0, $in_new = 0;

  $this_old = ($text[0] == '-');
  $this_new = ($text[0] == '+');

  $text = substr($text, 1);

  // fix leading spaces
  $text = preg_replace("/^(\s+)(.+)/e",
                       "new_entity(array('raw', str_repeat('&nbsp;', strlen('\\1')))) . q1('\\2')",
                       $text);

  if($this_old && !$in_old)
    { $text = new_entity(array('diff_old_start')) . $text; }
  else if($this_new && !$in_new)
    { $text = new_entity(array('diff_new_start')) . $text; }

  if($in_old && !$this_old)
    { $text = new_entity(array('diff_old_end')) . $text; }
  else if($in_new && !$this_new)
    { $text = new_entity(array('diff_new_end')) . $text; }

  $in_old = $this_old;
  $in_new = $this_new;

  if ($text != '')
    $text = $text . new_entity(array('newline'));

  return $text;
}

function parse_wdiff_tags($text)
{
    static $buffer = array();
    static $hr_done = true;
    static $is_after = false;
    static $after_count = 0;

    $buffer_size = 4;

    // fix leading spaces
    $text = preg_replace("/^(<(INS|DEL)>)?(\s+)(.+)/e", "q1('\\1') . ".
        "new_entity(array('raw', str_repeat('&nbsp;', strlen('\\3')))) . ".
        "q1('\\4')", $text);

    $text = preg_replace("/^(.*)$/e",
                         "q1('\\1') . new_entity(array('raw', '<br>'))", $text);

    $ptn = '/(<\/?(DEL|INS)>)/';
    if (preg_match($ptn, $text))
    {
        $text = preg_replace($ptn . 'e', "new_entity(array('raw', q1('\\1')))",
                             $text);
        if ($buffer)
        {
            $text = implode(' ', $buffer) . $text;
            $buffer = array();
        }
        $is_after = true;
        return $text;
    }
    else if ($is_after)
    {
        $after_count++;
        if ($after_count > $buffer_size)
        {
            $is_after = false;
            $after_count = 0;
            $hr_done = false;
            return '';
        }
        else
            return $text;
    }
    else
    {
        array_push($buffer, $text);
        if (count($buffer) > $buffer_size)
        {
            array_shift($buffer);
            if (!$hr_done)
            {
                $hr_done = true;
                return new_entity(array('raw', '<hr>'));
            }
        }
        return '';
    }
}

function parse_table($text)
{
  global $page;
  static $in_table = false;
  static $table_count = 0;

  $pre = '';
  $post = '';
  $csv_download = false;
  if(preg_match('/^\*?(\|\|)+.*(\|\|)\s*$/', $text))  // Table.
  {
    if(!$in_table)
    {
      $pre = html_table_start();
      $in_table = true;
      $table_count++;
      if(preg_match('/^\*(\|\|)+.*(\|\|)\s*$/', $text))
      {
        $csv_download = true;
      }
    }

    $text = preg_replace('/^\*?((\|\|)+)(.*)\|\|\s*$/e',
                         "new_entity(array('raw',html_table_row_start().html_table_cell_start(strlen('\\1')/2))).".
                         "q1('\\3').new_entity(array('raw',html_table_cell_end().html_table_row_end()))",
                         $text, -1);
    $text = preg_replace('/((\|\|)+)/e',
                         "new_entity(array('raw',html_table_cell_end().html_table_cell_start(strlen('\\1')/2)))",
                         $text, -1);
  }
  else if($in_table)                    // Have exited table.
  {
    $in_table = false;
    $pre = html_table_end();
  }

  if($pre != '')
  {
    $text = new_entity(array('raw', $pre)) . parse_newline($text);
    if($csv_download)
    {
      $img = '<img align="top" src="images/csv.png" alt="Download as CSV" '.
             'title="Download as CSV" hspace="3" width="14" height="15" '.
             'border="0">';
      $text = html_table_start().html_table_row_start().html_table_cell_start().
              '<a href="'.tablecsvURL($page, $table_count).'">'.$img.
              '<small>Download as CSV</small></a>'.
              html_table_cell_end().html_table_row_end().html_table_end().
              $text;
    }
  }

  if($post != '')
  {
    $text = $text . new_entity(array('raw', $post));
  }

  return $text;
}

function parse_tablecsv($text)
{
    global $tablenum;

    static $in_table = false;
    static $table_count = 0;

    if (preg_match('/^\*?(\|\|)+.*(\|\|)\s*$/', $text))
    {
        if (!$in_table)
        {
            $in_table = true;
            $table_count++;
        }
        if ($table_count == $tablenum)
        {
            $cells = array();
            $parts = explode('||', $text);
            for ($i = 1; $i < count($parts)-1; $i++)
            {
                $cell = trim($parts[$i]);
                if ($cell == '*') { $cell = ''; }
                $cell = str_replace('"', '""', $cell);
                $cells[] = '"'.$cell.'"';
            }
            $text = implode(',', $cells)."\n";
            return $text;
        }
    }
    else if ($in_table)
    {
        $in_table = false;
    }

    return;
}

function parse_redirect($text)
{
    global $action, $no_redirect, $page, $version;

    if (preg_match('/^#redirect\s+\[?(.*?)\]?\s*$/i', $text, $matches)
        && validate_page($matches[1]))
    {
        if ($no_redirect || $action != 'view' || isset($version))
        {
            $text = new_entity(array('raw', '#redirect ')) . wikiname_token($matches[1], '');
        }
        else
        {
            header ('Location: ' . viewUrl($matches[1]) . '&redirect_from=' . $page);
            exit;
        }
    }

    return $text;
}
?>
