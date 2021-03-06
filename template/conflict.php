<?php

require_once('template/common.php');

// The conflict template is passed an associative array with the following
// elements:
//
//   page      => A string containing the name of the wiki page being edited.
//   text      => A string containing the wiki markup of the version that was
//                saved while the user was editing the page.
//   html      => A string containing the XHTML markup of the version of the
//                page that was saved while the user was editing the page.
//   usertext  => A string containing the wiki markup of the text the user
//                tried to save.
//   merge     => If diff3 is enabled, contains a merged version of text and
//                usertext, including the conflicts marked with brackets
//   timestamp => Timestamp of last edit to page.
//   nextver   => An integer; the expected version of this document when saved.

function template_conflict($args)
{
    global $categories, $comment, $EditCols, $EditRows, $MaxPostLen, $minoredit;
    global $PrefsScript, $ShowCategoryBox, $template, $UserName;

    template_common_prologue(array(
        'norobots' => 1,
        'title'    => 'Editing ' . $args['page'],
        'heading'  => 'Editing ',
        'headlink' => $args['page'],
        'headsufx' => '',
        'toolbar'  => 1,

        'button_selected'  => '',
        'button_view'      => 1,
        'timestamp'        => $args['timestamp'],
        #'editver'         => $args['editver']  no edit
        'button_backlinks' => 1
    ));

    if ($args['merge'])
    {
        $conflictDoc = $args['merge'];
        $editBoxTitle = 'Merged Version With Conflicts';
        $conflictWarning = 'Please merge the conflicts marked with brackets '.
                           'in the box below.';
    }
    else
    {
        $conflictDoc = $args['text'];
        $editBoxTitle = 'Current Version';
        $conflictWarning = 'Please merge your edits into the current version '.
                           'of this document.';
    }
?>

<div id="body">
<p class="warning">
  <b>Warning! Since you started editing, this document has been changed by
  someone else. <?php print $conflictWarning; ?></b>
</p>
<h1><?php print $editBoxTitle?></h1>
<form method="post" action="<?php print saveURL($args['page']); ?>">
<input type="hidden" name="pagesizelimit" value="<?=$MaxPostLen?>">
<div class="form">
  <input type="submit" name="Save" value="Save" onClick="return sizeLimitCheck(this.form, 'document');">
  <input type="submit" name="Preview" value="Preview" onClick="return sizeLimitCheck(this.form, 'document');">
<?php
  if($UserName != '')
    { print 'Your user name is ' . html_ref($UserName, $UserName); }
  else
  {
?>  Visit <a href="<?php print $PrefsScript; ?>">Preferences</a> to set your
user name<?php
  }
?><br />
  <input type="hidden" name="nextver" value="<?php print $args['nextver']; ?>" />
  <textarea name="document" rows="<?php
    print $EditRows; ?>" cols="<?php
    print $EditCols; ?>" wrap="virtual"><?php
  print str_replace('<', '&lt;', str_replace('&', '&amp;', $conflictDoc));
?></textarea><br />
<?php
print '<input id="minoredit" type="checkbox" name="minoredit" value="1"';
if ($minoredit) print ' CHECKED';
print '><label for="minoredit">Minor edit</label> ';

print '<input id="template" type="checkbox" name="template" value="1"';
if ($template) print ' CHECKED';
print '><label for="template">This page is a template</label> ';
?>
<br>
  Summary of change:
  <input type="text" name="comment" size="40" maxlength="80" value="" />
<?php if ($ShowCategoryBox) : ?>
  <br />
  Add document to category:
  <input type="text" name="categories" size="40" value="" />
<?php endif; ?>

<?php if (!$args['merge']) : ?>
  <hr />
  <h1>Your changes</h1>
  <textarea name="discard" rows="<?php
    print $EditRows; ?>" cols="<?php
    print $EditCols; ?>" wrap="virtual"><?php
  print str_replace('<', '&lt;', str_replace('&', '&amp;', $args['usertext']));
  ?></textarea><br />
<?php endif; ?>

</div>
</form>
<h1>Preview of Current Version</h1>
<?php
  print $args['html'];
?>
</div>
<?php
    template_common_epilogue(array(
        'twin'      => $args['page'],
        'edit'      => '',
        'editver'   => 0,
        'history'   => $args['page'],
        'timestamp' => $args['timestamp'],

        'headlink'         => $args['page'],
        'button_selected'  => '',
        'button_view'      => 1,
        #'timestamp'       => $args['timestamp']  already specified
        #'editver'         => $args['editver']  no edit
        'button_backlinks' => 1
    ));
}
?>
