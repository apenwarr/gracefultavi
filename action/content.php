<?php

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
    global $page, $pagestore, $HomePage;

    // get all branches
    $tree = $pagestore->getTreeFromLeaves($HomePage);

    // get other nodes that could not fit in a branch
    $allPages = $pagestore->getAllPageNames();
    $treePages = getAllNodesNames($tree);
    $otherPages = array_diff($allPages, $treePages);

    if ($tree[$HomePage])
    {
        $content = array();
        $content[$HomePage] = $tree[$HomePage];
        unset ($tree[$HomePage]);
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

    if (isset($drawFrontPage))
        print '<a href="#content">Wiki content</a><br>';

    if (isset($drawOutside))
        print '<a href="#outside">Singletons, lost branches and cycles</a><br>';

    if (isset($drawOther))
        print '<a href="#other">Other mysterious cases</a><br>';

    if (isset($drawFrontPage))
    {
        print '<a name="content"><h3>Wiki content</h3></a>';
        drawTree($content, false, $page);
    }

    if (isset($drawOutside))
    {
        print '<a name="outside"><h3>Singletons, lost branches and cycles</h3></a>';
        drawTree($tree, false, $page);
    }

    if (isset($drawOther))
    {
        print '<a name="other"><h3>Other mysterious cases</h3></a>';
        foreach ($otherPages as $page)
            print html_ref($page, $page) . '<br>';
    }

    print '<h3>Bad parenting</h3>';
    ob_flush();
    $pages = $pagestore->getAllPageNames();
    $found = 0;
    foreach ($pages as $page) {
        $backlinks = $pagestore->getBacklinks($page);
        $parents = $pagestore->getParents($page);
        foreach($parents as $parent) {
            if (!in_array($parent, $backlinks)){
                print '<a href="?action=backlinks&page='.rawurlencode($page).
                      '">'.htmlspecialchars($page).'</a> -- '.
                      htmlspecialchars($parent).'<br>';
                ob_flush();
                $found = 1;
            }
        }
    }
    if (!$found) { print 'None'; }

    print '</body></html>';
}
?>
