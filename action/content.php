<?php
// $Id: save.php,v 1.7 2002/01/07 16:28:32 smoonen Exp $

require_once('template/tree.php');
require('parse/html.php');

function getAllNodesNames($tree, $i = 0)
{
    static $names;

    if ($i == 0) $names = array();

    foreach ($tree as $name => $node)
    {
        $names[] = $name;
        getAllNodesNames($node, $i + 1);
    }

    if ($i == 0) return $names;
}


// Display all nodes.
function action_content()
{
    global $page, $pagestore;

    // get all branches
    $tree = $pagestore->getTreeFromLeafs('FrontPage');

    // get other nodes that could not fit in a branch
    $allPages = $pagestore->getAllPageNames();
    $treePages = getAllNodesNames($tree);
    $otherPages = array_diff($allPages, $treePages);

    if ($tree['FrontPage'])
    {
        $content = array();
        $content['FrontPage'] = $tree['FrontPage'];
        unset ($tree['FrontPage']);
        $drawFrontPage = 1;
    }

    if (count($tree))
        $drawOutside = 1;
        
    if (count($otherPages))
        $drawOther = 1;

    print "<html>\n";
    print "<head>\n";
    print "<meta name=\"ROBOTS\" content=\"NOINDEX, NOFOLLOW\">\n";
    print "<link rel=\"SHORTCUT ICON\" href=\"images/niti-logo.ico\">\n";
    print "<title>Wiki content</title>\n";
    print "</head>\n";
    print "<body>";

    if ($drawFrontPage)
        print '<a href="#content">Wiki content</a><br>';
    if ($drawOutside)
        print '<a href="#outside">Singletons, lost branches and cycles</a><br>';
    if ($drawOther)
        print '<a href="#other">Other mysterious cases</a><br>';

    if ($drawFrontPage)
    {
        print '<a name="content"><h3>Wiki content</h3></a>';
        drawTree($content, false, $page);
    }

    if ($drawOutside)
    {
        print '<a name="outside"><h3>Singletons, lost branches and cycles</h3></a>';
        drawTree($tree, false, $page);
    }

    if ($drawOther)
    {
        print '<a name="other"><h3>Other mysterious cases</h3></a>';
        foreach ($otherPages as $page)
            print html_ref($page, $page) . '<br>';
    }

    print '</body></html>';
}
?>
