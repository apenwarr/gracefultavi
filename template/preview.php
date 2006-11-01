<?php

require_once('template/common.php');

// The preview template is passed an associative array with the following
// elements:
//
//   page      => A string containing the name of the wiki page being viewed.
//   text      => A string containing the wiki markup of the wiki page.
//   section     => An integer, section number being edited
//   text_before => A string, content of the page before the edited section
//   text_after  => A string, content of the page after the edited section
//   html      => A string containing the XHTML rendering of the wiki page.
//   timestamp => Timestamp of last edit to page.
//   nextver   => An integer; the expected version of this document when saved.
//   archive   => An integer.  Will be nonzero if this is not the most recent
//                version of the page.
//   diff      => A computed diff of the changes from the current edit.
//   diff_mode => A flag indicating the diff mode currently used.

function template_preview($args)
{
    global $categories, $comment, $EditCols, $EditRows, $EnableWordDiff;
    global $PageSizeLimit, $PrefsScript, $ShowCategoryBox, $UserName;

    $section_title = $args['section'] ? 'section of ' : '';

    template_common_prologue(array(
        'norobots' => 1,
        'title'    => 'Previewing ' . $section_title . $args['page'],
        'heading'  => 'Previewing ' . $section_title,
        'headlink' => $args['page'],
        'headsufx' => '',
        'tree'     => 1,
        'toolbar'  => 1,

        'button_selected'  => '',
        'button_view'      => 1,
        'timestamp'        => $args['timestamp'],
        #'editver'         => $args['editver']  no edit
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

<div class="form">
<form method="post" action="<?php print saveURL($args['page']); ?>">
<input type="hidden" name="pagesizelimit" value="<?=$PageSizeLimit?>">
<input type="submit" name="Save" value="Save"
    onClick="return sizeLimitCheck(this.form, 'document', 'text_before', 'text_after');">
<input type="submit" name="Preview" value="Preview"
    onClick="return sizeLimitCheck(this.form, 'document', 'text_before', 'text_after');">
<?php
  if($UserName != '')
    { print 'Your user name is ' . html_ref($UserName, $UserName); }
  else
  {
?>  Visit <a href="<?php print $PrefsScript; ?>">Preferences</a> to set your
user name<?php
  }
?>
 | <a href="#changes">View changes in this edit</a>
  <br />
  <input type="hidden" name="nextver" value="<?php print $args['nextver']; ?>">
  <input type="hidden" name="pagefrom" value="<?php print $args['pagefrom']; ?>">

  <input type="hidden" name="section" value="<?php print intval($args['section']); ?>">
  <input type="hidden" name="text_before" value="<?php print htmlspecialchars($args['text_before']); ?>">
  <input type="hidden" name="text_after" value="<?php print htmlspecialchars($args['text_after']); ?>">

<?php  if($args['archive'])
    {?>
  <input type="hidden" name="archive" value="1" />
<?php  }?>
  <textarea name="document" rows="<?php
    print $EditRows; ?>" cols="<?php
    print $EditCols; ?>" wrap="virtual"><?php
  print str_replace('<', '&lt;', str_replace('&', '&amp;', $args['text']));
?></textarea><br />
<?php
print '<input id="minoredit" type="checkbox" name="minoredit" value="1"';
if ($args['minoredit']) print ' CHECKED';
print '><label for="minoredit">Minor edit</label> ';

print '<input id="template" type="checkbox" name="template" value="1"';
if ($args['template']) print ' CHECKED';
print '><label for="template">This page is a template</label> ';
?>
<br>
  Summary of change:
  <input type="text" name="comment" size="40" maxlength="80" value="<?php
    print htmlspecialchars($comment); ?>" /><br />
<?php if ($ShowCategoryBox) : ?>
  Add document to category:
  <input type="text" name="categories" size="40" value="<?php
    print htmlspecialchars($categories); ?>" /><br />
<?php endif; ?>
  <input type="submit" name="Save" value="Save"
    onClick="return sizeLimitCheck(this.form, 'document', 'text_before', 'text_after');">
  <input type="submit" name="Preview" value="Preview"
    onClick="return sizeLimitCheck(this.form, 'document', 'text_before', 'text_after');">
<?php
  if($UserName != '')
    { print 'Your user name is ' . html_ref($UserName, $UserName); }
  else
  {
?>  Visit <a href="<?php print $PrefsScript; ?>">Preferences</a> to set your
user name<?php
  }
?>
 | <a href="#changes">View changes in this edit</a>

<div id="body" class="content">
<h1>Preview</h1>
<hr />
<?php print $args['html']; ?>
</div>
<hr />
<strong>Confirm changes to above document?</strong><br>
<input type="submit" name="Save" value="Save" onClick="return sizeLimitCheck(this.form.document);">
<input type="submit" name="Preview" value="Preview" onClick="return sizeLimitCheck(this.form.document);">

<hr />

<a name="changes"></a>
<h1>Changes in this edit</h1>

<?php if ($EnableWordDiff) : ?>
<br>
Diff method:
<input type="radio" id="regular_diff" name="diff_mode" value="0"<?=$regular_diff_checked?>><label for="regular_diff">Regular diff</label>
<input type="radio" id="word_diff" name="diff_mode" value="1"<?=$word_diff_checked?>><label for="word_diff">Word diff</label>
<?php endif; ?>

<hr />

<?php
if ($args['diff'])
    print '<div class="diff">' . $args['diff'] . '</div>';
else
    print 'There were no changes made in this edit.';
?>

</div>
</form>
<?php
    template_common_epilogue(array(
        'twin'      => $args['page'],
        'edit'      => '',
        'editver'   => -1,
        'history'   => $args['page'],
        'euser'     => $args['edituser'],
        'timestamp' => $args['timestamp'],

        'headlink'         => $args['page'],
        'button_selected'  => '',
        'button_view'      => 1,
        #'timestamp'       => $args['timestamp']  already specified
        #'editver'         => $args['editver']  no edit, already specified
        'button_backlinks' => 1
    ));
}
?>
