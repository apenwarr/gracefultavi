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

function template_view($args) {
  template_common_prologue(array('norobots' => $args['archive'],
                                 'title'    => $args['page'],
                                 'heading'  => '',
                                 'headlink' => $args['page'],
                                 'headsufx' => $args['archive'] ?
                                                 ' (' . html_timestamp($args['timestamp']) . ')'
                                                 : '',
                                 'tree' => 1,
                                 'quote' => 1,
                                 'toolbar'  => 1));
?>
<div id="body" class="content">
<?php
print $args['html'];
// Modified by mich on November 21, 2002, new feature
// Aligns the browser with an HTML anchor, showing the last added comment (or quote)
// See: action/save.php, template/save.php, template/view.php
?>

<a name="pageContentBottom">
</div>
<?php
  template_common_epilogue(array('watch'     => $args['page'],
                                 'twin'      => $args['page'],
                                 'edit'      => $args['page'],
                                 'editver'   => !$args['editable'] ? -1
                                                : ($args['archive']
                                                   ? $args['version'] : 0),
                                 'history'   => $args['page'],
                                 'euser'     => $args['edituser'],
                                 'timestamp' => $args['timestamp']));
}
?>
