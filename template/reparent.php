<?php
// $Id: save.php,v 1.4 2002/01/01 20:13:19 smoonen Exp $

// The reparent template is passed an associative array with the following
// elements:
//
//   page      => A string containing the name of the wiki page being reparented.

function template_reparent($args) {
    $newLocation = viewURL($args['page']);

    header('Location: ' . $newLocation);
}
?>
