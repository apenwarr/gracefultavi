<?php

require_once('template/tree.php');

function toolbar_button($url, $label, $is_selected)
{
    $label = str_replace(' ', '&nbsp;', $label);
    if ($url) {
        $class = $is_selected ? 'buttonSelected' : 'button';
        print '<a class="'.$class.'" href="'.$url.'">'.$label.'</a> ';
    } else {
        print '<span class="buttonDisabled">'.$label.'</span> ';
    }
}

function toolbar($page, $args)
{
    // toolbar buttons added by macros
    global $macroToolbarButtons;
    ksort($macroToolbarButtons);
    foreach ($macroToolbarButtons as $macro => $label) {
        toolbar_button(macroURL($page, $macro, ''), $label, false);
    }

    // view
    toolbar_button($args['button_view'] ? viewURL($args['headlink']) : '',
        'View', $args['button_selected']=='view');

    // edit
    $edit_label = 'Edit';
    if (isset($args['editver']) && $args['editver'] > -1) {
        if ($args['editver'] == 0) {
            $edit_url = editURL($args['headlink']);
        } else {
            $edit_url = editURL($args['headlink'], $args['editver']);
            $edit_label = 'Edit Archive';
        }
    } else {
        $edit_url = '';
    }
    toolbar_button($edit_url, $edit_label, $args['button_selected']=='edit');

    // diff
    $diff_url = (isset($args['timestamp']) && $args['timestamp'] != '') ?
                historyURL($args['headlink']) : '';
    toolbar_button($diff_url, 'Diff', $args['button_selected']=='diff');

    // backlinks
    $backlinks_url = ($args['button_backlinks'] && $args['headlink']) ?
                    backlinksURL($args['headlink']) : '';
    toolbar_button($backlinks_url, 'Backlinks',
        $args['button_selected']=='backlinks');
}


// This function generates the common prologue and header
// for the various templates.
//
// Its parameters are passed as an associative array with the following
// members:
//
//   'norobots' => An integer; if nonzero, robots will be forbidden to
//                 index the page or follow links from the page.
//   'title'    => A string containing the page title.  This function
//                 will append ' - WikiName' to the title.
//   'heading'  => A string containing the page's heading.
//   'headlink' => A string.  If not empty, it will be appended to the
//                 page's heading as a link to find the contents of the
//                 string in the database.
//   'headsufx' => A string containing the heading suffix.  If not
//                 empty, it will be printed after the heading link.
//   'toolbar'  => An integer; if nonzero, the toolbar will be displayed.
//   'spam_revert' => Boolean, indicates whether or not to use the spam revert
//                    button.
//
// Button specific parameters:
//
//   'button_view'      => An integer; if nonzero, the View button will be enabled.
//   'timestamp'        => Timestamp for the page. If not empty, the Diff button will
//                         be enabled.
//   'editver'          => An integer; if greater than -1, the Edit button will be
//                         enabled.
//   'button_backlinks' => An integer; if nonzero, the Backlinks button will be
//                         enabled.

