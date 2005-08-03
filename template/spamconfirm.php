<?php

require_once('template/common.php');

function look_like_spam($comment)
{
    preg_match_all('/http:\/\//', $comment, $matches);
    if (count($matches[0]) > 5) { return true; }

    preg_match('/^(<hr><b>.*?<\/b>:)?(.*)$/', trim($comment), $matches);
    if (count(preg_split('/ +/', $matches[2], -1, PREG_SPLIT_NO_EMPTY)) < 5) { return true; }

    return false;
}

function template_spamconfirm($args)
{
    template_common_prologue(array(
        'norobots' => 1,
        'title'    => $args['page'],
        'heading'  => '',
        'headlink' => $args['page'],
        'headsufx' => '',
        'toolbar'  => 1,

        'button_selected'  => '',
        'button_view'      => 0,
        #'timestamp'        => $args['timestamp']  no diff
        #'editver'          => $args['editver']  no edit
        'button_backlinks' => 0
    ));
?>

<h2>Spam Detection</h2>

<p>
The comment you submitted has been identified as possible spam based on length and content.<br>
Please provide any necessary corrections and confirm that the comment should be posted.

<p>
<form method="post" action="<?php print saveURL($args['page']); ?>">

<input type="hidden" name="Save" value="1">
<input type="hidden" name="appending" value="1">
<input type="hidden" name="isnotspam" value="1">
<input type="hidden" name="comment"
    value="<?php print htmlspecialchars($args['comment']); ?>">

<table width="50%">
<tr>
<td>
<textarea name="quickadd" rows="4" cols="20"
    ><?php print htmlspecialchars($args['quickadd']); ?></textarea>
</td>
</tr>
<?php if ($args['appendingQuote']) : ?>
    <tr>
    <td>
    <input type="hidden" name="appendingQuote" value="1">
    <input class="fullWidth" type="text" name="quoteAuthor" size="20"
        value="<?php print htmlspecialchars($args['quoteAuthor']); ?>">
    </td>
    </tr>
<?php endif; ?>
</table>

<p>
<input type="submit" name="append" value="Confirm"
    onClick="return epilogue_quickadd_validate(this.form)">

</form>

<?php
    template_common_epilogue(array(
        'twin'      => '',
        'edit'      => '',
        'editver'   => -1,
        'history'   => '',

        'headlink'         => $args['page'],
        'button_selected'  => '',
        'button_view'      => 0,
        #'timestamp'       => $args['timestamp']  no diff
        #'editver'         => $args['editver']  no edit
        'button_backlinks' => 0
    ));
}
?>
