<?php
// $Id: common.php,v 1.9 2003/04/09 16:55:23 mich Exp $

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

require_once('template/tree.php');

function toolbar_button($url, $label)
{
    if ($url)
        print '<a class="button" href="'.$url.'">'.$label.'</a> ';
    else
        print '<span class="buttonDisabled">'.$label.'</span> ';
}


function toolbar($page, $args)
{
    // view
    toolbar_button($args['button_view'] ? viewURL($args['headlink']) : '', 'View');

    // edit
    $edit_label = 'Edit';
    if ($page != 'RecentChanges' && isset($args['editver'])
        && $args['editver'] > -1) {
        if ($args['editver'] == 0) {
            $edit_url = editURL($args['headlink']);
        } else {
            $edit_url = editURL($args['headlink'], $args['editver']);
            $edit_label = 'Edit Archive';
        }
    } else {
        $edit_url = '';
    }
    toolbar_button($edit_url, $edit_label);

    // diff
    $diff_url = ($page != 'RecentChanges' && isset($args['timestamp']) &&
                $args['timestamp'] != '') ? historyURL($args['headlink']) : '';
    toolbar_button($diff_url, 'Diff');

    // backlinks
    $backlinks_url = ($args['button_backlinks'] && $args['headlink']) ?
                    backlinksURL($args['headlink']) : '';
    toolbar_button($backlinks_url, 'Backlinks');

    // subscribe / unsubscribe
    /*
    if ($EnableSubscriptions && isset($EmailSuffix) && $UserName != '') {
        $pg = $pagestore->page($page);
        if ($pg->isSubscribed($UserName))
            $subscription_label = 'Unsubscribe';
        else
            $subscription_label = 'Subscribe';
        $subscription_url = $args['headlink'] ?
            pageSubscribeURL($args['headlink']) : '';
        toolbar_button($subscription_url, $subscription_label);
    }
    */
}

