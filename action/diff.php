<?php
// $Id: diff.php,v 1.7 2002/01/07 16:28:32 smoonen Exp $

require('parse/main.php');
require('parse/macros.php');
require('parse/html.php');
require('template/diff.php');  #require(TemplateDir . '/diff.php');
require('lib/diff.php');

// Compute difference between two versions of a page.
function action_diff()
{
    global $page, $pagestore, $ParseEngine, $UserName, $ver1, $ver2;

    $p1 = $pagestore->page($page);
    $p1->version = $ver1;
    $p2 = $pagestore->page($page);
    $p2->version = $ver2;

    $diff = diff_compute($p1->read(), $p2->read());

    template_diff(array(
        'page'      => $page,
        'diff_html' => diff_parse($diff),
        'html'      => parseText($p2->text, $ParseEngine, $page),
        'editable'  => $p2->mutable,
        'timestamp' => $p2->time,
        'editver'   => ($UserName && $p2->mutable) ? 0 : -1
    ));
}
?>
