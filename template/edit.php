<?php
// $Id: edit.php,v 1.9 2002/01/10 01:31:04 smoonen Exp $

require('template/common.php');
#require_once(TemplateDir . '/common.php');

// The edit template is passed an associative array with the following
// elements:
//
//   page      => A string containing the name of the wiki page being edited.
//   pagefrom  => A string containing the name of the wiki page where the page being created is linked.
//   text      => A string containing the wiki markup of the wiki page.
//   timestamp => Timestamp of last edit to page.
//   nextver   => An integer; the expected version of this document when saved.
//   archive   => An integer.  Will be nonzero if this is not the most recent
//                version of the page.

function template_edit($args)
{
    global $EditRows, $EditCols, $UserName, $PrefsScript;

    template_common_prologue(array('norobots' => 1,
                                   'title'    => 'Editing ' . $args['page'],
                                   'heading'  => 'Editing ',
                                   'headlink' => $args['page'],
                                   'headsufx' => '',
                                   'tree' => 1,
                                   'toolbar'  => 1));
?>

<div id="body">

<form method="post" action="<?php print saveURL($args['page']); ?>">

<div class="form">

<input type="submit" name="Save" value="Save">
<input type="submit" name="Preview" value="Preview">

<?php
    if($UserName != '')
        print 'Your user name is ' . html_ref($UserName, $UserName);
    else
        print "Visit <a href=\"$PrefsScript\">Preferences</a> to set your user name";
?>

<input type="hidden" name="nextver" value="<?php print $args['nextver']; ?>">
<input type="hidden" name="pagefrom" value="<?php print $args['pagefrom']; ?>">

<?php
if($args['archive'])
    print '<input type="hidden" name="archive" value="1">';

print "<textarea name=\"document\" rows=\"$EditRows\" cols=\"$EditCols\" wrap=\"virtual\">";
print htmlspecialchars($args['text']);
print '</textarea>';
?>

<br>

<?php
print '<input type="checkbox" name="minoredit" value="1">Minor edit<br>';
?>

Summary of change:
<input type="text" name="comment" size="40" value=""><br>

Add document to category:
<input type="text" name="categories" size="40" value=""><br>

</div> <!-- Class form -->

</form>

</div> <!-- Body -->

<?php
    template_common_epilogue(array('watch'     => '',
                                   'twin'      => $args['page'],
                                   'edit'      => '',
                                   'editver'   => '',
                                   'history'   => $args['page'],
                                   'timestamp' => $args['timestamp']));
}
?>
