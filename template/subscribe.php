<?php
// The subscribe template is passed an associative array with the following
// elements:
//
//   page      => A string containing the name of the wiki page being
//                (un)subscribed.

function template_subscribe($args) {
    $newLocation = viewURL($args['page']);

    header('Location: ' . $newLocation);
}
?>
