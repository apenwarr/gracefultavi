<?php

require_once('template/common.php');

// The history template is passed an associative array with the following
// elements:
//
//   page      => A string containing the name of the wiki page.
//   history   => A string containing the XHTML markup for the history form.
//   diff      => A string containing the XHTML markup for the changes made.

function template_history($args)
{
    global $DiffScript, $EnableWordDiff, $full, $ver1;

    template_common_prologue(array(
        'norobots' => 1,
        'title'    => 'History of ' . $args['page'],
        'heading'  => 'History of ',
        'headlink' => $args['page'],
        'headsufx' => '',
        'toolbar'  => 1,
        'tree'     => 1,
        'spam_revert' => 1,

        'button_selected'  => 'diff',
        'button_view'      => 1,
        'timestamp'        => $args['timestamp'],
        'editver'          => $args['editver'],
        'button_backlinks' => 1
    ));

    if ($args['diff_mode'] == 1)
    {
        $regular_diff_checked = '';
        $word_diff_checked = ' checked';
    }
    else
    {
        $regular_diff_checked = ' checked';
        $word_diff_checked = '';
    }
?>

<div class="content">

<form method="get" action="<?php print $DiffScript; ?>">
<input type="hidden" name="action" value="history">
<input type="hidden" name="page" value="<?php print $args['page']; ?>">
<?php
if ($full)
    print '<input type="hidden" name="full" value="1">';
?>

<div class="form">
<table border="0">
<tr>
<td><strong>Older</strong></td>
<td><strong>Newer</strong></td>
<td></td>
</tr>
<?php print $args['history']; ?>
<tr><td colspan="3">
    <input type="submit" value="Compute Difference">
    <?php if ($EnableWordDiff) : ?>
    <input type="radio" id="regular_diff" name="diff_mode" value="0"<?=$regular_diff_checked?>><label for="regular_diff">Regular diff</label>
    <input type="radio" id="word_diff" name="diff_mode" value="1"<?=$word_diff_checked?>><label for="word_diff">Word diff</label>
    <?php endif; ?>
</td></tr>
</table>
</div>
</form>

<hr><br>

<?php
print '<strong>';
if ($ver1)
    print 'Difference between versions:';
else
    print 'Changes by last author:';
print '</strong><br><br>';

if ($args['diff'])
{
    print '<div class="diff">';
    print $args['diff'];
    print '</div>';
}
else
    print 'There were no differences between the selected versions.';
?>

</div>

<?php
    template_common_epilogue(array(
        'twin'      => $args['page'],
        'edit'      => '',
        'editver'   => $args['editver'],
        'history'   => $args['page'],
        'euser'     => $args['edituser'],
        'timestamp' => $args['timestamp'],
        'toolbar'  => 1,

        'headlink'         => $args['page'],
        'button_selected'  => 'diff',
        'button_view'      => 1,
        #'timestamp'       => $args['timestamp']  no diff, already specified
        #'editver'         => $args['editver']  already specified
        'button_backlinks' => 1
    ));
}
?>
