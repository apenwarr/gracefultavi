<?php
// $Id: view.php,v 1.9 2002/01/10 01:31:04 smoonen Exp $

require_once('template/common.php');

// The backlinks template is passed an associative array with the following
// elements:
//
//   page      => A string containing the name of the wiki page being viewed.
//   parents   =>
//   backlinks =>

function template_backlinks($args) {
    template_common_prologue(array('norobots' => 1,
                                   'title'    => 'Backlinks of ' . $args['page'],
                                   'heading'  => 'Backlinks of ',
                                   'headlink' => $args['page'],
                                   'headsufx' => '',
                                   'toolbar'  => 1));

    global $pagestore;

    $page = $args['page'];
    $backlinks = $args['backlinks'];
    $parents = $args['parents'];
?>

<h2>Backlinks and Nesting Information</h2>

<p>See <a href="<?=childrenURL($page)?>">children</a> of <?=$page?>.

<form name="reparentForm" action="<?php print reparentURL($page); ?>" method="POST">

<div class="form">

<p>
<?php print html_ref($page, $page); ?> is linked on the following pages:

<p>
<table cellspacing="0" cellpadding="0" border="0">
<tr class="backlinksHeader">
<td><b>Parent?</b></td>
<td>&nbsp;&nbsp;&nbsp;</td>
<td><b>Backlink</b></td>
</tr>

<?php
foreach($backlinks as $backlink)
{
    if ($backlink != $page)
    {
        if (in_array($backlink, $parents))
            $checked = " checked";
        else
            $checked = "";

        print '<tr>';
        print '<td align="center"><input type="checkbox" name="parents[]" value="' . $backlink . '"' . $checked. '></td>';
        print '<td>&nbsp;</td>';
        print '<td>' . html_ref($backlink, $backlink) . '</td>';
        print '</tr>';
    }
}
?>

</table>
</p>


<?php
$tempText = '';

foreach($parents as $parent)
{
    if (!in_array($parent, $backlinks))
    {
        $tempText .= '<tr>';
        $tempText .= '<td align="center"><input type="checkbox" name="parents[]" value="' . $parent . '" checked></td>';
        $tempText .= '<td>&nbsp;</td>';
        $tempText .= '<td>' . html_ref($parent, $parent) . '</td>';
        $tempText .= '</tr>';
    }
}

if ($tempText)
{
    print html_ref($page, $page) . ' is NOT linked on the following pages but is still referring to them as parents:';
    print '<p>';
    print '<table cellspacing="0" cellpadding="0" border="0">';
    print '<tr class="notBacklinksHeader">';
    print '<td><b>Parent?</b></td>';
    print '<td>&nbsp;</td>';
    print '<td><b>Not backlink</b></td>';
    print '</tr>';
    print $tempText;
    print '</table>';
    print '</p>';
}
?>


<p>
<input type="submit" name="reparentButton" value="Reparent">
</p>

</div>

</form>

<?php
    template_common_epilogue(array('twin'      => '',
                                   'edit'      => '',
                                   'editver'   => 0,
                                   'history'   => '',
                                   'timestamp' => ''));
}
?>
