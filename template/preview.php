<?php

require_once('template/common.php');

// The preview template is passed an associative array with the following
// elements:
//
//   page      => A string containing the name of the wiki page being viewed.
//   text      => A string containing the wiki markup of the wiki page.
//   html      => A string containing the XHTML rendering of the wiki page.
//   timestamp => Timestamp of last edit to page.
//   nextver   => An integer; the expected version of this document when saved.
//   archive   => An integer.  Will be nonzero if this is not the most recent
//                version of the page.

function template_preview($args)
{
    global $categories, $comment, $EditCols, $EditRows, $PageSizeLimit;
    global $PrefsScript, $ShowCategoryBox, $UserName;

    template_common_prologue(array(
        'norobots' => 1,
        'title'    => 'Previewing ' . $args['page'],
        'heading'  => 'Previewing ',
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
?>

<div class="form">
<form method="post" action="<?php print saveURL($args['page']); ?>">
<input type="hidden" name="pagesizelimit" value="<?=$PageSizeLimit?>">
<input type="submit" name="Save" value="Save" onClick="return sizeLimitCheck(this.form.document);">
<input type="submit" name="Preview" value="Preview" onClick="return sizeLimitCheck(this.form.document);">
<?php
  if($UserName != '')
    { print 'Your user name is ' . html_ref($UserName, $UserName); }
  else
  {
?>  Visit <a href="<?php print $PrefsScript; ?>">Preferences</a> to set your
user name<?php
  }
?><br />
  <input type="hidden" name="nextver" value="<?php print $args['nextver']; ?>">
  <input type="hidden" name="pagefrom" value="<?php print $args['pagefrom']; ?>">
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
  <input type="submit" name="Save" value="Save" onClick="return sizeLimitCheck(this.form.document);">
  <input type="submit" name="Preview" value="Preview" onClick="return sizeLimitCheck(this.form.document);">
<?php
  if($UserName != '')
    { print 'Your user name is ' . html_ref($UserName, $UserName); }
  else
  {
?>  Visit <a href="<?php print $PrefsScript; ?>">Preferences</a> to set your
user name<?php
  }
?>

<div id="body" class="content">
<h1>Preview</h1>
<hr />
<?php print $args['html']; ?>
</div>
<hr />
<strong>Confirm changes to above document?</strong><br>
<input type="submit" name="Save" value="Save" onClick="return sizeLimitCheck(this.form.document);">
<input type="submit" name="Preview" value="Preview" onClick="return sizeLimitCheck(this.form.document);">
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
