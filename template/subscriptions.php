<?php

// The subscriptions template is passed an associative array with the following
// elements:
//
//   subscriptions => An array containing the name of the subscribed page of a
//                    user.

require('parse/html.php');
require_once('template/common.php');

function template_subscriptions($args)
{
    template_common_prologue(array(
        'norobots' => 1,
        'title'    => 'Subscriptions',
        'heading'  => 'Subscriptions',
        'headlink' => '',
        'headsufx' => '',
        'toolbar'  => 0,

        'button_view' => 0,
        #'timestamp' => $args['timestamp']  no diff
        #'editver'   => $args['editver']  no edit
        'button_backlinks' => 0
    ));
?>

<h2>Page Subscriptions</h2>

<form name="subscriptionsForm" action="<?php print $SubscriptionsScript; ?>" method="POST">
<input type="hidden" name="subscribed_pages[]" value="">

<?php if ($args['subscriptions']) : ?>

    <p>
    Uncheck any pages you want to remove from your subscriptions.

    <p>
    <?php
        foreach ($args['subscriptions'] as $page) {
            print '<input type="checkbox" name="subscribed_pages[]" value="' . urlencode($page) . '" checked>';
            print ' <a href="' . viewURL($page) . '">' . $page . "</a><br>\n";
        }
    ?>

    <p>
    <input type="submit" value="Save">

<?php else : ?>

   <p>
   You have no page subscriptions.

<?php endif; ?>

</form>

<?php
     template_common_epilogue(array('nosearch' => 1));
}
?>
