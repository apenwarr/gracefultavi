<?php
// $Id: find.php,v 1.1.1.1 2003/03/15 03:53:58 apenwarr Exp $

require_once('template/common.php');
#require_once(TemplateDir . '/common.php');

// The find template is passed an associative array with the following
// elements:
//
//   find      => A string containing the text that was searched for.
//   pages     => A string containing the XHTML markup for the list of pages
//                found containing the given text.
//   branch_search => A string containing the top node of the branch to search
//                    through.

function template_find($args)
{
  template_common_prologue(array('norobots' => 1,
                                 'title'    => 'Find ' . $args['find'],
                                 'heading'  => $args['find'] . ' and related pages',
                                 'headlink' => '',
                                 'headsufx' => '',
                                 'toolbar'  => 1));
?>

<div id="body">

<?php
$find = $args['find'];
global $pagestore;
$searchpage = $pagestore->page($find);

$pageExists = $searchpage->exists();

if ($pageExists)
    print "<h3>You're probably looking for " . html_ref($find, $find) . ".</h3>";
else
    print "<h3>'" . htmlentities($find) . "' isn't currently a page in the wiki.</h3>";

if ($args['pages'])
{
    print '<br><hr align=left><br>';

    if ($pageExists)
        print 'Other pages ';
    else
        print 'Pages ';

    if ($args['branch_search'])
        print '(children of ' . $args['branch_search'] . ') ';

    if ($pageExists)
        print 'related to ';
    else
        print 'containing ';

    print "'" . htmlentities($find) . "'" . ':<br>';

/*
    if ($searchpage->exists())
        print "<br><hr align=left><br>Other pages related to  " . htmlentities($find) . ":<br>";
    else
        print "<br><hr align=left><br>Pages containing '" . htmlentities($find) . "':<br>";
*/

    print $args['pages'];
}
?>

</div>

<?php
  template_common_epilogue(array('watch'     => '',
                                 'twin'      => '',
                                 'edit'      => '',
                                 'editver'   => 0,
                                 'history'   => '',
                                 'timestamp' => ''));
}

function toolbar_find($args)
{
    print $args['pages'];
}
?>
