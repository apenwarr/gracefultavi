<?php
// $Id: view.php,v 1.9 2002/01/10 01:31:04 smoonen Exp $

require_once('template/common.php');
#require_once(TemplateDir . '/common.php');

// The view template is passed an associative array with the following
// elements:
//
//   page      => A string containing the name of the wiki page being viewed.
//   html      => A string containing the XHTML rendering of the wiki page.
//   editable  => An integer.  Will be nonzero if user is allowed to edit page.
//   timestamp => Timestamp of last edit to page.
//   archive   => An integer.  Will be nonzero if this is not the most recent
//                version of the page.
//   version   => Version number of page version being viewed.

function template_view($args)
{
    template_common_prologue(array(
        'heading'  => '',
        'headlink' => $args['page'],
        'headsufx' => $args['archive'] ?
                      ' (' . html_timestamp($args['timestamp']) . ')' : '',
        'norobots' => $args['archive'],
        'quote'    => 1,
        'redirect_from' => $args['redirect_from'],
        'title'    => $args['page'],
        'toolbar'  => 1,
        'tree'     => 1,

        'button_selected'  => 'view',
        'button_view'      => 1,
        'timestamp'        => $args['timestamp'],
        'editver'          => $args['editver'],
        'button_backlinks' => 1
    ));
?>

<div id="body" class="content">
<?php
print $args['html'];
// Aligns the browser with an HTML anchor, showing the last added comment (or quote)
// See: action/save.php, template/save.php, template/view.php
?>

<a name="pageContentBottom">
</div>
<?php
    template_common_epilogue(array(
        'subscribe' => $args['page'],
        'twin'      => $args['page'],
        'edit'      => $args['page'],
        'editver'   => !$args['editable'] ? -1 :
                       ($args['archive'] ? $args['version'] : 0),
        'history'   => $args['page'],
        'euser'     => $args['edituser'],
        'timestamp' => $args['timestamp'],

        'headlink'         => $args['page'],
        'button_selected'  => 'view',
        'button_view'      => 1,
        #'timestamp'       => $args['timestamp']  already specified
        #'editver'         => $args['editver']  already specified
        'button_backlinks' => 1,
    ));
}
?>
