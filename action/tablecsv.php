<?php

require('parse/main.php');
require('parse/html.php');

function action_tablecsv()
{
    global $page, $pagestore, $tablenum;

    $pg = $pagestore->page($page);
    $pg->read();

    $csv = parseText($pg->text, array('parse_tablecsv'), $page);

    header('Content-Type: text/csv');
    header('Content-Disposition: filename="'.$page.'_'.$tablenum.'.csv"');

    print $csv;
}

?>
