<?php

require_once('template/common.php');
require_once('template/tree.php');

// The children template is passed an associative array with the following
// elements:
//
//   page     => A string containing the name of the wiki page being viewed.
//   children => An array containing the list of children, in the same format
//               as returned from pagestore->getTree().

function template_children($args)
{
    template_common_prologue(array(
        'norobots' => 1,
        'title'    => 'Children of ' . $args['page'],
        'heading'  => 'Children of ',
        'headlink' => $args['page'],
        'headsufx' => '',
        'toolbar'  => 1,

        'button_view' => 1,
        'timestamp' => $args['timestamp'],
        'editver'   => $args['editver'],
        'button_backlinks' => 1
    ));

    global $pagestore;

    $page = $args['page'];
    $children = $args['children'];
?>

<h2>Children Tree</h2>

<p>
<table cellspacing="0" cellpadding="0" border="0">
<tr valign="top">
<td>

<p>
<?php
if ($children) {
    $tree = array();
    $tree[$page] = array();
    foreach ($children as $child) {
        $tree[$page][$child] = array();
    }
    drawTree($tree);
} else {
    print 'No children';
}
?>

</td>
</tr>
</table>

<?php
    template_common_epilogue(array('twin'      => '',
                                   'edit'      => '',
                                   'editver'   => 0,
                                   'history'   => '',
                                   'timestamp' => ''));
}
?>
