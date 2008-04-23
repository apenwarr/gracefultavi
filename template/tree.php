<?php

function _drawTree($path, $tree, $page)
{
    $out = '';
    
    if (count($tree))
    {
	foreach ($tree as $name => $node)
	{
	    if ($name == $page)
	        $path[] = "<b>$name</b>";
	    else
	        $path[] = html_ref($name, $name);
	    $out .= _drawTree($path, $node, $page);
	}
    }
    else
    {
	return join(" &lt; ", array_reverse($path)) . "<br>";
    }

    return $out;
}

function drawTree($tree, $smallFont = 0, $page = '', $i = 0, $previousEdges = '')
{
    global $WikiName;
    print "<div class='tree printhide'>" .
      _drawTree(array($WikiName), $tree, $page) .
      "</div>";
}
  
?>
