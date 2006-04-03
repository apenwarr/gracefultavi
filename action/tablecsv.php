<?php

require('parse/main.php');
require('parse/html.php');

function action_tablecsv()
{
    global $page, $pagestore, $tablenum;

    $pg = $pagestore->page($page);
    $pg->read();

    $parse_engine = array('parse_htmlpre', 'parse_nowiki', 'parse_tablecsv');
    $csv = parseText($pg->text, $parse_engine, $page);

    header('Content-Type: text/csv');
    header('Content-Disposition: filename="'.$page.'_'.$tablenum.'.csv"');

    print $csv;
}

?>
