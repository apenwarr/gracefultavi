<?php

function _drawEdges($edges)
{
    return preg_replace("/([0-3])/",
      "<img src=\"images/tree-edge\\1.png\" alt=\"\" align=\"top\" width=\"19\" height=\"17\" border=\"0\">",
      substr($edges, 1));
}

function _drawTree($tree, $smallFont, $page, $previousEdges)
{
    global $HomePage, $WikiName;
    
    if (count($tree))
    {
        // adjust the pictures of the previous edges
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

	    $drawEdges = _drawEdges($currentEdges);
	    $title = $name==$HomePage ? "<b>$WikiName</b>" : $name;
            $output = html_ref($name, $title);
            if ($name == $page) $output = "<a name=\"$name\"></a><b>$title</b>";
            if ($smallFont) $output = "<small>$output</small>";
            $output = "<tr><td nowrap>$drawEdges$output</td></tr>\n";
            print $output;

            _drawTree($node, $smallFont, $page, $currentEdges);
        }

    }
}

function drawTree($tree, $smallFont = 0, $page = '')
{
    print '<div class="tree" align=center><table>' . "\n";
    _drawTree($tree, $smallFont, $page, '');
    print "</table></div>\n";
}

?>
