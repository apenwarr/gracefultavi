<?php

class Macro_TestMacro
{
	var $pagestore;

	function parse($args, $page)
	{
		global $REMOTE_ADDR;
		return "Your IP is $REMOTE_ADDR. You passed the parameter \"$args\". This page is \"$page\".";
	}
}

return 1;

?>
