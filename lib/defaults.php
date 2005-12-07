<?php

//**********************************************************************
// DO NOT EDIT THIS FILE.
//
// This file contains configuration defaults for 'Tavi that are over-
// written on installation.  Instead, you should edit 'config.php' to
// re-set these options, or use the 'configure.pl' script to create a
// 'config.php' for yourself.
//
// If you see options in here that are not present in 'config.php',
// you can safely copy them to 'config.php' and set them to a new
// value.  This will override the default set here.
//**********************************************************************

// The following variables establish the format for WikiNames in this wiki.
//   Note that changing this might require a change to parse/transforms.php so
//   that parse_wikiname knows how many parentheses are included in $LinkPtn.
$UpperPtn = "[A-Z\xc0-\xde]";
$LowerPtn = "[a-z\xdf-\xff]";
$AlphaPtn = "[A-Za-z\xc0-\xff]";
$LinkPtn = $UpperPtn . $AlphaPtn . '*' . $LowerPtn . '+' .
           $UpperPtn . $AlphaPtn . '*()';

// $UrlPtn establishes the format for URLs in this wiki.
//   Note that changing this requires a change to parse/transforms.php so
//   that parse_hyperlinkxxx know how many parentheses are included in $UrlPtn.

$UrlPtn  = "(http:|mailto:|https:|ftp:|gopher:|news:)" .
            "([^ \\/\"\']*\\/)*[^ \\t\\n\\/\"\']*[A-Za-z0-9\\/?=&~]";

// $InterWikiPtn establishes the format for InterWiki links in this wiki.
//   Note that changing this requires a change to parse/transforms.php so
//   that parse_interwiki knows how many parentheses are in $InterwikiPtn.
$InterwikiPtn = "([A-Za-z0-9]+):" .
                "(([^ \\/\"\']*\\/\\$FlgChr)*" .
                "[^ \\t\\n\\/\"\'\\$FlgChr]*[\\/=&~A-Za-z0-9])";

// !!!WARNING!!!
// If $AdminEnabled is set to 1, the script admin/index.php will be accessible.
//   This allows administrators to lock pages and block IP addresses.  If you
//   want to use this feature, YOU SHOULD FIRST BLOCK ACCESS TO THE admin/
//   DIRECTORY BY OTHER MEANS, such as Apache's authorization directives.
//   If you do not do so, any visitor to your wiki will be able to lock pages
//   and block others from accessing the wiki.
// If $AdminEnabled is set to 0, administrator control will be disallowed.
$AdminEnabled = 0;

// Old versions of pages will be deleted after $ExpireLen days.  If $ExpireLen
//   is set to 0, old versions of pages (and pages set to empty) will never
//   be removed from the database.
$ExpireLen = 14;

// Set $Charset to indicate the character set used for storage, editing,
//   and display in your wiki.  The default is "ISO-8859-1" (Latin-1).
//   "utf-8" is supported, and is recommended for international text;
//   however you should be cautioned that Netscape does not behave correctly
//   when editing utf-8 text.  Hence, "utf-8" is not currently the default.
$Charset = 'ISO-8859-1';

// $SeparateTitleWords determines whether spaces should be inserted in page
//   titles.  If nonzero, the page title (but not header) of WikiName would
//   show 'Wiki Name' instead.  Pages that have free link titles will not
//   be changed.
$SeparateTitleWords = 1;

// $SeparateHeaderWords determines whether spaces should be inserted in page
//   headers.  If nonzero, the page header of WikiName would show 'Wiki Name'
//   instead.  Pages that have free link names would not have changed headers.
$SeparateHeaderWords = 0;

// $SeparateLinkWords determines whether spaces should be inserted in links
//   to pages.  If nonzero, all links to pages such as WikiName would display
//   as 'Wiki Name'.  Pages that have free link names would not have changed
//   links.
$SeparateLinkWords = 0;

// $CookieName determines the name of the cookie that browser preferences
//   (like user name, etc.) are stored in.
$CookieName = 'prefs';

// If $EnableWordDiff is set to 1, the "word diff" feature will be enabled. This
//   uses the external "wdiff" executable to perform the diff instead of the
//   regular internal diff.
$EnableWordDiff = 0;

// $WdiffCmd determines what command to run to compute word diffs.
#$WdiffCmd = "/home/nitwiki/Files/wdiff";

