<?php

require_once('template/common.php');

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
    template_common_prologue(array(
        'norobots' => 1,
        'title'    => 'Find ' . $args['find'],
        'heading'  => $args['find'] . ' and related pages',
        'headlink' => '',
        'headsufx' => '',
        'toolbar'  => 1,

        'button_selected'  => '',
        'button_view'      => 0,
        #'timestamp'       => $args['timestamp']  no diff
        #'editver'         => $args['editver']  no edit
        'button_backlinks' => 0
    ));
?>

<div class="content">

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
    print '<p>Search results for "' . htmlentities($find) . '":<ul>';

    if ($args['branch_search'])
    {
        print 'Restricted to children of ' . $args['branch_search'] . '<br>';
    }

    print $args['pages'] . "</ul>";
}

global $HomePage;
print "<p>(Back to " . html_ref($HomePage, $HomePage) . ".)";
    
?>

</div>

<?php
    template_common_epilogue(array(
        'twin'      => '',
        'edit'      => '',
        'editver'   => -1,
        'history'   => '',
        'timestamp' => '',

        'headlink'         => '',
        'button_selected'  => '',
        'button_view'      => 0,
        #'timestamp'       => $args['timestamp']  no diff, already specified
        #'editver'         => $args['editver']  no edit, already specified
        'button_backlinks' => 0
    ));
}

function toolbar_find($args)
{
    print $args['pages'];
}
?>
