<?php
// $Id: style.php,v 1.1 2002/01/08 17:31:00 smoonen Exp $

// This function emits the current template's stylesheet.

function action_style()
{
  header("Content-type: text/css");

  require('template/wiki.css');
}