// When $WdiffLibrary is set to 1, the LD_LIBRARY_PATH environment variable will
//   be set to /disk before executing wdiff. This value is hardcoded to avoid
//   security issues. See lib/diff.php.
$WdiffLibrary = 0;

// When $EnableWordDiff is set to 1, $DiffModeCookieName determines the name of
//   the cookie to store the diff mode preference.
$DiffModeCookieName = 'diffmode';

// $EditRows and $EditCols determine the default dimensions of the wiki edit
//   box for users that have not set their preferences.
$EditRows = 20;
$EditCols = 65;

// Initialize the default user name to empty.
$UserName = '';

// Initialize the default nickname to empty.
$NickName = '';

// Default time zone offset (in minutes) for visitors who haven't yet set their
//   preferences.
$TimeZoneOff = 0;

// $AuthorDiff indicates whether history pages should show a diff for the last
//   edit (zero), or for all edits made by the same author (not zero).  The
//   default here is used if the user has not set their preferences.
$AuthorDiff = 1;

// $DayLimit determines how many days worth of changes show in a category list.
//   This default is used if the user has not set their preferences.
$DayLimit = 14;

// $MinEntries determines the absolute minimum size of a category list (unless
//   there are fewer pages *in* the category).  This default is used if the
//   user has not set their preferences.
$MinEntries = 20;

// $UseHotPages indicates whether the hot pages icon should be used on the
//   RecentChanges page.
$UseHotPages = 0;

// $HistMax determines the maximum number of entries on a page's history list.
//   This default is used if the user has not set their preferences.
$HistMax = 8;

// $RatePeriod determines how many seconds of time to record a visitor's access
//   to the site.  If it is set to zero, ALL RATE CHECKING AND IP ADDRESS
//   BLOCKING WILL BE DISABLED.
$RatePeriod = 300;

// $RateView determines how many pages a visitor can view in $RatePeriod
//   amount of time.
$RateView   = 100;

// $RateSearch determines how many processor-intensive operations (search,
//   diff, etc.) a visitor can perform in $RatePeriod amount of time.
$RateSearch = 50;

// $RateEdit determines how many edits a visitor can make in $RatePeriod
//   amount of time.
$RateEdit   = 20;

// $TempDir determines the location of temp files used for computing diffs.
$TempDir = '/tmp';

// $MaxPostLen determines the size, in bytes, of the largest edit allowed.
$MaxPostLen = 204800;

// $MaxNesting determines the maximum allowed nesting of lists.
$MaxNesting = 20;

// $MaxHeading determines the maximum allowed heading level in headings.
$MaxHeading = 6;

// If $EnableSubscriptions is set to 1, users will be allowed to subscribe to
// pages and receive an email when they're updated. If it is set to 0, this
// feature will be disabled.
$EnableSubscriptions = 0;

// $EmailSuffix specifies the email address suffix that should be appended to
// the username when sending the page subscription email.
$EmailSuffix = '';

// $PageSizeLimit and $PageTooLongSize are used to control when pages are too
// long for the supporting database backend and prevent data from being lost.
// Currently, the limit of the "text" datatype, used to store the content of the
// pages, is 65535 characters in MySQL. Pages are considered too long when they
// reach 60000 characters and comments are then disabled.
$PageSizeLimit = 65535;
$PageTooLongSize = 60000;

// If $UseSpamRevert is set to 1, logged in users will be given the
// "Spam Revert" button on the Diff page, allowing to easily remove undesired
// content and restore the state of the page on RecentChanges.
$UseSpamRevert = 0;

// $ParseEngine indicates what parsing rules will be run when displaying a
//   wiki page.  To disable a particular rule, you can place a comment at the
//   beginning of its line.  The order of this list is important.
// Note that free links and wiki names are disabled above, using config
//   variables.  This is because wiki names are parsed in other places than
//   just the wiki page.
// Raw HTML parsing is turned off by default, since this is a potential
//   security hole.

$ParseEngine = array(
                 'parse_elem_flag',
                 'parse_redirect',
                 'parse_raw_html',
                 'parse_htmlisms',
                 'parse_code',
                 'parse_nowiki',
                 'parse_htmlanchor',
                 'parse_htmltags',
                 'parse_hyperlink_ref',
                 'parse_hyperlink_description',
                 'parse_macros',
                 'parse_hyperlink',
                 'parse_transclude',
                 'parse_freelink',
                 'parse_interwiki',
                 'parse_wikiname',
                 'parse_teletype',
                 'parse_heading',
                 'parse_table',
                 'parse_horiz',
                 'parse_indents',
                 'parse_bold',
                 'parse_highlight',
                 'parse_italic',
                 'parse_underline',
                 'parse_singlequote',
                 'parse_newline',
                 'parse_elements'
               );

