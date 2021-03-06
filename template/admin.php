<?php

require_once('template/common.php');

// The admin template is passed an associative array with the following
// elements:
//
//   html      => A string containing the XHTML markup of the form to be
//                displayed.

function template_admin($args)
{
    template_common_prologue(array(
        'norobots' => 1,
        'title'    => 'Administration',
        'heading'  => 'Administration',
        'headlink' => '',
        'headsufx' => '',
        'toolbar'  => 0,

        'button_selected'  => '',
        'button_view'      => 0,
        #'timestamp'       => $args['timestamp']  no diff
        #'editver'         => $args['editver']  no edit
        'button_backlinks' => 0
    ));
?>

<div id="body">
<?php print $args['html']; ?>
</div>
<?php
    template_common_epilogue(array(
        'nosearch' => 1,

        'headlink'         => '',
        'button_selected'  => '',
        'button_view'      => 0,
        #'timestamp'       => $args['timestamp']  no diff
        #'editver'         => $args['editver']  no edit
        'button_backlinks' => 0
    ));
}
?>
