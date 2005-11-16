<?php

require_once('template/common.php');

function look_like_spam($comment)
{
    if (preg_match('/<body|<head|<html|<meta|<script|<title/i',
                   $comment)) {
        return 2;
    }

    preg_match_all('/<a .*?href="(.*?)">\1<\/a>/i', $comment, $matches);
    if (count($matches[0]) > 0) {
        return 2;
    }

    preg_match_all('/http:\/\//', $comment, $matches);
    if (count($matches[0]) > 20) {
        return 2;
    }
    if (count($matches[0]) > 5) {
        return 1;
    }

    preg_match('/^(<hr><b>.*?<\/b>:)?(.*)$/s', trim($comment), $matches);
    if (count(preg_split('/ +/', $matches[2], -1, PREG_SPLIT_NO_EMPTY)) < 5) {
        return 1;
    }

    return 0;
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

<?php if ($args['spam_level'] == 2) : ?>

    <div id="body" class="content">

    <?php
    require_once('parse/main.php');
    global $page, $pagestore, $ParseEngine;

    $pg = $pagestore->page($page);
    $pg->read();
    $document = $pg->text . "\n\n" . $args['quickadd'];
    print parseText($document, $ParseEngine, $page);
    ?>

    </div>

<?php else : ?>

    <h2>Questionable Contribution Detection</h2>

    <p>
    The comment you submitted has been identified as a questionable contribution
    based on length and content.<br>
    Please provide any necessary corrections and confirm that the comment should
    be posted.

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

<?php endif; ?>

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
