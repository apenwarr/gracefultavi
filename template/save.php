<?php

// The save template is passed an associative array with the following
// elements:
//
//   page      => A string containing the name of the wiki page being saved.
//   text      => A string containing the wiki markup for the given page.
//
//   anchor    => A string containing the name of the HTML anchor.

// Aligns the browser with an HTML anchor, showing the last added comment (or quote)
// See: action/save.php, template/save.php, template/view.php

function template_save($args) {
    // You might use this to put up some sort of "thank-you" page like Ward
    // does in WikiWiki, or to display a list of words that fail spell-check.
    // For now, we simply redirect to the view action for this page.

    $newLocation = viewURL($args['page']) . '&no_redirect=1';
    if(isset($args['anchor']))
        $newLocation .= "#" . $args['anchor'];

    header('Location: ' . $newLocation);
}
?>