function template_common_prologue($args)
{
    global $AdditionalHeader, $CommonScript, $FindScript, $HomePage;
    global $MetaDescription, $MetaKeywords, $page, $pagestore, $ScriptBase;
    global $SeparateHeaderWords, $SeparateTitleWords, $ShortcutIcon;
    global $StyleScript, $TableSortScript, $UserName, $UseSpamRevert, $WikiLogo;
    global $WikiName;

    if ($SeparateTitleWords) { $args['title'] = html_split_name($args['title']); }
?>

<html>
<head>
<meta name="KEYWORDS" content="<?php print $MetaKeywords; ?>">
<meta name="DESCRIPTION" content="<?php print $MetaDescription; ?>">
<?php if ($args['norobots']) {?>
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
<?php } ?>
<link type="text/css" rel="stylesheet" media="screen" href="<?php print $StyleScript; ?>">
<link type="text/css" rel="stylesheet" media="print" href="<?php print $StyleScript; ?>&amp;csstype=print">
<script src="<?php print $TableSortScript; ?>" type="text/javascript"></script>
<script src="<?php print $CommonScript; ?>" type="text/javascript"></script>
<?php if ($ShortcutIcon) : ?>
    <link rel="SHORTCUT ICON" href="<?=$ShortcutIcon?>">
<?php endif; ?>
<link rel="ALTERNATE" title="<?=htmlspecialchars($WikiName)?>" href="<?=$ScriptBase?>?action=rss" TYPE="application/rss+xml">
<title><?php print $args['title'] . ' - ' . htmlspecialchars($WikiName); ?></title>
</head>

<body onLoad="bodyOnLoad();">
<NOINDEX>

<?php
if ($AdditionalHeader) {
    print '<span class="printhide">';
    include($AdditionalHeader);
    print '</span>';
}
?>

<div id="header">

<table align="center" class="topbox" border="0">
<tr valign="top">
<td>
    <table cellspacing="2" cellpadding="1" border="0">
    <tr valign="top">
    <td class="printhide">
        <div class="logo">
        <a href="<?php print viewURL($HomePage); ?>"><img src="<?php print $WikiLogo; ?>" alt="[Home]"></a>
        </div>
    </td>
    <td class="printhide">
        <h2><?php print htmlspecialchars($WikiName); ?></h2>

        <form method="get" action="<?php print $FindScript; ?>">
        <div class="form">
        <input type="hidden" name="action" value="find">

        <table cellspacing="0" cellpadding="0" border="0">
        <tr>
        <td>Jump to:&nbsp;</td>
        <td><input type="text" name="find" size="20" accesskey=","></td>
        </tr>

        <?php
        if ( $args['headlink']
             && $args['headlink'] != $HomePage
             && $args['headlink'] != 'RecentChanges'
             && $pagestore->getChildren($args['headlink']) )
        {
        ?>
        <tr>
        <td></td>
        <td>
        <input type="checkbox" name="branch_search" value="<?php print htmlspecialchars($args['headlink']) ?>">
        <small>Search only children of <b><?=$args['headlink']?></b></small>
        </td>
        </tr>
        <?php } ?>
        </table>

        </div>
        </form>
    </td>
    </tr>
    </table>
</td>

<td class="printhide" valign="top" align="right">

    <table cellspacing="2" cellpadding="1" border="0">
    <tr>
    <td>

    <?php
    if (isset($args['tree']))
    {
        $tree = $pagestore->getTreeFromLeaves($HomePage, $args['headlink']);
        if (isset($tree[$HomePage]) && count($tree[$HomePage]) > 0)
        {
            drawTree($tree, true, $args['headlink']);
        }
    }
    ?>

    </td></tr></table>

</td>
</tr>

<?php
if (isset($args['quote']))
{
    $quotepage = $pagestore->page('AnnoyingQuote');
    $quote = $quotepage->read();
    if ($quotepage->exists())
    {
        global $ParseEngine;
        $paragraphs = explode("\n\n", $quotepage->text);
        $last_paragraph = parseText(trim(array_pop($paragraphs)), $ParseEngine, $page);
        print '<tr><td colspan="2" align="center">'."\n";
        print '<table cellspacing="0" cellpadding="0" border="0"><tr><td>';
        print '<div align="right"><span class="quote">';
        print $last_paragraph;
        print '</span></div>';
        print '</td></tr></table>'."\n";
        print '</td></tr>';
    }
}
?>

<tr>
<td class="printhide" valign="bottom" align="right" colspan="2">
    <table cellspacing="0" cellpadding="0" border="0">
    <tr valign="top" align="right">
    <?php if ($args['spam_revert'] && $UseSpamRevert && $UserName) : ?>
        <form name="revertForm" method="post" action="<?php print revertURL($page); ?>"></form>
        <td>
        <?php print toolbar_button('javascript:spamRevert();', 'Spam Revert', 0); ?>
        </td>
        <td><img src="spacer.png" alt="" width="40" height="1" border="0"></td>
    <?php endif; ?>
    <td><?php toolbar($page, $args); ?></td>
    </tr>
    </table>
</td>
</tr>

<tr>
<td colspan="2">
    <?php
    print '<h1>' . $args['heading'];
    if ($args['headlink'] != '')
    {
        if ($SeparateHeaderWords)
            print html_split_name($args['headlink']);
        else
            print $args['headlink'];
    }
    if (count($twin = $pagestore->twinpages($args['headlink'])))
    {
        // point at the sisterwiki's version
        print '<sup class="printhide">';
        foreach ($twin as $site)
            print " " . html_twin($site[0], $site[1]);
        print '</sup>';
    }
    print $args['headsufx'] . '</h1>';

    if (isset($args['redirect_from']) && $args['redirect_from']) {
        print '(Redirected from <a href="' . viewURL($args['redirect_from']) . '&no_redirect=1">';
        if ($SeparateHeaderWords)
            print htmlspecialchars(html_split_name($args['redirect_from']));
        else
            print htmlspecialchars($args['redirect_from']);
        print '</a>)';
    }
    ?>
</td>
</tr>
</table>

</div>

</NOINDEX>

<table class="maintable" width="98%" align="center" cellspacing="0"
    cellpadding="10" border="1" bordercolor="black" bgcolor="white">
<tr>
<td>
<?php
}


