<?php
// $Id: common.php,v 1.7 2002/01/15 16:22:39 smoonen Exp $

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

//require_once('lib/pagestore.php');
require_once('template/tree.php');

function template_common_prologue($args)
{
    global $WikiName, $HomePage, $WikiLogo, $MetaKeywords, $MetaDescription;
    global $StyleScript, $SeparateTitleWords, $SeparateHeaderWords, $UserName;
    global $shortcutIcon;

    if($SeparateTitleWords)
        { $args['title'] = html_split_name($args['title']); }
?>

<html>
<head>
<meta name="KEYWORDS" content="<?php print $MetaKeywords; ?>">
<meta name="DESCRIPTION" content="<?php print $MetaDescription; ?>">
<?php if($args['norobots']) {?>
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
<?php } ?>
<link rel="STYLESHEET" href="<?php print $StyleScript; ?>" type="text/css">
<link rel="SHORTCUT ICON" href="<?=$shortcutIcon?>">
<title><?php print $args['title'] . ' - ' . htmlspecialchars($WikiName); ?></title>
</head>

<body>

<?php print '<small><a href="' . contentURL($args['headlink']) . '">Entire wiki contents</a></small>'; ?>

<table class="topbox" border="0">
<tr valign="top"><td>
<div id="header">

<div class="logo">
<a href="<?php print viewURL($HomePage); ?>"><img src="<?php print $WikiLogo; ?>" alt="[Home]"></a>
</div>

<?php
print '<h1>' . $args['heading'];
if($args['headlink'] != '')
{
//    print '<a class="title" href="' . findURL($args['headlink']) . '">';
    print '<a class="title" href="' . backlinksURL($args['headlink']) . '">';
    if($SeparateHeaderWords)
        print html_split_name($args['headlink']);
    else
        print $args['headlink'];
    print '</a>';
}
print $args['headsufx'] . '</h1>';
?>

<br>
<form method="get" action="<?php print $FindScript; ?>">
<div class="form">
<input type="hidden" name="action" value="find">
Jump to: <input type="text" name="find" size="20">
</div>
</form>

<?php
if($args['quote']) {
    global $pagestore, $ParseEngine;
    $quotepage = $pagestore->page(AnnoyingQuote);
    $quote = $quotepage->read();
    if($quotepage->exists()) {
        foreach(explode("\n\n", $quotepage->text) as $line);
        $yada = $line;
        print '<div align="right"><span class="quote">';
        print parseText($yada, $ParseEngine, $page);
        print '</span></div>';
    }
}
?>

</div> <!-- header -->

<?php
/*
if($args['related']) {
    global $pagestore;

    $pagename = $args['headlink'];
    $list = $pagestore->find($pagename);
    $text = '';
    $i = 0;
    foreach($list as $page) {
        if($page != $pagename && $i<5 ) {
            $text .= html_ref($page, $page) . html_newline();
        }
        $i++;
    }
    if($text) {
        print '</td><td valign="top">';
        print '<strong>Related Pages</strong><br>';
        print $text;
    }
    if($i>5) {
        print "<a class=\"title\" href=\"" . findURL($args['headlink']) . "\"><ul><li>More Pages</li></a>";
    }
}
*/
?>

<?php
if($args['tree'])
{
    global $pagestore;

    $tree = $pagestore->getTreeFromLeafs('FrontPage', $args['headlink']);

    if ($tree['FrontPage'] && count($tree['FrontPage']) > 0)
    {
        // print '</td><td valign="top">';
        // drawTreeOld($tree);
        print '</td><td><img src="images/spacer.png" alt="" width="20" height="1" border="0">';
        print '</td><td valign="top" align="right">';
        drawTree($tree, true, $args['headlink']);
    }
}
?>

</td></tr>
</table>

<table width="100%" border="1" bordercolor="black" cellspacing="0" bgcolor="white" cellpadding="10">
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
//   'watch'     => A string containing the page's name.
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
  global $FindScript, $pagestore, $page, $UserName, $PrefsScript;

  $pg = $pagestore->page($page);
  $pagetext = $pg->text;
?>

<!-- start of epilogue -->

</td>
</tr>
</table>

<small>Logged in as <?php print html_ref($UserName, $UserName) ?></small>

<?php
if ($UserName != '' && $args['watch'] != '' && $page != 'FrontPage' && $page != 'RecentChanges')
{
    if ($pg->isWatched($UserName))
        $caption = 'Remove watch';
    else
        $caption = 'Watch this page';

    print '| <small>';
    print '<a href="' . pageWatchURL($args['watch']) . '">';
    print '<img src="images/watch.png" alt="' . $caption . '" title="' . $caption . '" width="19" height="13" border="0">';
    print $caption;
    print '</a>';
    print '</small>';
}
?>

<p></p>

<div id="footer">
<table class="bottombox"><tr><td>

<?php
// if($args['toolbar']) { print html_toolbar_bottom(); }
//print html_toolbar_bottom();

print html_ref('RecentChanges', 'RecentChanges') . ', ' .
      '<a href="' . $PrefsScript . '">UserOptions</a>';

if ($page != 'RecentChanges')
{
    if($args['timestamp'])
    {
        print '<td align="center"><i>Last edited ' . html_time($args['timestamp']);

        if($args['euser'])
            print ' by ' . $args['euser'];
        else
            print ' anonymously';

        print ' <a href="' . historyURL($args['history']) . '">(diff)</a></i><br>';
    }

    if($args['twin'] != '')
    {
        if(count($twin = $pagestore->twinpages($args['twin'])))
        {
            print 'Twin pages: ';
            for($i = 0; $i < count($twin); $i++)
            {
                print html_twin($twin[$i][0], $twin[$i][1]) . ' ';
            }
            print '</td><br>';
        }
    }

    if ($args['edit'])
    {
        if ($args['editver'] == 0)
            print '<td align="right"><b><a href="' . editURL($args['edit']) . '">Edit this page</a></b></td>';
        else if($args['editver'] == -1)
            print 'This document cannot be edited';
        else
        {
            print '<td align="left"><a href="' . editURL($args['edit'], $args['editver']) . '">';
            print 'Edit this <em>ARCHIVE VERSION</em> of this document</a></td>';
        }
    }
}
?>
</td></tr></table>

<?php
if ($page != 'FrontPage' && $page != 'RecentChanges')
{
?>
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
    // -->
    </script>

    <table width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
    <td width="50%"><!-- padding empty cell--></td>
    <td width="50%" align="right">
        <form method="post" action="<?php print saveURL($args['page']); ?>">
        <div class="form">
        <?php
        if($args['edit'])
        {
            global $document;
            $document = $pg->read();
            $document = str_replace('"', "\\\\'", $document);
        ?>
            <input type="hidden" name="Save" value="1">
            <input type="hidden" name="appending" value="1">
            <input type="hidden" name="page" value="<?php print $page ?>">
            <?php
            // Modified by mich on Sept 30, 2002, fix the "Add a Comment" bug
            // print '<input type="hidden" name="document" value="' . $document . '">';
            ?>
            <input type="hidden" name="document" value="This value should never be used anywhere. If you see it, other than in a view html source, please contact someone in charge of the wiki.">
            <?php
            if(!strcasecmp($page, 'annoyingquote'))
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
                print "<hr><b>[$UserName] (" . date('Y/m/d') . ")</b>: ";
                print '</textarea>';
                print '<input type="submit" name="append" value="Add a Comment" onClick="return epilogue_quickadd_validate(this.form)">';
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
<?php
}
?>

</div>

</body>
</html>

<!-- end of epilogue -->
<?php
}
?>
