<?php

require('parse/html.php');
require('template/find.php');

// Find a string in the database.
function action_find()
{
    global $pagestore, $find, $branch_search;

    $doFindOne = 1;

    // avoid findOne search if empty string, full text search, or branch search
    if ((trim($find) == '') || ($find[0] == '!') || $branch_search)
    {
        $doFindOne = 0;
    }

    // remove leading ! if full text search
    if ($find[0] == '!')
    {
        $find = substr($find, 1);
    }

    // try to find one page by its name
    if ($doFindOne && ($findOne = $pagestore->findOne(trim($find)))) {
        header('Location: ' . viewURL($findOne));
    }
    // or perform a full text search
    else
    {
        $list = $pagestore->find($find);

        if ($branch_search)
        {
            $branch_nodes = $pagestore->getTree($branch_search, '', 'FLAT');
            $list = array_intersect($list, $branch_nodes);
        }

        $text = '';
        foreach ($list as $page) {
            if ($page != $find) {
                $text .= html_ref($page, $page) . html_newline();
            }
        }

        template_find(array(
            'find'          => $find,
            'pages'         => $text,
            'branch_search' => $branch_search
        ));
    }
}
?>
