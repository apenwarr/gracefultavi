<?php

require('template/common.php');

// The edit template is passed an associative array with the following
// elements:
//
//   page      => A string containing the name of the wiki page being edited.
//   pagefrom  => A string containing the name of the wiki page where the page
//                being created is linked.
//   text      => A string containing the wiki markup of the wiki page.
//   timestamp => Timestamp of last edit to page.
//   nextver   => An integer; the expected version of this document when saved.
//   archive   => An integer.  Will be nonzero if this is not the most recent
//                version of the page.

function template_edit($args)
{
    global $EditCols, $EditRows, $PageSizeLimit, $PrefsScript, $UserName;

    template_common_prologue(array(
        'norobots' => 1,
        'title'    => 'Editing ' . $args['page'],
        'heading'  => 'Editing ',
        'headlink' => $args['page'],
        'headsufx' => '',
        'tree'     => 1,
        'toolbar'  => 1,

        'button_selected'  => 'edit',
        'button_view'      => 1,
        'timestamp'        => $args['timestamp'],
        'editver'          => $args['editver'],
        'button_backlinks' => 1
    ));
?>

<div id="body">

<form method="post" action="<?php print saveURL($args['page']); ?>">
<input type="hidden" name="pagesizelimit" value="<?=$PageSizeLimit?>">
<input type="hidden" name="nextver" value="<?php print $args['nextver']; ?>">
<input type="hidden" name="pagefrom" value="<?php print $args['pagefrom']; ?>">
<?php
if($args['archive'])
    print '<input type="hidden" name="archive" value="1">';
?>

<div class="form">

<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
<td>
<input type="submit" name="Save" value="Save" onClick="return sizeLimitCheck(this.form.document);">
<input type="submit" name="Preview" value="Preview" onClick="return sizeLimitCheck(this.form.document);">

<?php
if($UserName != '')
    print 'Your user name is ' . html_ref($UserName, $UserName);
else
    print "Visit <a href=\"$PrefsScript\">Preferences</a> to set your user name";
?>
</td>
<td align="right">
<?php
if ($args['templates'])
{
    print 'Templates: <select name="templateName">'."\n";
    print '<option value="">-- Select a template --'."\n";
    foreach ($args['templates'] as $template_name)
    {
        print '<option value="'.htmlspecialchars($template_name).'"';
        if ($template_name == $args['use_template']) { print ' selected'; }
        print '>'.htmlspecialchars($template_name)."\n";
    }
    print '</select>'."\n";

    $js_page = str_replace('\\', '\\\\', $args['page']);
    $js_page = str_replace('\'', '\\\'', $js_page);
    print '<input type="button" name="useTemplateButton" value="Use" '.
          'onClick="useTemplate(this.form.templateName, '."'$js_page'".')">'."\n";
}
?>
</td>
</tr>
</table>

<?php
print "<textarea name=\"document\" rows=\"$EditRows\" cols=\"$EditCols\" wrap=\"virtual\">";
print htmlspecialchars($args['text']);
print '</textarea>';
?>
<br>

<?php
$minorEditChecked = (substr($args['page'], -8) == 'Schedule') ? ' checked' : '';
print '<input id="minoredit" type="checkbox" name="minoredit" value="1"' .
      $minorEditChecked . '><label for="minoredit">Minor edit</label> ';

print '<input id="template" type="checkbox" name="template" value="1"'.
      ($args['template'] ? ' checked' : '') . '>'.
      '<label for="template">This page is a template</label> ';
?>
<br>

Summary of change:
<input type="text" name="comment" size="40" value=""><br>

Add document to category:
<input type="text" name="categories" size="40" value=""><br>

<input type="submit" name="Save" value="Save" onClick="return sizeLimitCheck(this.form.document);">
<input type="submit" name="Preview" value="Preview" onClick="return sizeLimitCheck(this.form.document);">

<?php
if($UserName != '')
    print 'Your user name is ' . html_ref($UserName, $UserName);
else
    print "Visit <a href=\"$PrefsScript\">Preferences</a> to set your user name";
?>
<br>

</div> <!-- Class form -->

</form>

</div> <!-- Body -->

<?php
    template_common_epilogue(array(
        'twin'      => $args['page'],
        'history'   => $args['page'],
        'euser'     => $args['edituser'],
        'timestamp' => $args['timestamp'],

        'headlink'         => $args['page'],
        'button_selected'  => 'edit',
        'button_view'      => 1,
        #'timestamp'       => $args['timestamp']  already specified
        'editver'          => $args['editver'],
        'button_backlinks' => 1
    ));
}
?>
