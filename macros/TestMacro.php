<?php

class Macro_TestMacro
{
    // Set the toolbarButton attribute to add a button in the toolbar.
    // The value of this attribute is used as the label of the button.
    // The button is a link to execute the macro directly from the URL.
    // Leave this attribute set to an empty string if you do not wish
    // to add a button in the toolbar.
    var $toolbarButton = '';

	function parse($args, $page)
	{
		global $REMOTE_ADDR;
		return "Your IP is $REMOTE_ADDR. You passed the parameter \"$args\". This page is \"$page\".";
	}
}

return 1;

?>
