<?php
// $Id: html.php,v 1.2 2003/04/21 18:20:58 mich Exp $

// These functions take wiki entities like 'bold_begin' or 'ref' and return
//   HTML representing these entities.  They are used throught this script
//   to generate appropriate HTML.  Together with the template scripts, they
//   constitue the sole generators of HTML in this script, and are thus the
//   sole means of customizing appearance.
function html_bold_start()
  { return '<strong>'; }
function html_bold_end()
  { return '</strong>'; }
function html_italic_start()
  { return '<em>'; }
function html_italic_end()
  { return '</em>'; }
function html_underline_start()
  { return '<u>'; }
function html_underline_end()
  { return '</u>'; }
function html_singlequote_start()
  { return '<code>'; }
function html_singlequote_end()
  { return '</code>'; }
function html_highlight_start()
  { return '<span class="highlight">'; }
function html_highlight_end()
  { return '</span>'; }
function html_highlightpass_start()
  { return '<span class="highlightpass">'; }
function html_highlightpass_end()
  { return '</span>'; }
function html_highlightfail_start()
  { return '<span class="highlightfail">'; }
function html_highlightfail_end()
  { return '</span>'; }
function html_tt_start()
  { return '<tt>'; }
function html_tt_end()
  { return '</tt>'; }
function html_ul_start()
  { return '<ul>'; }
function html_ul_end()
  { return "</ul>\n"; }
function html_ol_start()
  { return '<ol>'; }
function html_ol_end()
  { return "</ol>\n"; }
function html_li_start()
  { return '<li>'; }
function html_li_end()
  { return "</li>\n"; }
function html_dl_start()
  { return '<dl>'; }
function html_dl_end()
  { return "</dl>\n"; }
function html_dd_start()
  { return '<dd>'; }
function html_dd_end()
  { return "</dd>\n"; }
function html_dt_start()
  { return '<dt>'; }
function html_dt_end()
  { return '</dt>'; }
function html_cite_start()
 { return '<blockquote class="cite">'; }
function html_cite_end()
 { return "</blockquote>\n"; }
function html_citeitem_start()
 { return ''; }
function html_citeitem_end()
 { return ''; }
function html_hr()
  { return "<hr>\n"; }
function html_newline()
  { return "<br>\n"; }
function html_head_start($level)
  { static $count = 0; $count++; return "<a name=\"toc$count\"></a><h$level>"; }
function html_head_end($level)
  { return "</h$level>"; }
function html_nowiki($text)
  { return $text; }
function html_code($text)
  { return '<pre>' . $text . '</pre>'; }
function html_raw($text)
  { return $text; }
function html_anchor($name)
  { return '<a name="' . $name . '"></a>'; }
function html_diff_old_start()
  { return "<table class=\"diff\" cellspacing=0 cellpadding=1><tr><td class=\"diff-removed\">\n"; }
function html_diff_new_start()
  { return "<table class=\"diff\" cellspacing=0 cellpadding=1><tr><td class=\"diff-added\">\n"; }
function html_diff_end()
  { return '</td></tr></table>'; }
function html_diff_add()
  { return html_bold_start() . 'Added:' . html_bold_end(); }
function html_diff_change()
  { return html_bold_start() . 'Changed:' . html_bold_end(); }
function html_diff_delete()
  { return html_bold_start() . 'Deleted:' . html_bold_end(); }
function html_table_start()
  { return '<table>'; }
function html_table_end()
  { return '</table>'; }
function html_table_row_start()
  { return '<tr>'; }
function html_table_row_end()
  { return '</tr>'; }
function html_table_cell_start($span = 1)
{
  if($span == 1)
    { return '<td>'; }
  else
    { return '<td colspan="' . $span . '">'; }
}
function html_table_cell_end()
  { return '</td>'; }