// This function generates the common prologue and header
// for the various templates.
//
// Its parameters are passed as an associative array with the following
// members:
//
//   'twin'      => A string containing the page's name; if not empty,
//                  twin pages will be sought and printed.
//   'edit'      => A string containing the page's name; if not empty,
//                  an edit link will be printed.
//   'editver'   => An integer containing the page's version; if not
//                  zero, the edit link will be directed at the given
//                  version.  If it is -1, the page cannot be edited,
//                  and a message to that effect will be printed.
//   'history'   => A string containing the page's name; if not empty,
//                  a history link will be printed.
//   'timestamp' => Timestamp for the page.  If not empty, a 'document
//                  last modified' note will be printed.
//   'nosearch'  => An integer; if nonzero, the search form will not appear.
//   'page_length' => The length of the page in terms of characters in the
//                    database, i.e. before being parsed.

function template_common_epilogue($args)
{
  global $AdditionalFooter, $AllowAnonymousPosts, $EmailSuffix;
  global $EnableSubscriptions, $EnableCaptcha, $HomePage, $NickName, $page;
  global $pagestore, $PageTooLongLen, $PrefsScript, $UserName;

  $pg = $pagestore->page($page);
  $pagetext = $pg->text;
?>
</td>
</tr>
</table>
<NOINDEX>
<div class="printhide">
<div id="footer">
<table align="center" class="bottombox" border="0">
<tr>
<td>
<small><?php
if ($UserName)
    print("Logged in as " . html_ref($UserName, $UserName));
else
    print("Not <a href=\"login/?$page\">logged in</a>");
?></small>
<?php
if ($EnableSubscriptions && isset($EmailSuffix) && $UserName != ''
    && isset($args['subscribe']) && !empty($args['subscribe'])) {
    if ($pg->isSubscribed($UserName))
        $caption = 'Unsubscribe';
    else
        $caption = 'Subscribe';

    print ' | <small><a href="' . pageSubscribeURL($args['subscribe']) . '">' .
          $caption . '</a></small>';
}
?>
<?php
if (!$UserName) {
    print ' | <small><a href="' . viewURL($page) . '&view_source=1">View source</a></small>';
}
?>
</td>
<td colspan="2" align="right"><?php toolbar($page, $args); ?></td>
</tr>

<tr><td colspan="3">
<?php
print html_ref('RecentChanges', 'RecentChanges') . ', ' .
      '<a href="' . $PrefsScript . '">UserOptions</a>';
$help_page = $pagestore->page('HelpPage');
if ($help_page->exists()) {
    print ', ' . html_ref('HelpPage', 'HelpPage');
}
?>
</td></tr>

<tr><td align="center" colspan="3">
<?php
if (isset($args['timestamp']))
{
    print '<i>Last edited ' . html_time($args['timestamp']);
    if ($args['timestamp'] != '')
    {
        if (isset($args['euser']) && $args['euser'])
            print ' by ' . $args['euser'];
        else
            print ' anonymously';
    }
}

if (isset($args['twin']) && $args['twin'] != '')
{
    if (count($twin = $pagestore->twinpages($args['twin'])))
    {
        print '<br>See twins of this page: ';
        for ($i = 0; $i < count($twin); $i++)
        {
            print html_twin($twin[$i][0], $twin[$i][1]) . ' ';
        }
    }
}
?>
</td></tr>

<?php
if (!in_array($page, array($HomePage, 'RecentChanges')) &&
    ($UserName || $AllowAnonymousPosts))
{
?>
    <tr><td colspan="3">

    <script language="javascript">
    <!--
    function epilogue_quickadd_validate(form)
    {
        if (form.quickadd.value == '') {
            alert('Please provide content for the text field.');
            return false;
        } else if (form.validationcode && form.validationcode.value == '') {
            alert('The validation code is required.');
            return false;
        } else {
            return true;
        }
    }
    //-->
    </script>

    <table width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
    <td width="50%">&nbsp;</td>
    <td width="50%" align="right">

    <?php
    if ($args['edit'])
    {
        if ($args['page_length'] > $PageTooLongLen)
        {
            print '<div style="color:red;font-weight:bold">'.
                  'This page is too long. Comments are disabled.</div>';
        }
        else
        {
            global $document;
            $document = $pg->read();
            $document = str_replace('"', "\\\\'", $document);
            ?>
            <form method="post" action="<?php print saveURL($page); ?>">
            <div class="form">
            <input type="hidden" name="Save" value="1">
            <input type="hidden" name="appending" value="1">
            <?php
            if (!strcasecmp($page, 'annoyingquote') || !strcasecmp($page, 'accumulatedwisdom'))
            {
                // Tweaked "Add a Comment" for AnnoyingQuote page
                ?>
                <input type="hidden" name="comment" value="Add a Quote">
                <input type="hidden" name="appendingQuote" value="1">
                <table width="100%" cellspacing="2" cellpadding="0" border="0">
                <tr valign="bottom">
                <td width="1%" align="right">Quote:&nbsp;</td>
                <td width="99%" nowrap><textarea name="quickadd" rows="2" cols="20" wrap="virtual"></textarea></td>
                </tr>
                <tr valign="bottom">
                <td width="1%" align="right">Author:&nbsp;</td>
                <td width="99%" nowrap><input class="fullWidth" type="text" name="quoteAuthor" size="20" value=""></td>
                </tr>
                </table>
                <?php
                if (!$UserName && $EnableCaptcha) {
                    print_captcha_box();
                }
                ?>
                <input type="submit" name="append" value="Add a Quote" onClick="return epilogue_quickadd_validate(this.form)">
                <?php
            }
            else
            {
                // Standard Add a Comment
                print '<input type="hidden" name="comment" value="Comment">';
                print '<textarea name="quickadd" rows="4" cols="20">';
                print "<hr><b>";
                if ($UserName) {
                    print "[$UserName]";
                } else if ($NickName) {
                    print htmlspecialchars($NickName);
                } else {
                    print "Anonymous@" . $_SERVER["REMOTE_ADDR"];
                }
                print " (" . date('Y/m/d') . ")</b>: ";
                print '</textarea>';
                print "<br>\n";
                if (!$UserName) {
                    if ($EnableCaptcha) {
                        print_captcha_box();
                    }
                    if (!$NickName) {
                        print '<small>(Anonymous users, see <a href="'.$PrefsScript.'">UserOptions</a> to set a nickname.)</small>&nbsp;'   ;
                    }
                }
                print '<input type="submit" name="append" value="Add a Comment" onClick="return epilogue_quickadd_validate(this.form)">';
            }
            ?>
            </div>
            </form>
            <?php
        }
    }
    ?>

    </td>
    </tr>
    </table>

    </td></tr>
<?php
}
?>
</table>

</div>

<p>

<?php
if ($AdditionalFooter)
    include($AdditionalFooter);
?>
</div>
</NOINDEX>
</body>
</html>

<?php
}
?>
