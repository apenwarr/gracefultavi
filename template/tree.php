<?php
function drawTreeOld($tree)
{
    if (count($tree))
    {
        print "<ul>";
        foreach ($tree as $name => $node)
        {
            print "<li>";
            print "<small>" . html_ref($name, $name) . "</small>";
            drawTreeOld($node);
            print "</li>";
        }
        print "</ul>";
    }
}

function drawTree($tree, $smallFont = 0, $page = '', $i = 0, $previousEdges = '')
{
    $i++;

    if (count($tree))
    {
        if ($i == 1)
            print '<table cellspacing="0" cellpadding="0" border="0">' . "\n";

        // ajust the pictures of the previous edges
        $previousEdges = preg_replace("/2/", "0", $previousEdges);
        $previousEdges = preg_replace("/3/", "1", $previousEdges);

        $j = 0;
        foreach ($tree as $name => $node)
        {
            $j++;
            if ($j == count($tree))
                $currentEdges = $previousEdges . '2'; // last node of the branch
            else
                $currentEdges = $previousEdges . '3';  // above the last node of the branch

            $drawEdges = substr($currentEdges, 1);
            $drawEdges = preg_replace("/([0-3])/", "<img src=\"images/tree-edge\\1.png\" alt=\"\" align=\"top\" width=\"19\" height=\"17\" border=\"0\">", $drawEdges);

            $output = html_ref($name, $name);
            if ($name == $page) $output = "<a name=\"$name\"></a><b>$output</b>";
            if ($smallFont) $output = "<small>$output</small>";
            $output = "<tr><td nowrap>$drawEdges$output</td></tr>\n";
            print $output;

            drawTree($node, $smallFont, $page, $i+1, $currentEdges);
        }

        if ($i == 1)
            print "</table>\n";
    }
}
?>
