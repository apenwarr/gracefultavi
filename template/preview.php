<?php
// $Id: preview.php,v 1.9 2002/01/10 01:31:04 smoonen Exp $

require_once('template/common.php');
#require_once(TemplateDir . '/common.php');

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
  global $EditRows, $EditCols, $categories, $UserName, $comment, $PrefsScript;
  global $minoredit;

  template_common_prologue(array('norobots' => 1,
                                 'title'    => 'Previewing ' . $args['page'],
                                 'heading'  => 'Previewing ',
                                 'headlink' => $args['page'],
                                 'headsufx' => '',
                                 'tree' => 1,
                                 'toolbar'  => 1));
?>
<div id="body" class="content">
<form method="post" action="<?php print saveURL($args['page']); ?>">
<div class="form">
  <input type="submit" name="Save" value="Save" />
  <input type="submit" name="Preview" value="Preview" />
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
print '<input type="checkbox" name="minoredit" value="1"';
if ($minoredit) print ' CHECKED';
print '>Minor edit<br>';
?>

  Summary of change:
  <input type="text" name="comment" size="40" value="<?php
    print $comment; ?>" /><br />
  Add document to category:
  <input type="text" name="categories" size="40" value="<?php
    print $categories; ?>" />
<h1>Preview</h1>
<hr />
<?php print $args['html']; ?>
</div>
<hr />
<strong>Confirm changes to above document?</strong><br>
<input type="submit" name="Save" value="Save" />
<input type="submit" name="Preview" value="Preview" />
</div>
</form>
<?php
  template_common_epilogue(array('twin'      => $args['page'],
                                 'edit'      => '',
                                 'editver'   => 0,
                                 'history'   => $args['page'],
                                 'euser'     => $args['edituser'],
                                 'timestamp' => $args['timestamp']));
}
?>
