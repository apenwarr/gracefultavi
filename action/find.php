<?php
// $Id: find.php,v 1.6 2002/01/07 16:28:32 smoonen Exp $

require('parse/html.php');
require('template/find.php'); // require(TemplateDir . '/find.php');

function emptyElement($var)
{
    return trim($var) ? 1 : 0;
}

// Find a string in the database.
function action_find()
{
    global $pagestore, $find;

    // determine which 'find mode' to use
    if ($find[0] == '!')
    {
        // force a full text search, boolean words are not interpreted
        $find = substr($find, 1); // remove the leading '!'
        $search = array($find);
        $doFindOne = 0;
    }
    else
    {
        // check if boolean search is used
        $search = preg_split('/\sand\s/i', $find);
        $search = array_filter($search, "emptyElement");

        if (count($search) == 1)
            $doFindOne = 1; // will try to find one page by its name
        else
        {
            $find = preg_replace('/\sand\s/i', ' AND ', $find);
            $doFindOne = 0; // boolean full text search
        }
    }

    // -- From this point, '$search' is always an array. --

    // try to find one page by its name...
    if ($doFindOne && $findOne = $pagestore->findOne($search[0]))
        header('Location: ' . viewURL($findOne));
    else
    {
        // ...or perform a full text search

        if (count($search) <= 1)
            // empty or 1 word search
            $list = $pagestore->find($search[0]);
        else
        {
            // boolean search

            // get results for each string
            $buffer = array();
            foreach ($search as $string)
                $buffer[$string] = $pagestore->find($string);

            // merge the results
            $list = array_shift($buffer);
            while (count($buffer))
                $list = array_intersect($list, array_shift($buffer));
        }

        $text = '';
        foreach ($list as $page)
            if ($page != $find)
                $text .= html_ref($page, $page) . html_newline();

        template_find(array('find'  => $find,
                            'pages' => $text));
    }
}
?>