function html_time($timestamp)
{
  global $TimeZoneOff;
  if($timestamp == '') { return 'never'; }
  $time = mktime(substr($timestamp, 8, 2),  substr($timestamp, 10, 2),
                 substr($timestamp, 12, 2), substr($timestamp, 4, 2),
                 substr($timestamp, 6, 2),  substr($timestamp, 0, 4));
  return date('D, d M Y H:i:s', $time + $TimeZoneOff * 60);
}
function html_time2($timestamp)
{
  global $TimeZoneOff;
  if($timestamp == '') { return 'never'; }
  $time = mktime(substr($timestamp, 8, 2),  substr($timestamp, 10, 2),
                 substr($timestamp, 12, 2), substr($timestamp, 4, 2),
                 substr($timestamp, 6, 2),  substr($timestamp, 0, 4));
  return date('M d, Y H:i:s', $time + $TimeZoneOff * 60);
}
function html_gmtime($timestamp)
{
  $time = mktime(substr($timestamp, 8, 2),  substr($timestamp, 10, 2),
                 substr($timestamp, 12, 2), substr($timestamp, 4, 2),
                 substr($timestamp, 6, 2),  substr($timestamp, 0, 4));
  return gmdate('Y-m-d', $time) . 'T' . gmdate('H:i:s', $time) . 'Z';
}
function html_timestamp($timestamp)
{
  global $TimeZoneOff;
  $time = mktime(substr($timestamp, 8, 2),  substr($timestamp, 10, 2),
                 substr($timestamp, 12, 2), substr($timestamp, 4, 2),
                 substr($timestamp, 6, 2),  substr($timestamp, 0, 4));
  return date('Y.m.d H:i:s', $time + $TimeZoneOff * 60);
}
function html_url($url, $text)
{
  if($url == $text
     && preg_match('/(.jpe?g|.png|.gif|.bmp)$/i', $text))
  {
    return "<img src=\"$url\" alt=\"" . basename($url) . "\" />";
  }
  return "<a href=\"$url\">$text</a>";
}

function html_ref($refPage, $appearance, $hover = '', $anchor = '', $anchor_appearance = '')
{
    global $db, $SeparateLinkWords, $page, $pagestore;

    if($hover != '')
    {
        $hover = ' title="' . $hover . '"';
    }

    $p = new WikiPage($db, $refPage);
    $p_exists = $p->exists();

    $twintext = "";
    $onlytwin = "";
    $twin = $pagestore->twinpages($refPage);
    if(!$p_exists && count($twin) == 1)
    {
        $onlytwin = html_twin_x($twin[0][0], $twin[0][1], $twin[0][1]);
    }
    else if(count($twin))
    {
        // point at the sisterwiki's version
        $n = 1;
        foreach($twin as $site)
        {
            $twintext = $twintext . html_twin_x($site[0], $n, $site[1]);
            $n++;
        }
        $twintext = '<sup>' . $twintext . '</sup>';
    }

    if($p_exists)
    {
        if($SeparateLinkWords && $refPage == $appearance)
        {
            $appearance = html_split_name($refPage);
        }

        $result = '<a href="' . viewURL($refPage) . $anchor . '"' . $hover . '>'
               . $appearance . $anchor_appearance . '</a>' . $onlytwin;
    }
    else
    {
        $result = "";
        if(validate_page($refPage) == 1       // Normal WikiName
            && $appearance == $refPage)       // ... and is what it appears
        {
            if ($onlytwin)
                $result = $onlytwin;
            else
                $result = $refPage;
        }
        else                                  // Free link.
        {
            // Puts the appearance between parenthesis if there's a space in it.
            if (strpos($appearance, ' ') === false)
                $tempAppearance = $appearance;
            else
                $tempAppearance = "($appearance)";

            if ($onlytwin)
                $result = $onlytwin;
            else
                $result = $tempAppearance;
        }

        $result = $result
            . '<a href="' . editURL($refPage, '', $page) . '"'
            . ' title="Create this Wiki page" '
            . $hover . '>?</a>'
            . $twintext;
    }

    return $result;
}

function html_interwiki($url, $text)
{
  return '<a href="' . $url . '">' . $text . '</a>';
}
function html_twin($whichwiki, $ref)
{
  return html_twin_x($whichwiki, "[$whichwiki]", $ref);
}
function html_twin_x($whichwiki, $linktext, $ref)
{
  global $pagestore;

  return '<a href="' . $pagestore->interwiki($whichwiki) . $ref . '"' .
         'title="See also: ' . $ref . ' in ' . $whichwiki . '">' .
         '<span class="twin"><em>' . $linktext . '</em></span></a>';
}
function html_category($time, $page, $host, $user, $comment, $version,
                       $hotPages, $newPages)
{
  global $pagestore, $UserName, $UseHotPages;

  $text = '(' . html_timestamp($time) . ') (' .
          '<a href="' . historyURL($page) . '">history</a>) ' .
          html_ref($page, $page, '', "&$version");

  /*
  // Skip this.  Why should twin pages show up in RecentChanges?
  if(count($twin = $pagestore->twinpages($page)))
  {
    foreach($twin as $site)
      { $text = $text . ' ' . html_twin($site[0], $site[1]); }
  }
  */

  if (in_array($page, $newPages))
    $text .= '<img src="images/new.png" alt="New!" title="" width="28" height="11" border="0">';

  if ($UseHotPages && in_array($page, $hotPages))
    $text .= '<img src="images/hot.png" alt="Hot!" title="Hot!" width="16" height="15" border="0">';

  $text = $text . ' . . . . ' .
          ($user == '' ? $host : html_ref($user, $user, $host));

  if($comment != '')
  {
    $text = $text . ' ' . html_bold_start() . '[' .
            str_replace('<', '&lt;', str_replace('&', '&amp;', $comment)) .
            ']' . html_bold_end();
  }

  return $text;
}
function html_fulllist($page, $count)
{
  return '<strong><a href="' . viewURL($page, '', 1) . '">' .
         'See complete list (' . $count . ' entries)</a></strong>';
}
function html_fullhistory($page, $count)
{
  return '<tr><td colspan="3"><strong><a href="' . historyURL($page, 1) .
         '">' .  'See complete list (' . $count .
         ' entries)</a></strong></td></tr>';
}
function html_toolbar_top()
{
  global $HomePage, $PrefsScript;
  return html_ref($HomePage, $HomePage) . ' | ' .
         html_ref('RecentChanges', 'RecentChanges') . ' | ' .
         '<a href="' . $PrefsScript . '">Preferences</a>';
}