// $DiffEngine indicates what parsing rules will be run to display differences
//   between versions.  This should be a shorter list than $ParseEngine,
//   since we just want minimal things like bold and italic and wiki links.
$DiffEngine = array(
                'parse_elem_flag',
                'parse_diff_skip',
                'parse_diff_color',
                'parse_htmlisms',
                'parse_nowiki',
                'parse_hyperlink_ref',
                'parse_hyperlink_description',
                'parse_hyperlink',
                'parse_freelink',
                'parse_interwiki',
                'parse_wikiname',
                'parse_bold',
                'parse_italic',
                'parse_highlight',
                'parse_teletype',
                'parse_newline',
                'parse_elements'
              );

// $WdiffEngine indicates what parsing rules will be run when using word diff to
//   display differences between versions.
$WdiffEngine = array(
                 'parse_elem_flag',
                 'parse_wdiff_tags',
                 'parse_htmlisms',
                 'parse_elements'
               );

// $DisplayEngine indicates what functions will be used to translate wiki
//   markup elements into actual HTML.  See parse/html.php
$DisplayEngine = array(
                   'bold_start'   => 'html_bold_start',
                   'bold_end'     => 'html_bold_end',
                   'italic_start' => 'html_italic_start',
                   'italic_end'   => 'html_italic_end',
                   'tt_start'     => 'html_tt_start',
                   'tt_end'       => 'html_tt_end',
                   'head_start'   => 'html_head_start',
                   'head_end'     => 'html_head_end',
                   'newline'      => 'html_newline',
                   'ref'          => 'html_ref',
                   'url'          => 'html_url',
                   'interwiki'    => 'html_interwiki',
                   'raw'          => 'html_raw',
                   'code'         => 'html_code',
                   'hr'           => 'html_hr',
                   'nowiki'       => 'html_nowiki',
                   'anchor'       => 'html_anchor',
                   'highlight_start'     => 'html_highlight_start',
                   'highlight_end'       => 'html_highlight_end',
                   'highlightpass_start' => 'html_highlightpass_start',
                   'highlightpass_end'   => 'html_highlightpass_end',
                   'highlightfail_start' => 'html_highlightfail_start',
                   'highlightfail_end'   => 'html_highlightfail_end',
                   'bullet_list_start'   => 'html_ul_start',
                   'bullet_list_end'     => 'html_ul_end',
                   'bullet_item_start'   => 'html_li_start',
                   'bullet_item_end'     => 'html_li_end',
                   'indent_list_start'   => 'html_dl_start',
                   'indent_list_end'     => 'html_dl_end',
                   'indent_item_start'   => 'html_dd_start',
                   'indent_item_end'     => 'html_dd_end',
                   'term_item_start'     => 'html_dt_start',
                   'term_item_end'       => 'html_dt_end',
                   'numbered_list_start' => 'html_ol_start',
                   'numbered_list_end'   => 'html_ol_end',
                   'numbered_list_a_start' => 'html_ol_a_start',
                   'numbered_list_a_end'   => 'html_ol_a_end',
                   'numbered_list_i_start' => 'html_ol_i_start',
                   'numbered_list_i_end'   => 'html_ol_i_end',
                   'numbered_item_start' => 'html_li_start',
                   'numbered_item_end'   => 'html_li_end',
                   'cite_list_start'     => 'html_cite_start',
                   'cite_list_end'       => 'html_cite_end',
                   'cite_item_start'     => 'html_citeitem_start',
                   'cite_item_end'       => 'html_citeitem_end',
                   'diff_old_start'      => 'html_diff_old_start',
                   'diff_old_end'        => 'html_diff_end',
                   'diff_new_start'      => 'html_diff_new_start',
                   'diff_new_end'        => 'html_diff_end',
                   'diff_change'         => 'html_diff_change',
                   'diff_add'            => 'html_diff_add',
                   'diff_delete'         => 'html_diff_delete'
                 );
                 
// $SaveMacroEngine determines what save macros will be called after a
// page is saved.  See parse/save.php
$SaveMacroEngine = array('parse_define_interwiki',
                         'parse_define_sisterwiki',
                         'parse_define_links'
                        );

?>