function template_common_prologue($args)
{
    global $AdditionalHeader, $HomePage, $MetaDescription, $MetaKeywords, $page;
    global $pagestore, $ScriptBase, $SeparateHeaderWords, $SeparateTitleWords;
    global $shortcutIcon, $StyleScript, $UserName, $WikiLogo, $WikiName;
    // global $EmailSuffix, $EnableSubscriptions # for subscription button

    if ($SeparateTitleWords) { $args['title'] = html_split_name($args['title']); }
?>

<html>
<head>
<meta name="KEYWORDS" content="<?php print $MetaKeywords; ?>">
<meta name="DESCRIPTION" content="<?php print $MetaDescription; ?>">
<?php if ($args['norobots']) {?>
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
<?php } ?>
<link rel="STYLESHEET" href="<?php print $StyleScript; ?>" type="text/css">
<link rel="SHORTCUT ICON" href="<?=$shortcutIcon?>">
<link rel="ALTERNATE" title="<?=htmlspecialchars($WikiName)?>" href="<?=$ScriptBase?>?action=rss" TYPE="application/rss+xml">
<title><?php print $args['title'] . ' - ' . htmlspecialchars($WikiName); ?></title>
</head>

<body>
<NOINDEX>

<?php
if ($AdditionalHeader) { include($AdditionalHeader); }
?>

<table align="center" class="topbox" border="0">
<tr valign="top"><td>
<?php print '<small><a href="' . contentURL($args['headlink']) . '">Entire wiki contents</a></small>'; ?>
</td></tr>

<tr valign="top"><td>
<div id="header">

<div class="logo">
<a href="<?php print viewURL($HomePage); ?>"><img src="<?php print $WikiLogo; ?>" alt="[Home]"></a>
</div>

<table cellspacing="0" cellpadding="0" border="0">
<tr>
<td>
<?php
print '<h1>' . $args['heading'];
if ($args['headlink'] != '')
{
    print '<a class="title" href="' . backlinksURL($args['headlink']) . '">';
    if ($SeparateHeaderWords)
        print html_split_name($args['headlink']);
    else
        print $args['headlink'];
    print '</a>';
}

if (count($twin = $pagestore->twinpages($args['headlink'])))
{
    // point at the sisterwiki's version
    //print "See also:";
    print "<sup>";
    foreach ($twin as $site)
      { print " " . html_twin($site[0], $site[1]); }
    print "</sup>";
}

print $args['headsufx'] . '</h1>';

if (isset($args['redirect_from']) && $args['redirect_from']) {
    print '</td></tr><tr><td>';
    print '<h2>Redirected from <a href="' . viewURL($args['redirect_from']) . '&no_redirect=1">' .
        htmlspecialchars($args['redirect_from']) . '</a></h2>';
}
?>
</td>
</tr>

<tr>
<td>
<br>
<form method="get" action="<?php print $FindScript; ?>">
<div class="form">
<input type="hidden" name="action" value="find">

<table cellspacing="0" cellpadding="0" border="0">
<tr>
<td>Jump to:&nbsp;</td>
<td><input type="text" name="find" size="20"></td>
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
</td>
</table>

<?php
if (isset($args['quote']))
{
    $quotepage = $pagestore->page('AnnoyingQuote');
    $quote = $quotepage->read();
    if ($quotepage->exists())
    {
        global $ParseEngine;
        foreach (explode("\n\n", $quotepage->text) as $line);
        $yada = $line;
        print '<div align="right"><span class="quote">';
        $yada = parseText($yada, $ParseEngine, $page);
        if (strtolower(substr($yada, 0, 3)) != '<p>') { $yada = '<p>' . $yada; }
        print $yada;
        print '</span></div>';
    }
}
?>

</div>

<?php
if (isset($args['tree']))
{
    $tree = $pagestore->getTreeFromLeaves($HomePage, $args['headlink']);

    if (isset($tree[$HomePage]) && count($tree[$HomePage]) > 0)
    {
        print '</td><td><img src="images/spacer.png" alt="" width="20" height="1" border="0">';
        print '</td><td valign="top" align="right">';
        drawTree($tree, true, $args['headlink']);
    }
}
?>

</td></tr>

<tr><td>
<?php toolbar($page, $args); ?>
</td></tr>

</table>
</NOINDEX>

<table width="98%" align="center" border="1" bordercolor="black" cellspacing="0" bgcolor="white" cellpadding="10">
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

function template_common_epilogue($args)
{
  global $AdditionalFooter, $EmailSuffix, $EnableSubscriptions, $FindScript;
  global $HomePage, $ndfnow, $page, $pagestore, $PrefsScript, $UserName;

  $pg = $pagestore->page($page);
  $pagetext = $pg->text;
?>
</td>
</tr>
</table>
<NOINDEX>
<div id="footer">
<table align="center" class="bottombox" cellspacing="0" cellpadding="0" border="0">
<tr>
<td>
<small><?php
if ($UserName)
    print("Logged in as " . html_ref($UserName, $UserName));
else
    print("Not <a href=\"login/?$page\">logged in</a>.");
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
</td>
<td colspan="2" align="right"><?php toolbar($page, $args); ?></td>
</tr>

<tr><td colspan="3">&nbsp;</td></tr>

<tr><td>
<?php
print html_ref('RecentChanges', 'RecentChanges') . ', ' .
               '<a href="' . $PrefsScript . '">UserOptions</a>';

if ($ndfnow) print '<br><br><a href="?NowOnWednesdays"><img src="images/ndfnow.png"></a>';

if ($page != 'RecentChanges')
{
    if (isset($args['timestamp']))
    {
        print '<td align="center"><i>Last edited ' . html_time($args['timestamp']);

        if ($args['timestamp'] != '')
        {
            if (isset($args['euser']))
                print ' by ' . $args['euser'];
            else
                print ' anonymously';

            print ' <a href="' . historyURL($args['history']) . '">(diff)</a></i>';
        }
    }

    if (isset($args['twin']) && $args['twin'] != '')
    {
        if (count($twin = $pagestore->twinpages($args['twin'])))
        {
            print '<br>See twins of this page in: ';
            for ($i = 0; $i < count($twin); $i++)
            {
                print html_twin($twin[$i][0], $twin[$i][1]) . ' ';
            }
            print '</td>';
        }
    }

    if (isset($args['edit']))
    {
        if ($args['editver'] == 0)
            print '<td align="right"><b><a href="' . editURL($args['edit']) . '">Edit this page</a></b></td>';
        else if ($args['editver'] == -1)
            ; //print 'This document cannot be edited';
        else
        {
            print '<td align="left"><a href="' . editURL($args['edit'], $args['editver']) . '">';
            print 'Edit this <em>ARCHIVE VERSION</em> of this document</a></td>';
        }
    }
}
?>
</td></tr>

<?php
if ($page != $HomePage && $page != 'RecentChanges')
{
?>
    <tr><td colspan="3">

    <script language="javascript">
    <!--
    function epilogue_quickadd_validate(form)
    {
        if (form.quickadd.value == '')
        {
            alert ('Please provide content for the text field.');
            return false;
        }
        else
            return true;
    }
    //-->
    </script>

    <table width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
    <td width="50%">&nbsp;</td>
    <td width="50%" align="right">
        <form method="post" action="<?php print saveURL($page); ?>">
        <div class="form">
        <?php
        if ($args['edit'])
        {
            global $document;
            $document = $pg->read();
            $document = str_replace('"', "\\\\'", $document);
        ?>
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
                <input type="submit" name="append" value="Add a Quote" onClick="return epilogue_quickadd_validate(this.form)">
            <?php
            }
            else
            {
                // Standard Add a Comment
                print '<input type="hidden" name="comment" value="Comment">';
                print '<textarea name="quickadd" rows="4" cols="20">';
                print "<hr><b>";
                if ($UserName)
                    print "[$UserName]";
                else
                    print "Anonymous@" . $_SERVER["REMOTE_ADDR"];
                print " (" . date('Y/m/d') . ")</b>: ";
                print '</textarea>';
                print '<br><input type="submit" name="append" value="Add a Comment" onClick="return epilogue_quickadd_validate(this.form)">';
            }
            ?>
        <?php
        }
        ?>
        </div>
        </form>
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
</NOINDEX>
</body>
</html>

<?php
}
?>