function html_toolbar_bottom()
{
  global $PrefsScript;
  return html_ref('RecentChanges', 'RecentChanges') . ', ' .
         '<a href="' . $PrefsScript . '">UserOptions</a>';
}

function html_history_entry($page, $version, $time, $host, $user, $c1, $c2,
                            $comment)
{
  return "<tr><td>" .
         "<input type=\"radio\" name=\"ver1\" value=\"$version\"" .
         ($c1 ? ' checked="checked"' : '') . " /></td>\n" .
         "    <td>" .
         "<input type=\"radio\" name=\"ver2\" value=\"$version\"" .
         ($c2 ? ' checked="checked"' : '') . " /></td>\n" .
         "<td><a href=\"" . viewURL($page, $version) . "\">" .
         html_time2($time) . "</a> . . . . " .
         ($user == '' ? $host : html_ref($user, $user, $host)) .
         ($comment == '' ? '' :
           (' ' . html_bold_start() . '[' .
            str_replace('<', '&lt;', str_replace('&', '&amp;', $comment)) .
            ']' . html_bold_end())) .
         "</td></tr>\n";
}
function html_lock_start()
{
  global $AdminScript;

  return '<form method="post" action="' . $AdminScript . "\">\n" .
         '<div class="form">' . "\n" .
         '<input type="hidden" name="locking" value="1" />' . "\n" .
         html_bold_start() . 'Locked' . html_bold_end() . html_newline();
}
function html_lock_end($count)
{
  return '<input type="hidden" name="count" value="' . $count . '" />' . "\n" .
         '<input type="submit" name="Save" value="Save" />' . "\n" .
         '</div>' . "\n" .
         '</form>' . "\n";
}
function html_lock_page($page, $mutable)
{
  static $count = 0;
  $count++;
  return '<input type="hidden" name="name' . $count .
         '" value="' . urlencode($page) . '" />' . "\n" .
         '<input type="checkbox" name="lock' . $count . '" value="1"' .
         ($mutable ? '' : ' checked="checked"') . ' />' . "\n" .
         "\n" . $page . html_newline();
}
function html_rate_start()
{
  return '<br /><strong>Blocked IP address ranges</strong>' .
         "\n<dl>\n";
}
function html_rate_end()
{
  global $AdminScript;

  return "</dl>\n" .
         '<form method="post" action="' . $AdminScript . "\">\n" .
         '<div class="form">' . "\n" .
         '<input type="hidden" name="blocking" value="1" />' . "\n" .
         'Enter IP address range in form <tt>12.*</tt>, <tt>34.56.*</tt>, or ' .
         '<tt>78.90.123.*</tt><br />' . "\n" .
         '<input type="text" name="address" value="" size="40" /><br />' .
         "\n" .
         '<input type="submit" name="Block" value="Block" />' . "\n" .
         '<input type="submit" name="Unblock" value="Unblock" />' . "\n" .
         '</div>' . "\n";
         '</form>' . "\n";
}
function html_rate_entry($address)
{
  return '<dd>' . $address . "</dd>\n";
}

// This function splits up a traditional WikiName so that individual
// words are separated by spaces.

function html_split_name($page)
{
  global $UpperPtn, $LowerPtn;

  if(validate_page($page) != 1)
    { return $page; }
  $page = preg_replace("/(?<=$UpperPtn|$LowerPtn)($UpperPtn$LowerPtn)/",
                       ' \\1', $page, -1);
  $page = preg_replace("/($LowerPtn)($UpperPtn)/",
                       '\\1 \\2', $page, -1);
  return $page;
}
?>
